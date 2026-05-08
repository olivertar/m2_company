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

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\Company\Api\Data\CompanyInterface;
use Orangecat\Company\Api\Data\CompanySearchResultsInterfaceFactory;
use Orangecat\Company\Model\ResourceModel\Company as CompanyResource;
use Orangecat\Company\Model\ResourceModel\Company\CollectionFactory as CompanyCollectionFactory;

use Orangecat\Company\Model\CompanyFactory;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as CompanyCustomerCollectionFactory;
use Orangecat\Company\Api\Data\RoleInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Orangecat\Company\Model\Config;
use Orangecat\Company\Model\Company;

class CompanyRepository implements CompanyRepositoryInterface
{
    /**
     * @param CompanyResource $resource
     * @param CompanyFactory $companyFactory
     * @param CompanyCollectionFactory $collectionFactory
     * @param CompanySearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param CompanyCustomerCollectionFactory $companyCustomerCollectionFactory
     * @param CustomerFactory $customerFactory
     * @param CustomerResource $customerResource
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Psr\Log\LoggerInterface $logger
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     */
    public function __construct(
        private CompanyResource $resource,
        private CompanyFactory $companyFactory,
        private CompanyCollectionFactory $collectionFactory,
        private CompanySearchResultsInterfaceFactory $searchResultsFactory,
        private CollectionProcessorInterface $collectionProcessor,
        private CompanyCustomerCollectionFactory $companyCustomerCollectionFactory,
        private CustomerFactory $customerFactory,
        private CustomerResource $customerResource,
        private CustomerRepositoryInterface $customerRepository,
        private \Psr\Log\LoggerInterface $logger,
        private TransportBuilder $transportBuilder,
        private StateInterface $inlineTranslation,
        private StoreManagerInterface $storeManager,
        private Config $config
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(CompanyInterface $company)
    {
        try {
            /** @var \Magento\Framework\Model\AbstractModel $company */
            $this->validateCompany($company);

            $isStatusChanged = false;
            $oldStatus = null;

            if ($company->getId()) {
                $originalCompany = $this->get($company->getId());
                $oldStatus = (int)$originalCompany->getStatus();
                $newStatus = (int)$company->getStatus();
                if ($oldStatus !== $newStatus) {
                    $isStatusChanged = true;
                }
            }

            $this->resource->save($company);
            $this->syncCompanyAdminStatus($company);

            if ($isStatusChanged) {
                $this->sendStatusChangeEmail($company);
            }
        } catch (LocalizedException $e) {
            throw $e;
        } catch (\Exception $exception) {
            $this->logger->error('Could not save company: ' . $exception->getMessage(), ['exception' => $exception]);
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $this->get($company->getId());
    }

    /**
     * Validate Company Data
     *
     * @param CompanyInterface $company
     * @throws LocalizedException
     */
    private function validateCompany(CompanyInterface $company): void
    {
        // Validation: Unique Company Email
        if ($company->getEmail()) {
            $emailCollection = $this->collectionFactory->create()
                ->addFieldToFilter('email', $company->getEmail());

            if ($company->getId()) {
                $emailCollection->addFieldToFilter('entity_id', ['neq' => $company->getId()]);
            }

            if ($emailCollection->getSize() > 0) {
                throw new InputException(
                    __('A company with this email address already exists.')
                );
            }

            // Validation: Email must not belong to any Customer
            try {
                $this->customerRepository->get($company->getEmail());
                // If get() succeeds, a customer exists.
                throw new InputException(
                    __('A customer with this email address already exists. Company email must be unique.')
                );
            } catch (NoSuchEntityException $e) {
                // Good, no customer found.
                $this->logger->info('Customer email check passed: No existing customer found.');
            }
        }

        // Validation: Unique Tax ID
        if ($company->getTaxId()) {
            $taxCollection = $this->collectionFactory->create()
                ->addFieldToFilter('tax_id', $company->getTaxId());

            if ($company->getId()) {
                $taxCollection->addFieldToFilter('entity_id', ['neq' => $company->getId()]);
            }

            if ($taxCollection->getSize() > 0) {
                throw new InputException(
                    __('A company with this Tax/VAT ID already exists.')
                );
            }
        }
    }

    /**
     * Sync Company Status to Admin Customer Approval
     *
     * @param CompanyInterface $company
     */
    private function syncCompanyAdminStatus(CompanyInterface $company): void
    {
        $companyId = (int)$company->getId();
        if (!$companyId) {
            return;
        }

        // Find Company Admin
        $collection = $this->companyCustomerCollectionFactory->create();
        $collection->addFieldToFilter('company_id', $companyId);
        $collection->addFieldToFilter('role_id', RoleInterface::ADMIN_ROLE_ID);
        $item = $collection->getFirstItem();

        if (!$item->getId()) {
            return;
        }

        $customerId = (int)$item->getCustomerId();
        $approveValue = ((int)$company->getStatus() === 1) ? 1 : 0;

        try {
            $customer = $this->customerFactory->create();
            $this->customerResource->load($customer, $customerId);

            if ($customer->getId()) {
                $currentValue = $customer->getData('approve_account');
                if ($currentValue === null || (int)$currentValue !== $approveValue) {
                    $customer->setData('approve_account', $approveValue);
                    $this->customerResource->saveAttribute($customer, 'approve_account');
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error syncing company status to admin user: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function get($companyId)
    {
        $company = $this->companyFactory->create();
        $this->resource->load($company, $companyId);
        if (!$company->getId()) {
            throw new NoSuchEntityException(__('Company with id "%1" does not exist.', $companyId));
        }
        return $company;
    }

    /**
     * @inheritdoc
     */
    public function delete(CompanyInterface $company)
    {
        try {
            /** @var \Magento\Framework\Model\AbstractModel $company */
            $this->resource->delete($company);
        } catch (\Exception $exception) {
            $this->logger->error('Could not delete company: ' . $exception->getMessage(), ['exception' => $exception]);
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Send company status change email
     *
     * @param CompanyInterface $company
     * @return void
     */
    private function sendStatusChangeEmail(CompanyInterface $company): void
    {
        if (!$this->config->isNotificationEnabled()) {
            return;
        }

        $recipientEmail = $company->getEmail();
        $recipientEmail = str_replace(["\r", "\n", "%0a", "%0d", "%0A", "%0D"], '', $recipientEmail);
        if (empty($recipientEmail) || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $templateId = $this->config->getCompanyStatusChangeEmailTemplate();
        if (empty($templateId)) {
            $templateId = 'company_status_change'; // Fallback to ID defined in email_templates.xml
        }

        $sender = [
            'name' => 'Store Administrator',
            'email' => $this->storeManager->getStore()->getConfig('trans_email/ident_general/email')
        ];

        try {
            $this->inlineTranslation->suspend();

            $statusLabel = $this->getStatusLabel((int)$company->getStatus());

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId(),
                    ]
                )
                ->setTemplateVars(
                    [
                        'company' => $company,
                        'status_label' => $statusLabel,
                        'store_email' => $this->storeManager->getStore()->getConfig('trans_email/ident_support/email')
                    ]
                )
                ->setFrom($sender)
                ->addTo($recipientEmail)
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->error('Error sending company status email: ' . $e->getMessage());
        }
    }

    /**
     * Get status label
     *
     * @param int $status
     * @return string
     */
    private function getStatusLabel(int $status): string
    {
        return match ($status) {
            Company::STATUS_APPROVED => __('Approved'),
            Company::STATUS_SUSPENDED => __('Suspended'),
            Company::STATUS_REJECTED => __('Rejected'),
            default => __('Pending'),
        };
    }

    /**
     * @inheritdoc
     */
    public function deleteById($companyId)
    {
        return $this->delete($this->get($companyId));
    }

    /**
     * @inheritdoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }
}
