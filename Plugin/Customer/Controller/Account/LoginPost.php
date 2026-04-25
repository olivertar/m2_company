<?php
/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Plugin\Customer\Controller\Account;

use Magento\Customer\Controller\Account\LoginPost as LoginPostAction;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

/**
 * Login post controller plugin
 */
class LoginPost
{
    /**
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param \Orangecat\Company\Model\Config $config
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private Session $customerSession,
        private CustomerRepositoryInterface $customerRepository,
        private ManagerInterface $messageManager,
        private RedirectFactory $resultRedirectFactory,
        private \Orangecat\Company\Model\Config $config,
        private \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * Restrict login for unapproved customers and companies
     *
     * @param LoginPostAction $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundExecute(LoginPostAction $subject, \Closure $proceed)
    {
        if ($subject->getRequest()->isPost()) {
            $login = $subject->getRequest()->getPost('login');
            if (!empty($login['username']) && !empty($login['password'])) {
                try {
                    $customer = $this->customerRepository->get($login['username']);
                    $attribute = $customer->getCustomAttribute('approve_account');
                    $isApproved = $attribute ? (bool)$attribute->getValue() : false;

                    $shouldBlock = false;
                    // If attribute exists and is false -> Block (Explicitly disapproved)
                    if ($attribute !== null && !$isApproved) {
                        $shouldBlock = true;
                    } elseif ($attribute === null && $this->config->isCustomerApprovalRequired()) {
                        // If attribute missing AND approval required -> Block (New/Unapproved)
                        $shouldBlock = true;
                    }

                    if ($shouldBlock) {
                        $this->messageManager->addErrorMessage(
                            __('Your account is not enabled or your company has not yet been enabled.')
                        );
                        $this->customerSession->setUsername($login['username']);
                        $resultRedirect = $this->resultRedirectFactory->create();
                        $resultRedirect->setPath('customer/account/login');
                        return $resultRedirect;
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error in LoginPost plugin: ' . $e->getMessage(), ['exception' => $e]);
                    // Proceed to standard login flow which handles invalid credentials (or missing user) appropriately
                }
            }
        }

        return $proceed();
    }
}
