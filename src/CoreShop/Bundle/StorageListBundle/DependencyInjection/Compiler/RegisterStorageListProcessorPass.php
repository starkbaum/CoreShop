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

namespace CoreShop\Bundle\StorageListBundle\DependencyInjection\Compiler;

use CoreShop\Component\Registry\PrioritizedCompositeServicePass;

final class RegisterStorageListProcessorPass extends PrioritizedCompositeServicePass
{
    public function __construct(string $serviceId, string $compositeId, string $tagName)
    {
        parent::__construct(
            $serviceId,
            $compositeId,
            $tagName,
            'addProcessor'
        );
    }
}
