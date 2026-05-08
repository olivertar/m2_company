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

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;
use Orangecat\Company\Model\CompanyManagement;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;

class Index implements HttpGetActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     * @param Session $customerSession
     * @param CompanyManagement $companyManagement
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        private PageFactory $resultPageFactory,
        private Session $customerSession,
        private CompanyManagement $companyManagement,
        private ResultFactory $resultFactory
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

        $customerId = $this->customerSession->getCustomerId();
        $roleId = $this->companyManagement->getRoleIdByCustomerId($customerId);

        // Check if user is Company Admin (Role ID 1)
        if ($roleId != 1) {
            return $resultRedirect->setPath('customer/account');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Company Users'));

        return $resultPage;
    }
}
