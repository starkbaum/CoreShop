<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

namespace CoreShop\Bundle\FrontendBundle\Controller;

use CoreShop\Component\Core\Model\OrderInterface;
use CoreShop\Component\Order\Checkout\CheckoutException;
use CoreShop\Component\Order\Checkout\CheckoutManagerFactoryInterface;
use CoreShop\Component\Order\Checkout\CheckoutStepInterface;
use CoreShop\Component\Order\Context\CartContextInterface;
use CoreShop\Component\Order\Workflow\WorkflowManagerInterface;
use Payum\Core\Payum;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

class CheckoutController extends FrontendController
{
    /**
     * @var CheckoutManagerFactoryInterface
     */
    protected $checkoutManagerFactory;

    /**
     * @param CheckoutManagerFactoryInterface $checkoutManagerFactory
     */
    public function __construct(CheckoutManagerFactoryInterface $checkoutManagerFactory)
    {
        $this->checkoutManagerFactory = $checkoutManagerFactory;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function processAction(Request $request)
    {
        $checkoutManager = $this->checkoutManagerFactory->createCheckoutManager($this->getCart());
        
        /**
         * @var CheckoutStepInterface
         */
        $stepIdentifier = $request->get('stepIdentifier');
        $step = $checkoutManager->getStep($stepIdentifier);
        $dataForStep = [];
        $cart = $this->getCart();

        if (!$step instanceof CheckoutStepInterface) {
            return $this->redirectToRoute('coreshop_index');
        }

        //Check all previous steps if they are valid, if not, redirect back
        foreach ($checkoutManager->getPreviousSteps($stepIdentifier) as $previousStep) {
            if (!$previousStep->validate($cart)) {
                return $this->redirectToRoute('coreshop_checkout', ['stepIdentifier' => $previousStep->getIdentifier()]);
            }
        }

        if ($step->validate($cart) && $step->doAutoForward($cart)) {
            $nextStep = $checkoutManager->getNextStep($stepIdentifier);

            if ($nextStep) {
                return $this->redirectToRoute('coreshop_checkout', ['stepIdentifier' => $nextStep->getIdentifier()]);
            }
        }

        if ($request->isMethod('POST')) {
            try {
                if ($step->commitStep($cart, $request)) {
                    $nextStep = $checkoutManager->getNextStep($stepIdentifier);

                    if ($nextStep) {
                        return $this->redirectToRoute('coreshop_checkout', ['stepIdentifier' => $nextStep->getIdentifier()]);
                    }
                }
            } catch (CheckoutException $ex) {
                $dataForStep['exception'] = $ex->getTranslatableText();
            }
        }
        
        //$errors = $this->get('validator')->validate($cart, null, ['coreshop']);

        $this->get('coreshop.tracking.manager')->trackCheckoutStep($cart, $step);

        $dataForStep = array_merge($dataForStep, $checkoutManager->prepareStep($step, $cart, $request));

        $dataForStep = array_merge(is_array($dataForStep) ? $dataForStep : [], [
            'cart' => $cart,
            'checkoutSteps' => $checkoutManager->getSteps(),
            'currentStep' => $checkoutManager->getCurrentStepIndex($stepIdentifier),
            'step' => $step,
            'identifier' => $stepIdentifier,
        ]);


        return $this->renderResponseForCheckoutStep($request, $step, $stepIdentifier, $dataForStep);
    }

    /**
     * @param Request $request
     * @param CheckoutStepInterface $step
     * @param $stepIdentifier
     * @param $dataForStep
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function renderResponseForCheckoutStep(Request $request, CheckoutStepInterface $step, $stepIdentifier, $dataForStep)
    {
        return $this->renderTemplate(sprintf('@CoreShopFrontend/Checkout/steps/%s.html.twig', $stepIdentifier), $dataForStep);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function doCheckoutAction(Request $request)
    {
        $checkoutManager = $this->checkoutManagerFactory->createCheckoutManager($this->getCart());

        /*
         * after the last step, we come here
         *
         * what are we doing here?
         *  1. Create Order with Workflow State: initialized
         *  2. Use Payum and redirect to Payment Provider
         *  3. PayumBundle takes care about payment stuff
         *  4. After Payment is done, we return to PayumBundle PaymentController and further process it
         *
         * therefore we need the CartToOrderTransformerInterface here
        */

        /*
         * Before we do anything else, lets check if the checkout is still valid
         * Check all previous steps if they are valid, if not, redirect back
         */

        /**
         * @var $step CheckoutStepInterface
         */
        foreach ($checkoutManager->getSteps($this->getCart()) as $stepIdentifier) {
            $step = $checkoutManager->getStep($stepIdentifier);

            if (!$step->validate($this->getCart())) {
                return $this->redirectToRoute('coreshop_checkout', ['stepIdentifier' => $step->getIdentifier()]);
            }
        }

        $this->get('coreshop.tracking.manager')->trackCheckoutAction($this->getCart(), count($checkoutManager->getSteps($this->getCart())));

        /**
         * If everything is valid, we continue with Order-Creation.
         */
        $order = $this->getOrderFactory()->createNew();
        $order = $this->getCartToOrderTransformer()->transform($this->getCart(), $order);

        if (0 === $order->getTotal()) {
            $request->getSession()->set('coreshop_order_id', $order->getId());
            
            $this->get('coreshop.workflow.manager.order')->changeState($order, 'change_order_state', [
                'newState' => WorkflowManagerInterface::ORDER_STATE_PROCESSING,
                'newStatus' => WorkflowManagerInterface::ORDER_STATUS_PROCESSING,
            ]);

            return $this->redirectToRoute('coreshop_checkout_confirmation');
        }

        /*
         * TODO: Not sure if we should create payment object right here, if so, the PaymentBundle would'nt be responsible for it :/
        */
        return $this->redirectToRoute('coreshop_payment', ['order' => $order->getId()]);
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function errorAction(Request $request)
    {
        $orderId = $request->getSession()->get('coreshop_order_id', null);

        if (null === $orderId) {
            return $this->redirectToRoute('coreshop_index');
        }

        $request->getSession()->remove('coreshop_order_id');

        /**
         * @var $order OrderInterface
         */
        $order = $this->get('coreshop.repository.order')->find($orderId);
        Assert::notNull($order);

        $payments = $order->getPayments();
        $lastPayment = is_array($payments) ? $payments[count($payments) - 1] : null;

        return $this->renderTemplate('@CoreShopFrontend/Checkout/error.html.twig', [
            'order' => $order,
            'payments' => $payments,
            'lastPayment' => $lastPayment
        ]);
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function thankYouAction(Request $request)
    {
        $orderId = $request->getSession()->get('coreshop_order_id', null);

        if (null === $orderId) {
            return $this->redirectToRoute('coreshop_index');
        }

        $request->getSession()->remove('coreshop_order_id');
        $order = $this->get('coreshop.repository.order')->find($orderId);
        Assert::notNull($order);

        $this->get('coreshop.tracking.manager')->trackCheckoutComplete($order);

        //After successfull payment, we log out the customer
        if ($this->get('coreshop.context.customer')->getCustomer()->getIsGuest()) {
            $this->get('security.token_storage')->setToken(null);
        }

        return $this->renderTemplate('@CoreShopFrontend/Checkout/thank-you.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * @return \CoreShop\Component\Order\Model\CartInterface
     */
    protected function getCart()
    {
        return $this->getCartContext()->getCart();
    }

    /**
     * @return CartContextInterface
     */
    protected function getCartContext()
    {
        return $this->get('coreshop.context.cart');
    }

    /**
     * @return \CoreShop\Bundle\OrderBundle\Manager\CartManager
     */
    protected function getCartManager()
    {
        return $this->get('coreshop.cart.manager');
    }

    /**
     * @return \CoreShop\Component\Order\Transformer\CartToOrderTransformer
     */
    protected function getCartToOrderTransformer()
    {
        return $this->get('coreshop.order.transformer.cart_to_order');
    }

    /**
     * @return \CoreShop\Component\Resource\Factory\PimcoreFactory
     */
    protected function getOrderFactory()
    {
        return $this->get('coreshop.factory.order');
    }

    /**
     * @return Payum
     */
    protected function getPayum()
    {
        return $this->get('payum');
    }
}
