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

class Edit implements HttpGetActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     * @param Session $customerSession
     * @param CompanyManagement $companyManagement
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory $collectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private PageFactory $resultPageFactory,
        private Session $customerSession,
        private CompanyManagement $companyManagement,
        private ResultFactory $resultFactory,
        private RequestInterface $request,
        private \Magento\Framework\Message\ManagerInterface $messageManager,
        private \Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory $collectionFactory,
        private \Psr\Log\LoggerInterface $logger
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

        $currentCustomerId = $this->customerSession->getCustomerId();

        if (!$this->companyManagement->isCompanyAdmin($currentCustomerId)) {
            return $resultRedirect->setPath('customer/account');
        }

        $linkId = $this->request->getParam('id');
        if ($linkId) {
            // Security Check
            try {
                /** @var \Orangecat\Company\Model\ResourceModel\CompanyCustomer\Collection $collection */
                $collection = $this->collectionFactory->create();
                $collection->addFieldToFilter('link_id', $linkId);
                $link = $collection->getFirstItem();

                if ($link && $link->getId()) {
                    $targetCustomerId = $link->getCustomerId();
                    try {
                        $this->companyManagement->validateManageUser($currentCustomerId, (int)$targetCustomerId);
                    } catch (\Magento\Framework\Exception\LocalizedException $e) {
                        $this->messageManager->addErrorMessage($e->getMessage());
                        return $resultRedirect->setPath('*/*/index');
                    }
                } else {
                    $this->messageManager->addErrorMessage(__('User not found.'));
                    return $resultRedirect->setPath('*/*/index');
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('An error occurred while loading the user.'));
                return $resultRedirect->setPath('*/*/index');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Edit Company User'));

        return $resultPage;
    }
}
