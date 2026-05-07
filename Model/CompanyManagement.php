<?php
/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Model;

use Orangecat\Company\Api\CompanyManagementInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\Company\Api\RoleRepositoryInterface;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer as CompanyCustomerResource;
use Orangecat\Company\Model\CompanyCustomerFactory;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as CompanyCustomerCollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Orangecat\Company\Model\CompanyCustomer;

use Magento\Framework\Registry;

class CompanyManagement implements CompanyManagementInterface
{
    /**
     * @param CompanyCustomerResource $resource
     * @param CompanyCustomerFactory $companyCustomerFactory
     * @param CompanyCustomerCollectionFactory $collectionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param CompanyRepositoryInterface $companyRepository
     * @param RoleRepositoryInterface $roleRepository
     * @param Registry $registry
     */
    public function __construct(
        private CompanyCustomerResource $resource,
        private CompanyCustomerFactory $companyCustomerFactory,
        private CompanyCustomerCollectionFactory $collectionFactory,
        private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private CompanyRepositoryInterface $companyRepository,
        private RoleRepositoryInterface $roleRepository,
        private Registry $registry
    ) {
    }

    /**
     * @inheritdoc
     */
    public function assignCustomer($companyId, $customerId, $roleId, array $data = [])
    {
        // Validate Company exists
        try {
            $this->companyRepository->get($companyId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The specified company does not exist.'));
        }

        // Validate Customer exists
        try {
            $this->customerRepository->getById($customerId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The specified customer does not exist.'));
        }

        // Validate Role exists
        try {
            $this->roleRepository->get($roleId);
        } catch (NoSuchEntityException $e) {
            throw new LocalizedException(__('The specified role does not exist.'));
        }

        // Check if link exists
        $link = $this->getLinkByCustomerId($customerId);

        if ($link && $link->getId()) {
            // Update existing
            $link->setCompanyId($companyId);
            $link->setRoleId($roleId);
        } else {
            // Create new
            $link = $this->companyCustomerFactory->create();
            $link->setCompanyId($companyId);
            $link->setCustomerId($customerId);
            $link->setRoleId($roleId);
        }

        if (!empty($data)) {
            $link->addData($data);
        }

        try {
            $this->resource->save($link);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not assign customer to company: %1', $e->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function removeCustomer($customerId)
    {
        $link = $this->getLinkByCustomerId($customerId);
        if ($link && $link->getId()) {
            try {
                $this->resource->delete($link);
            } catch (\Exception $e) {
                throw new CouldNotDeleteException(__('Could not remove customer from company: %1', $e->getMessage()));
            }
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCompanyIdByCustomerId($customerId)
    {
        $link = $this->getLinkByCustomerId($customerId);
        if ($link && $link->getId()) {
            return $link->getCompanyId();
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRoleIdByCustomerId($customerId)
    {
        $link = $this->getLinkByCustomerId($customerId);
        if ($link && $link->getId()) {
            return $link->getRoleId();
        }
        return null;
    }

    /**
     * Get Link Model by Customer ID
     *
     * @param int $customerId
     * @return CompanyCustomer
     */
    private function getLinkByCustomerId($customerId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->setPageSize(1);
        return $collection->getFirstItem();
    }

    /**
     * Delete customer by ID with secure area bypass
     *
     * @param int $customerId
     * @return void
     * @throws CouldNotDeleteException
     */
    public function deleteCustomerById($customerId)
    {
        try {
            $this->registry->register('isSecureArea', true);
            $this->customerRepository->deleteById($customerId);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete customer account: %1', $e->getMessage()));
        } finally {
            $this->registry->unregister('isSecureArea');
        }
    }

    /**
     * @inheritdoc
     */
    public function isCompanyAdmin(int $customerId): bool
    {
        $roleId = $this->getRoleIdByCustomerId($customerId);
        $companyId = $this->getCompanyIdByCustomerId($customerId);

        return $roleId == 1 && $companyId;
    }

    /**
     * @inheritdoc
     */
    public function validateManageUser(int $adminId, ?int $targetUserId): void
    {
        if (!$this->isCompanyAdmin($adminId)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('You do not have permission to manage users.')
            );
        }

        if ($targetUserId) {
            $adminCompanyId = $this->getCompanyIdByCustomerId($adminId);
            $targetCompanyId = $this->getCompanyIdByCustomerId($targetUserId);

            if ((int)$adminCompanyId !== (int)$targetCompanyId) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('You cannot manage a user who does not belong to your company.')
                );
            }
        }
    }
}
