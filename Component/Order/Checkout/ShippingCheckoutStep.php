<?php

namespace CoreShop\Component\Order\Checkout;

use CoreShop\Component\Address\Model\AddressInterface;
use CoreShop\Component\Order\Model\CartInterface;
use Symfony\Component\HttpFoundation\Request;

class ShippingCheckoutStep implements CheckoutStepInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(CartInterface $cart)
    {
        //return $cart->getCarrier() instanceof CarrierInterface;

        //TODO: Implement Carrier stuff
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function commitStep(CartInterface $cart, Request $request)
    {
        //TODO: Implement Shipping/Carrier Form Type, validate here and apply carrier to cart
    }

    /**
     * {@inheritdoc}
     */
    public function prepareStep(CartInterface $cart)
    {
        //TODO Implement Shipping/Carrier Form Type and return here
    }
}