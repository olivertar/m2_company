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

namespace Orangecat\Company\Observer\Adminhtml\Customer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Orangecat\Company\Model\CompanyCustomerFactory;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer as CompanyCustomerResource;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory;
use Orangecat\Company\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Orangecat\Company\Model\CompanyCustomer;

class SaveCompanyObserver implements ObserverInterface
{
    /**
     * @param RequestInterface $request
     * @param CompanyCustomerFactory $companyCustomerFactory
     * @param CollectionFactory $collectionFactory
     * @param RoleCollectionFactory $roleCollectionFactory
     * @param CompanyCustomerResource $companyCustomerResource
     */
    public function __construct(
        private RequestInterface $request,
        private CompanyCustomerFactory $companyCustomerFactory,
        private CollectionFactory $collectionFactory,
        private RoleCollectionFactory $roleCollectionFactory,
        private CompanyCustomerResource $companyCustomerResource
    ) {
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();
        $customerId = (int)$customer->getId();

        // Form data is usually inside 'customer' array
        $customerData = $this->request->getParam('customer') ?? [];

        // If company_id is not in the form data, we should not touch the company assignment
        if (!array_key_exists('company_id', $customerData)) {
            return;
        }

        // M2: explicit int cast and validation before use in filters
        $companyId = (int)($customerData['company_id'] ?? 0);
        $roleId = !empty($customerData['role_id']) ? (int)$customerData['role_id'] : null;

        // Check for existing link
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);

        /** @var CompanyCustomer $link */
        $link = $collection->getFirstItem();

        $connection = $this->companyCustomerResource->getConnection();

        if ($companyId) {
            if (!$roleId) {
                throw new LocalizedException(__('Please select a company role.'));
            }

            $adminRoleId = $this->getAdminRoleId();

            // Rule 1: Single Admin per Company
            if ($roleId == $adminRoleId) {
                if ($this->hasOtherAdmin($companyId, $customerId, $adminRoleId)) {
                    throw new LocalizedException(
                        __('This company already has an administrator. Only one administrator is allowed.')
                    );
                }
            }

            // Rule 2: Old Company must have Admin (if moving)
            if ($link->getId() && $link->getCompanyId() != $companyId && $link->getRoleId() == $adminRoleId) {
                if (!$this->hasOtherAdmin($link->getCompanyId(), $customerId, $adminRoleId)) {
                    throw new LocalizedException(
                        __(
                            'The previous company must have an administrator. ' .
                                'Please assign another administrator before leaving.'
                        )
                    );
                }
            }

            // Rule 2b: If staying in same company but changing role from Admin to something else
            if ($link->getId() && $link->getCompanyId() == $companyId &&
                $link->getRoleId() == $adminRoleId && $roleId != $adminRoleId
            ) {
                if (!$this->hasOtherAdmin($link->getCompanyId(), $customerId, $adminRoleId)) {
                    throw new LocalizedException(
                        __('This company must have an administrator. You cannot demote the only administrator.')
                    );
                }
            }

            // Create or Update
            if (!$link->getId()) {
                $link = $this->companyCustomerFactory->create();
                $link->setCustomerId($customerId);
            }

            $link->setCompanyId($companyId);
            $link->setRoleId($roleId);

            // M1: wrap write in transaction
            $connection->beginTransaction();
            try {
                $link->save();
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        } else {
            // Delete if exists and company_id is cleared
            if ($link->getId()) {
                $adminRoleId = $this->getAdminRoleId();
                // Rule 2: Old Company must have Admin (if unassigning)
                if ($link->getRoleId() == $adminRoleId) {
                    if (!$this->hasOtherAdmin($link->getCompanyId(), $customerId, $adminRoleId)) {
                        throw new LocalizedException(
                            __(
                                'The company must have an administrator. ' .
                                    'Please assign another administrator before unassigning the current one.'
                            )
                        );
                    }
                }

                // M1: wrap write in transaction
                $connection->beginTransaction();
                try {
                    $link->delete();
                    $connection->commit();
                } catch (\Exception $e) {
                    $connection->rollBack();
                    throw $e;
                }
            }
        }
    }

    /**
     * Get Admin Role ID
     *
     * @return int
     */
    private function getAdminRoleId()
    {
        $collection = $this->roleCollectionFactory->create();
        // Assuming 'Company Admin' is the name. If not found, try 'Admin'.
        // Better to check your database seeding.
        // For now, let's try 'Company Admin'.
        $collection->addFieldToFilter('role_name', 'Company Admin');
        $role = $collection->getFirstItem();

        if ($role->getId()) {
            return (int)$role->getId();
        }

        return 0;
    }

    /**
     * Check if company has other administrator
     *
     * @param int $companyId
     * @param int $customerId
     * @param int $adminRoleId
     * @return bool
     */
    private function hasOtherAdmin($companyId, $customerId, $adminRoleId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('company_id', $companyId);
        $collection->addFieldToFilter('role_id', $adminRoleId);
        $collection->addFieldToFilter('customer_id', ['neq' => $customerId]);

        return $collection->getSize() > 0;
    }
}
