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

namespace CoreShop\Component\Core\Model;

use CoreShop\Component\Resource\Model\ResourceInterface;
use CoreShop\Component\Store\Model\StoreAwareInterface;
use Doctrine\Common\Collections\Collection;

interface ProductStoreValuesInterface extends ResourceInterface, StoreAwareInterface
{
    /**
     * @return int
     */
    public function getPrice();

    public function setPrice(int $price);

    /**
     * @return ProductInterface
     */
    public function getProduct();

    public function setProduct(ProductInterface $product);

    public function addProductUnitDefinitionPrice(ProductUnitDefinitionPriceInterface $productUnitDefinitionPrice);

    public function removeProductUnitDefinitionPrice(ProductUnitDefinitionPriceInterface $productUnitDefinitionPrice);

    /**
     * @return Collection<int, ProductUnitDefinitionPriceInterface>|ProductUnitDefinitionPriceInterface[]
     */
    public function getProductUnitDefinitionPrices();
}
