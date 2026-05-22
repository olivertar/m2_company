<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Orangecat\Company\Plugin\Customer\Model;

use Magento\Customer\Model\Customer\DataProvider;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory;

class DataProviderPlugin
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private CollectionFactory $collectionFactory
    ) {
    }

    /**
     * Add company data to customer data provider
     *
     * @param DataProvider $subject
     * @param array $result
     * @return array
     */
    public function afterGetData(DataProvider $subject, array $result)
    {
        foreach ($result as $customerId => &$data) {
            if (!isset($data['customer'])) {
                continue;
            }

            // Check if link exists
            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('customer_id', $customerId);

            /** @var \Orangecat\Company\Model\CompanyCustomer $link */
            $link = $collection->getFirstItem();

            if ($link->getId()) {
                // Determine company_id and role_id field mappings
                // Assuming standard customer form structure where custom attributes are top-level or inside customer
                // Ui component maps field name "company_id" to top level by default

                // Add to customer data array
                $data['customer']['company_id'] = $link->getData('company_id');
                $data['customer']['role_id'] = $link->getData('role_id');

                // Also add to top level just in case UI uses flat structure for these new fields
                $data['company_id'] = $link->getData('company_id');
                $data['role_id'] = $link->getData('role_id');
            }
        }

        return $result;
    }
}
