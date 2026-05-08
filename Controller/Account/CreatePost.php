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

namespace Orangecat\Company\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\Company\Api\Data\CompanyInterfaceFactory;
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Model\Company;
use Orangecat\Company\Model\CompanyCustomerFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Orangecat\Company\Model\Config as CompanyConfig;
use Magento\Framework\App\CacheInterface;

/**
 * Process company registration form
 */
class CreatePost implements HttpPostActionInterface
{

    /**
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param Validator $formKeyValidator
     * @param ManagerInterface $messageManager
     * @param CompanyInterfaceFactory $companyFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param CustomerInterfaceFactory $customerFactory
     * @param AccountManagementInterface $accountManagement
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DataPersistorInterface $dataPersistor
     * @param CustomerRepositoryInterface $customerRepository
     * @param CompanyCustomerFactory $companyCustomerFactory
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param CompanyConfig $config
     * @param CacheInterface $cache
     */
    public function __construct(
        private RequestInterface $request,
        private RedirectFactory $resultRedirectFactory,
        private Validator $formKeyValidator,
        private ManagerInterface $messageManager,
        private CompanyInterfaceFactory $companyFactory,
        private CompanyRepositoryInterface $companyRepository,
        private CustomerInterfaceFactory $customerFactory,
        private AccountManagementInterface $accountManagement,
        private StoreManagerInterface $storeManager,
        private LoggerInterface $logger,
        private SearchCriteriaBuilder $searchCriteriaBuilder,
        private DataPersistorInterface $dataPersistor,
        private CustomerRepositoryInterface $customerRepository,
        private CompanyCustomerFactory $companyCustomerFactory,
        private TransportBuilder $transportBuilder,
        private StateInterface $inlineTranslation,
        private CompanyConfig $config,
        private CacheInterface $cache
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please try again.'));
            return $resultRedirect->setPath('company/account/create');
        }

        // Rate limiting: max 3 attempts per hour per IP
        $clientIp = $this->request->getClientIp() ?? 'unknown';
        $cacheKey = 'company_reg_attempt_' . md5($clientIp);
        $attempts = (int)$this->cache->load($cacheKey);
        if ($attempts >= 3) {
            $this->messageManager->addErrorMessage(__('Too many requests. Please try again later.'));
            return $resultRedirect->setPath('company/account/create');
        }
        $this->cache->save($attempts + 1, $cacheKey, [], 3600);

        $data = $this->request->getPostValue();

        try {
            // Validation 1: Company Email must be different from Admin Email
            if (isset($data['company_email']) && isset($data['admin_email']) &&
                strtolower($data['company_email']) === strtolower($data['admin_email'])
            ) {
                $this->dataPersistor->set('company_account_create', $data);
                $this->messageManager->addErrorMessage(
                    __('Company Email cannot be the same as the Company Administrator Email.')
                );
                return $resultRedirect->setPath('company/account/create');
            }

            $websiteId = $this->storeManager->getWebsite()->getId();

            // Validate admin email is unique
            try {
                $this->customerRepository->get($data['admin_email'], $websiteId);
                $this->dataPersistor->set('company_account_create', $data);
                $this->messageManager->addErrorMessage(
                    __('The provided information cannot be used. Please check your details and try again.')
                );
                return $resultRedirect->setPath('company/account/create');
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Customer does not exist, proceed
                $this->logger->info('Customer check: Customer not found, proceeding with creation.');
            }

            // Validation 2: Company Email must be unique
            if (isset($data['company_email'])) {
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('email', $data['company_email'])
                    ->create();
                $companies = $this->companyRepository->getList($searchCriteria);
                if ($companies->getTotalCount() > 0) {
                    $this->dataPersistor->set('company_account_create', $data);
                    $this->messageManager->addErrorMessage(__('The provided information cannot be used. Please check your details and try again.'));
                    return $resultRedirect->setPath('company/account/create');
                }
            }

            // Validation 3: Tax ID must be unique
            if (!empty($data['tax_id'])) {
                // Clear previous filters
                $this->searchCriteriaBuilder->setFilterGroups([]);
                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('tax_id', $data['tax_id'])
                    ->create();
                $companies = $this->companyRepository->getList($searchCriteria);
                if ($companies->getTotalCount() > 0) {
                    $this->dataPersistor->set('company_account_create', $data);
                    $this->messageManager->addErrorMessage(__('The provided information cannot be used. Please check your details and try again.'));
                    return $resultRedirect->setPath('company/account/create');
                }
            }

            // Create company in Pending status
            $company = $this->companyFactory->create();
            $company->setName($data['company_name']);
            $company->setEmail($data['company_email']);
            $company->setTaxId($data['tax_id'] ?? null);
            $company->setNameLegal($data['name_legal'] ?? null);
            $company->setAddress($data['street'] ?? null);
            $company->setCity($data['city'] ?? null);
            $company->setCountry($data['country'] ?? null);
            $company->setRegion($data['region'] ?? null);
            $company->setPostalcode($data['postcode'] ?? null);
            $company->setTelephone($data['telephone'] ?? null);
            $company->setStatus(Company::STATUS_PENDING);
            $company->setData('website_id', $websiteId);

            $company = $this->companyRepository->save($company);

            // Create customer without password
            $customer = $this->customerFactory->create();
            $customer->setFirstname($data['firstname']);
            $customer->setLastname($data['lastname']);
            $customer->setEmail($data['admin_email']);
            $customer->setTaxvat($data['tax_id'] ?? null);
            $customer->setWebsiteId($websiteId);
            $customer->setStoreId($this->storeManager->getStore()->getId());

            // Create customer account without password
            $savedCustomer = $this->accountManagement->createAccount($customer);

            // Link customer to company as admin
            $this->linkCustomerToCompany((int)$company->getEntityId(), (int)$savedCustomer->getId());

            // Send notification email
            $this->sendNewCompanyEmail($company, $savedCustomer);

            $this->dataPersistor->clear('company_account_create');

            $this->messageManager->addSuccessMessage(
                __(
                    'Your company registration request has been submitted. ' .
                        'You will receive an email once it has been reviewed.'
                )
            );

            return $resultRedirect->setPath('company/account/success');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->dataPersistor->set('company_account_create', $data);
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('company/account/create');
        } catch (\Exception $e) {
            $this->dataPersistor->set('company_account_create', $data);
            $this->logger->error('Company registration error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(
                __('An error occurred while processing your request. Please try again.')
            );
            return $resultRedirect->setPath('company/account/create');
        }
    }

    /**
     * Send new company registration email
     *
     * @param \Orangecat\Company\Api\Data\CompanyInterface $company
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return void
     */
    private function sendNewCompanyEmail($company, $customer): void
    {
        if (!$this->config->isNotificationEnabled()) {
            return;
        }

        $recipientEmail = $this->config->getNotificationEmail();
        if (empty($recipientEmail)) {
            return;
        }

        $recipients = explode(',', $recipientEmail);
        $templateId = $this->config->getEmailTemplate();
        $sender = [
            'name' => 'Store Administrator',
            'email' => $this->storeManager->getStore()->getConfig('trans_email/ident_general/email')
        ];

        foreach ($recipients as $recipient) {
            try {
                $recipient = trim($recipient);
                if (empty($recipient)) {
                    continue;
                }

                $this->inlineTranslation->suspend();

                $customerDataObject = new \Magento\Framework\DataObject([
                    'firstname' => $customer->getFirstname(),
                    'lastname' => $customer->getLastname(),
                    'email' => $customer->getEmail()
                ]);

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
                            'customer' => $customerDataObject
                        ]
                    )
                    ->setFrom($sender)
                    ->addTo($recipient)
                    ->getTransport();

                $transport->sendMessage();
                $this->inlineTranslation->resume();
            } catch (\Exception $e) {
                $this->logger->error('Error sending company registration email: ' . $e->getMessage());
            }
        }
    }

    /**
     * Link customer to company
     *
     * @param int $companyId
     * @param int $customerId
     * @return void
     */
    private function linkCustomerToCompany(int $companyId, int $customerId): void
    {
        $link = $this->companyCustomerFactory->create();
        $link->setCompanyId($companyId);
        $link->setCustomerId($customerId);
        $link->setRoleId(RoleInterface::ADMIN_ROLE_ID);
        $link->save();
    }
}
