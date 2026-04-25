<?php
/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Orangecat\Company\Model\Config;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;

class AutoApproveCustomer implements ObserverInterface
{
    /**
     * @param Config $config
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerSession $customerSession
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private Config $config,
        private CustomerRepositoryInterface $customerRepository,
        private CustomerSession $customerSession,
        private \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * Auto approve customer if applicable
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->config->isFrontendCustomerRegistrationAllowed()) {
            return;
        }

        $isApproved = !$this->config->isCustomerApprovalRequired();
        $customer = $observer->getEvent()->getCustomer();

        try {
            // Save attribute: 1 (True) if auto-approve, 0 (False) if approval required
            $customer->setCustomAttribute('approve_account', $isApproved ? 1 : 0);
            $this->customerRepository->save($customer);

            // If not approved, force logout to prevent auto-login after registration
            if (!$isApproved && $this->customerSession->isLoggedIn()) {
                $this->customerSession->logout();
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error auto-approving customer: ' . $e->getMessage(),
                ['exception' => $e]
            );
        }
    }
}
