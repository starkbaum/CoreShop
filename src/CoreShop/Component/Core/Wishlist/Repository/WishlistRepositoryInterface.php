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

namespace CoreShop\Component\Core\Wishlist\Repository;

use CoreShop\Component\Customer\Model\CustomerInterface;
use CoreShop\Component\StorageList\Core\Repository\CustomerAndStoreAwareRepositoryInterface;
use CoreShop\Component\Store\Model\StoreInterface;
use CoreShop\Component\Wishlist\Model\WishlistInterface;

interface WishlistRepositoryInterface extends
    \CoreShop\Component\Wishlist\Repository\WishlistRepositoryInterface,
    CustomerAndStoreAwareRepositoryInterface
{
    public function findLatestByStoreAndCustomer(StoreInterface $store, CustomerInterface $customer): ?WishlistInterface;
}
