<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Model\ResourceModel\CompanyCustomer;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Orangecat\Company\Model\CompanyCustomer;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer as CompanyCustomerResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'link_id';

    /**
     * Initialize collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CompanyCustomer::class, CompanyCustomerResource::class);
    }
}
