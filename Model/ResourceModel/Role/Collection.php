<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Model\ResourceModel\Role;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Orangecat\Company\Model\Role;
use Orangecat\Company\Model\ResourceModel\Role as RoleResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'role_id';

    /**
     * Initialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Role::class, RoleResource::class);
    }
}
