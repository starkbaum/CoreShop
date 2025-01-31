<?php
/**
 * CoreShop.
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) CoreShop GmbH (https://www.coreshop.org)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace CoreShop\Bundle\ProductBundle\EventListener;

use CoreShop\Component\Product\Model\ProductInterface;
use CoreShop\Component\Product\Repository\ProductSpecificPriceRuleRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\ElementEventInterface;

final class ProductDeleteListener
{
    public function __construct(private ProductSpecificPriceRuleRepositoryInterface $repository, private EntityManagerInterface $entityManager)
    {
    }

    public function onPostDelete(ElementEventInterface $event): void
    {
        if ($event instanceof DataObjectEvent) {
            $object = $event->getObject();

            if (!$object instanceof ProductInterface) {
                return;
            }

            $entities = $this->repository->findForProduct($object);

            foreach ($entities as $rule) {
                $this->entityManager->remove($rule);
            }

            $this->entityManager->flush();
        }
    }
}
