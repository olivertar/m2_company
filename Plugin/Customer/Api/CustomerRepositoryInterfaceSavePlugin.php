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
use Magento\Framework\App\Request\Http;
use Magento\Framework\Exception\LocalizedException;
use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory;
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Model\CompanyCustomer;

class CustomerRepositoryInterfaceSavePlugin
{
    /**
     * @param Http $request
     * @param CompanyManagementInterface $companyManagement
     * @param CollectionFactory $companyCustomerCollectionFactory
     * @param \Orangecat\Company\Model\Config $companyConfig
     */
    public function __construct(
        private Http $request,
        private CompanyManagementInterface $companyManagement,
        private CollectionFactory $companyCustomerCollectionFactory,
        private \Orangecat\Company\Model\Config $companyConfig
    ) {
    }

    /**
     * Check context before save
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $customer
     * @param string|null $passwordHash
     * @return array
     */
    public function beforeSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $customer,
        $passwordHash = null
    ) {
        $extensionAttributes = $customer->getExtensionAttributes();
        $companyAttributes = $extensionAttributes ? $extensionAttributes->getCompanyAttributes() : null;

        if ($companyAttributes && $companyAttributes->getCompanyId()) {
            $this->companyConfig->setApiCompanyContext(true);
        }

        return [$customer, $passwordHash];
    }

    /**
     * Update Company assignment after customer save
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $result
     * @param CustomerInterface $customer
     * @param string|null $passwordHash
     * @return CustomerInterface
     * @throws LocalizedException
     */
    public function afterSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result,
        CustomerInterface $customer,
        $passwordHash = null
    ): CustomerInterface {
        $extensionAttributes = $customer->getExtensionAttributes();
        $companyAttributes = $extensionAttributes ? $extensionAttributes->getCompanyAttributes() : null;

        $customerId = (int)$result->getId();
        $companyId = null;
        $roleId = null;

        // Priority 1: API (Extension Attributes)
        if ($companyAttributes) {
            $companyId = (int)$companyAttributes->getCompanyId();
            $roleId = (int)$companyAttributes->getRoleId();
        } else {
            // Priority 2: Admin Panel (Post Data)
            $data = $this->request->getPostValue('customer');
            // Check if Company Tab was loaded to avoid clearing un-displayed relations
            if (empty($data['company_tab_loaded'])) {
                return $result;
            }
            $companyId = !empty($data['company_id']) ? (int)$data['company_id'] : null;
            $roleId = !empty($data['role_id']) ? (int)$data['role_id'] : null;
        }

        if ($currentAdminLink = $this->getCurrentAdminLink($customerId)) {
            // Check 1: Unassigning
            if (!$companyId) {
                throw new LocalizedException(
                    __(
                        'This customer is a Company Administrator (Company ID: %1). ' .
                            'You cannot unassign them. Please replace the administrator of the company first.',
                        $currentAdminLink->getCompanyId()
                    )
                );
            }

            // Check 2: Changing Company
            if ($companyId != $currentAdminLink->getCompanyId()) {
                throw new LocalizedException(
                    __(
                        'This customer is a Company Administrator (Company ID: %1). ' .
                            'You cannot change their company. ' .
                            'Please replace the administrator of the old company first.',
                        $currentAdminLink->getCompanyId()
                    )
                );
            }

            // Check 3: Changing Role
            if ($roleId != RoleInterface::ADMIN_ROLE_ID) {
                throw new LocalizedException(
                    __(
                        'This customer is a Company Administrator. ' .
                            'You cannot change their role to User. ' .
                            'Please replace the administrator of the company first.'
                    )
                );
            }
        }

        if ($companyId) {
            if (!$roleId) {
                throw new LocalizedException(__('Please select a Company Role.'));
            }

            // Validation: Company Admin Uniqueness (for new assignments)
            if ($roleId === RoleInterface::ADMIN_ROLE_ID) {
                $this->validateCompanyAdmin($companyId, $customerId);
            }

            $this->companyManagement->assignCustomer($companyId, $customerId, $roleId);
        } else {
            // If tab loaded but no company selected, unassign
            $this->companyManagement->removeCustomer($customerId);
        }

        return $result;
    }

    /**
     * Get current Admin link for customer
     *
     * @param int $customerId
     * @return CompanyCustomer|null
     */
    private function getCurrentAdminLink(int $customerId)
    {
        $collection = $this->companyCustomerCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('role_id', RoleInterface::ADMIN_ROLE_ID);
        $item = $collection->getFirstItem();
        return $item->getId() ? $item : null;
    }

    /**
     * Validate that company doesn't already have another admin
     *
     * @param int $companyId
     * @param int $currentCustomerId
     * @throws LocalizedException
     */
    private function validateCompanyAdmin(int $companyId, int $currentCustomerId): void
    {
        $collection = $this->companyCustomerCollectionFactory->create();
        $collection->addFieldToFilter('company_id', $companyId);
        $collection->addFieldToFilter('role_id', RoleInterface::ADMIN_ROLE_ID);
        $collection->addFieldToFilter('customer_id', ['neq' => $currentCustomerId]);

        if ($collection->getSize() > 0) {
            /** @var CompanyCustomer $existingAdmin */
            $existingAdmin = $collection->getFirstItem();
            throw new LocalizedException(
                __(
                    'This company already has an Administrator (Customer ID: %1). ' .
                        'A company can only have one administrator.',
                    $existingAdmin->getCustomerId()
                )
            );
        }
    }
}
