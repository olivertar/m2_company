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

namespace Orangecat\Company\Controller\Adminhtml\Company;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\ResultInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\Company\Api\Data\CompanyInterfaceFactory;
use Orangecat\Company\Api\Data\RoleInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Orangecat\Company\Model\CompanyCustomerFactory;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as CompanyCustomerCollectionFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;

/**
 * Save company action
 */
class Save extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Orangecat_Company::company_save';

    /**
     * @param Context $context
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyInterfaceFactory $companyFactory
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerInterfaceFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param DataPersistorInterface $dataPersistor
     * @param CompanyCustomerFactory $companyCustomerFactory
     * @param CompanyCustomerCollectionFactory $companyCustomerCollectionFactory
     * @param AccountManagementInterface $accountManagement
     * @param CustomerFactory $customerModelFactory
     * @param CustomerResource $customerResource
     */
    public function __construct(
        Context $context,
        private CompanyRepositoryInterface $companyRepository,
        private CompanyInterfaceFactory $companyFactory,
        private LoggerInterface $logger,
        private ResourceConnection $resourceConnection,
        private CustomerRepositoryInterface $customerRepository,
        private CustomerInterfaceFactory $customerFactory,
        private StoreManagerInterface $storeManager,
        private DataPersistorInterface $dataPersistor,
        private CompanyCustomerFactory $companyCustomerFactory,
        private CompanyCustomerCollectionFactory $companyCustomerCollectionFactory,
        private AccountManagementInterface $accountManagement,
        private CustomerFactory $customerModelFactory,
        private CustomerResource $customerResource
    ) {
        parent::__construct($context);
    }
    /**
     * Execute action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            // Extract data from fieldsets if available
            $generalData = $data['general'] ?? [];
            $addressData = $data['address'] ?? [];

            // Try to get ID from general fieldset first, then root
            $entityId = !empty($generalData['entity_id'])
                ? (int)$generalData['entity_id']
                : (!empty($data['entity_id']) ? (int)$data['entity_id'] : null);

            if ($entityId) {
                $company = $this->companyRepository->get($entityId);
            } else {
                $company = $this->companyFactory->create();
            }

            $company->setName($generalData['name'] ?? $data['name'] ?? null);
            $company->setEmail($generalData['email'] ?? $data['email'] ?? null);
            $company->setTaxId($generalData['tax_id'] ?? $data['tax_id'] ?? null);
            $company->setNameLegal($generalData['name_legal'] ?? $data['name_legal'] ?? null);
            $company->setStatus((int)($generalData['status'] ?? $data['status'] ?? 0));
            $company->setWebsiteId((int)($generalData['website_id'] ?? $data['website_id'] ?? 0));

            // Address Data
            $company->setAddress($addressData['address'] ?? $data['address'] ?? null);
            $company->setCity($addressData['city'] ?? $data['city'] ?? null);
            $company->setCountry($addressData['country'] ?? $data['country'] ?? null);
            $company->setRegion($addressData['region'] ?? $data['region'] ?? null);
            $company->setPostalcode($addressData['postalcode'] ?? $data['postalcode'] ?? null);
            $company->setTelephone($addressData['telephone'] ?? $data['telephone'] ?? null);

            $this->companyRepository->save($company);

            // Handle Company Admin
            $companyAdminId = null;
            $newCompanyAdmin = null;

            // Extract nested admin data
            $companyAdminData = $data['company_admin']['new_admin_fieldset'] ?? [];

            // Scenario A: New Admin (Create Customer)
            if (!empty($companyAdminData['admin_email'])) {
                // Validation: Company Email != Admin Email
                if (trim($company->getEmail()) === trim($companyAdminData['admin_email'])) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('The Company Email and the Company Administrator Email cannot be the same.')
                    );
                }

                // Pass Company Tax ID to the new customer, and approve if company is active
                $newCompanyAdmin = $this->createNewAdminCustomer(
                    $companyAdminData,
                    $company,
                    (int)$company->getStatus() === 1
                );
                $companyAdminId = (int)$newCompanyAdmin->getId();
            } elseif (!empty($data['admin_email'])) {
                // Fallback for flat structure or older usages
                $newCompanyAdmin = $this->createNewAdminCustomer($data, $company, (int)$company->getStatus() === 1);
                $companyAdminId = (int)$newCompanyAdmin->getId();
            } elseif (!empty($data['company_admin_id'])) {
                // Scenario B: Selected Existing Admin
                $companyAdminId = (int)$data['company_admin_id'];
            }

            if ($companyAdminId) {
                $this->saveCompanyAdmin((int)$company->getEntityId(), $companyAdminId);
            }

            $connection->commit();

            $this->dataPersistor->clear('company_company');

            $this->messageManager->addSuccessMessage(__('Company has been saved.'));

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['entity_id' => $company->getEntityId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $connection->rollBack();
            // Ensure entity_id is available at root for DataProvider
            if (isset($entityId)) {
                $data['entity_id'] = $entityId;
            }
            $this->dataPersistor->set('company_company', $data);
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $entityId ?? null]);
        } catch (\Exception $e) {
            $connection->rollBack();
            if (isset($entityId)) {
                $data['entity_id'] = $entityId;
            }
            $this->dataPersistor->set('company_company', $data);
            $this->logger->error($e->getMessage());
            $this->messageManager->addErrorMessage(__('Something went wrong while saving the company.'));
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $entityId ?? null]);
        }
    }

    /**
     * Create new customer to be admin
     *
     * @param array $data
     * @param \Orangecat\Company\Api\Data\CompanyInterface $company
     * @param bool $approveAccount
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createNewAdminCustomer(
        array $data,
        $company,
        bool $approveAccount = false
    ): \Magento\Customer\Api\Data\CustomerInterface {
        $email = $data['admin_email'];
        $websiteId = (int)$data['admin_website_id'];
        $taxId = $company->getTaxId();

        // Check if exists
        try {
            $existing = $this->customerRepository->get($email, $websiteId);
            if ($existing->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('A customer with email %1 already exists in this website.', $email)
                );
            }
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            // phpcs:ignore Squiz.PHP.EmptyCatchStatement
            /** Good, does not exist. Handled for PHPCS compliance. */
            $e = null;
        }

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->setEmail($email);
        $customer->setFirstname($data['admin_firstname']);
        $customer->setLastname($data['admin_lastname']);

        if ($taxId) {
            $customer->setTaxvat($taxId);
        }

        if ($approveAccount) {
            $customer->setCustomAttribute('approve_account', 1);
        } else {
            $customer->setCustomAttribute('approve_account', 0);
        }

        // Assign to default store of website
        $store = $this->storeManager->getWebsite($websiteId)->getDefaultStore();
        $customer->setStoreId($store->getId());

        // Use AccountManagement to create account (generates necessary tokens for password setup)
        return $this->accountManagement->createAccount($customer);
    }

    /**
     * Save company admin in mycompany_customer table using Models
     *
     * @param int $companyId
     * @param int $customerId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function saveCompanyAdmin(int $companyId, int $customerId): void
    {
        // Validation: Is this customer already an admin of ANOTHER company?
        $collection = $this->companyCustomerCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $collection->addFieldToFilter('role_id', RoleInterface::ADMIN_ROLE_ID);
        $collection->addFieldToFilter('company_id', ['neq' => $companyId]);

        $existingAdminLink = $collection->getFirstItem();
        if ($existingAdminLink->getId()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'This customer is already an Administrator of another company (ID: %1).',
                    $existingAdminLink->getCompanyId()
                )
            );
        }

        // 1. First, remove any existing admin for this company
        $adminCollection = $this->companyCustomerCollectionFactory->create();
        $adminCollection->addFieldToFilter('company_id', $companyId);
        $adminCollection->addFieldToFilter('role_id', RoleInterface::ADMIN_ROLE_ID);
        $adminCollection->walk('delete');

        // 2. Remove this customer from this company if they were just a User
        // to avoid duplicate link with different role
        // Note: Step 1 might have already deleted them if they were the admin, so we check again.
        $userCollection = $this->companyCustomerCollectionFactory->create();
        $userCollection->addFieldToFilter('company_id', $companyId);
        $userCollection->addFieldToFilter('customer_id', $customerId);
        $userCollection->walk('delete');

        // 3. Insert the new admin
        $newLink = $this->companyCustomerFactory->create();
        $newLink->setCompanyId($companyId);
        $newLink->setCustomerId($customerId);
        $newLink->setRoleId(RoleInterface::ADMIN_ROLE_ID);
        $newLink->save();
    }

    /**
     * Get existing company admin ID
     *
     * @param int $companyId
     * @return int|null
     */
    private function getExistingCompanyAdminId(int $companyId): ?int
    {
        $collection = $this->companyCustomerCollectionFactory->create();
        $collection->addFieldToFilter('company_id', $companyId);
        $collection->addFieldToFilter('role_id', RoleInterface::ADMIN_ROLE_ID);
        $item = $collection->getFirstItem();
        return $item->getId() ? (int)$item->getCustomerId() : null;
    }
}
