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

namespace Orangecat\Company\Plugin\Customer\Api;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory;
use Orangecat\Company\Model\CompanyCustomer;

class CustomerRepositoryInterfacePlugin
{

    /**
     * @param CollectionFactory $companyCustomerCollectionFactory
     */
    public function __construct(
        private CollectionFactory $companyCustomerCollectionFactory
    ) {
    }

    /**
     * Prevent deletion if customer is a Company Admin
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $customer
     * @return void
     * @throws LocalizedException
     */
    public function beforeDelete(CustomerRepositoryInterface $subject, CustomerInterface $customer): void
    {
        $this->checkIfCustomerIsCompanyAdmin((int)$customer->getId());
    }

    /**
     * Prevent deletion by ID if customer is a Company Admin
     *
     * @param CustomerRepositoryInterface $subject
     * @param int $customerId
     * @return void
     * @throws LocalizedException
     */
    public function beforeDeleteById(CustomerRepositoryInterface $subject, $customerId): void
    {
        $this->checkIfCustomerIsCompanyAdmin((int)$customerId);
    }

    /**
     * Check if customer is a company admin and throw exception if true
     *
     * @param int $customerId
     * @throws LocalizedException
     */
    private function checkIfCustomerIsCompanyAdmin(int $customerId): void
    {
        $collection = $this->companyCustomerCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('role_id', RoleInterface::ADMIN_ROLE_ID);

        /** @var CompanyCustomer $companyCustomer */
        $companyCustomer = $collection->getFirstItem();

        if ($companyCustomer->getId()) {
            throw new LocalizedException(
                __(
                    'Customer cannot be deleted because they are a Company Administrator (Company ID: %1). ' .
                        'Please replace the administrator of the company before deleting this customer.',
                    $companyCustomer->getCompanyId()
                )
            );
        }
    }
}
