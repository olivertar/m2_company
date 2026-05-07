<?php
/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Controller\Users;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Customer\Model\Session;
use Orangecat\Company\Model\CompanyManagement;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Message\ManagerInterface;
use Orangecat\Company\Model\CompanyRepository;
use Magento\Framework\Math\Random;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\FormKey\Validator;

class Save implements HttpPostActionInterface
{
    /**
     * @param Session $customerSession
     * @param CompanyManagement $companyManagement
     * @param ResultFactory $resultFactory
     * @param \Magento\Framework\App\Request\Http $request
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory
     * @param AccountManagementInterface $accountManagement
     * @param ManagerInterface $messageManager
     * @param CompanyRepository $companyRepository
     * @param Random $random
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param Validator $formKeyValidator
     */
    public function __construct(
        private Session $customerSession,
        private CompanyManagement $companyManagement,
        private ResultFactory $resultFactory,
        private \Magento\Framework\App\Request\Http $request,
        private CustomerRepositoryInterface $customerRepository,
        private \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory,
        private AccountManagementInterface $accountManagement,
        private ManagerInterface $messageManager,
        private CompanyRepository $companyRepository,
        private Random $random,
        private TransportBuilder $transportBuilder,
        private StateInterface $inlineTranslation,
        private StoreManagerInterface $storeManager,
        private ScopeConfigInterface $scopeConfig,
        private \Magento\Framework\UrlInterface $urlBuilder,
        private \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        private Validator $formKeyValidator
    ) {
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */

        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please try again.'));
            return $resultRedirect->setPath('*/*/index');
        }

        $currentCustomerId = $this->customerSession->getCustomerId();

        if (!$this->companyManagement->isCompanyAdmin($currentCustomerId)) {
            return $resultRedirect->setPath('customer/account');
        }

        $currentCompanyId = $this->companyManagement->getCompanyIdByCustomerId($currentCustomerId);

        $data = $this->request->getPostValue();
        if (!$data) {
            return $resultRedirect->setPath('*/*/create');
        }

        try {
            $linkId = isset($data['link_id']) ? (int)$data['link_id'] : null;
            $email = $data['email'];
            $firstname = $data['firstname'];
            $lastname = $data['lastname'];
            $roleId = (int)$data['role_id'];
            $status = isset($data['status']) ? (int)$data['status'] : 1;

            // Role 1 check
            if ($roleId == 1) {
                $this->messageManager->addErrorMessage(__('You cannot assign the Company Admin role.'));
                return $resultRedirect->setPath('*/*/index');
            }

            if ($linkId) {
                // EDIT EXISTING

                // Load by email to find the customer.
                // Ideally we should load by link_id to be safer, but current architecture relies on email uniqueness.
                $customer = $this->customerRepository->get($email);

                // Security Check: Ensure admin can manage this user
                try {
                    $this->companyManagement->validateManageUser($currentCustomerId, (int)$customer->getId());
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addErrorMessage($e->getMessage());
                    return $resultRedirect->setPath('*/*/index');
                }

                if ($customer->getId() == $currentCustomerId) {
                    $this->messageManager->addErrorMessage(__('You cannot edit your own account permissions.'));
                    return $resultRedirect->setPath('*/*/index');
                }

                $customer->setFirstname($firstname);
                $customer->setLastname($lastname);

                $this->customerRepository->save($customer);

                // Update Status (approve_account)
                if ($status !== null) {
                    $customerToUpdate = $this->customerRepository->getById($customer->getId());
                    $customerToUpdate->setCustomAttribute('approve_account', $status);
                    $this->customerRepository->save($customerToUpdate);
                }

                // Update Company Link
                $linkData = [
                    'max_purchase_amount' => isset($data['max_purchase_amount']) ? $data['max_purchase_amount'] : null,
                    'max_period_amount' => isset($data['max_period_amount']) ? $data['max_period_amount'] : null
                ];
                $this->companyManagement->assignCustomer($currentCompanyId, $customer->getId(), $roleId, $linkData);

                $this->messageManager->addSuccessMessage(__('The user has been updated.'));
            } else {
                // CREATE NEW

                try {
                    $this->customerRepository->get($email);

                    $this->messageManager->addErrorMessage(
                        __('A customer with this email address already exists. Please use a unique email.')
                    );
                    return $resultRedirect->setPath('*/*/create');
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    // Create new customer with hashed password via repository
                    $password = $this->random->getRandomString(10);
                    $customer = $this->customerFactory->create();
                    $customer->setFirstname($firstname);
                    $customer->setLastname($lastname);
                    $customer->setEmail($email);
                    $customer->setCustomAttribute('approve_account', $status);
                    $passwordHash = $this->encryptor->getHash($password, true);
                    $customer = $this->customerRepository->save($customer, $passwordHash);
                }

                $linkData = [
                    'max_purchase_amount' => isset($data['max_purchase_amount']) ? $data['max_purchase_amount'] : null,
                    'max_period_amount' => isset($data['max_period_amount']) ? $data['max_period_amount'] : null
                ];
                $this->companyManagement->assignCustomer($currentCompanyId, $customer->getId(), $roleId, $linkData);

                // Send Custom Welcome Email
                $this->sendWelcomeEmail($customer, $currentCompanyId);

                $this->messageManager->addSuccessMessage(__('The user has been created and notified.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error saving user: %1', $e->getMessage()));
            return $resultRedirect->setPath('*/*/create');
        }

        return $resultRedirect->setPath('company/users/index');
    }

    /**
     * Send welcome email
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @param int $companyId
     * @return void
     */
    private function sendWelcomeEmail($customer, $companyId): void
    {
        try {
            // Manually generate token to avoid triggering standard email or rate limiter
            $newPasswordToken = $this->random->getUniqueHash();

            // Save reset token via repository to ensure plugins and events fire
            $customerToUpdate = $this->customerRepository->getById($customer->getId());
            $customerToUpdate->setCustomAttribute('rp_token', $newPasswordToken);
            $customerToUpdate->setCustomAttribute(
                'rp_token_created_at',
                (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT)
            );
            $this->customerRepository->save($customerToUpdate);

            // Now Send Email
            $company = $this->companyRepository->get($companyId);

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('company_user_welcome')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId()
                ])
                ->setTemplateVars([
                    'customer' => $customer,
                    'customer_firstname' => $customer->getFirstname(),
                    'customer_lastname' => $customer->getLastname(),
                    'company_name' => $company->getName(),
                    'store' => $this->storeManager->getStore(),
                    'id' => $customer->getId(),
                    'token' => $newPasswordToken,
                    'reset_password_link' => $this->urlBuilder->getUrl(
                        'customer/account/createPassword',
                        ['_query' => ['id' => $customer->getId(), 'token' => $newPasswordToken]]
                    )
                ])
                ->setFromByScope('general')
                ->addTo($customer->getEmail(), $customer->getFirstname() . ' ' . $customer->getLastname())
                ->getTransport();

            $transport->sendMessage();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error sending email: %1', $e->getMessage()));
        }
    }
}
