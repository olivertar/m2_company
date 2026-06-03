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

use Magento\Customer\Model\Session;
use Orangecat\Company\Model\CompanyManagement;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;

class Delete implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /**
     * @param Session $customerSession
     * @param CompanyManagement $companyManagement
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param \Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param FormKeyValidator $formKeyValidator
     */
    public function __construct(
        private Session $customerSession,
        private CompanyManagement $companyManagement,
        private ResultFactory $resultFactory,
        private RequestInterface $request,
        private ManagerInterface $messageManager,
        private \Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory $collectionFactory,
        private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private \Magento\Customer\Model\CustomerFactory $customerFactory,
        private FormKeyValidator $formKeyValidator
    ) {
    }

    /**
     * Delete user
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$this->request->isPost()) {
            return $resultRedirect->setPath('*/*/index');
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please try again.'));
            return $resultRedirect->setPath('*/*/index');
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        $currentCustomerId = $this->customerSession->getCustomerId();

        try {
            // Initial check if admin
            if (!$this->companyManagement->isCompanyAdmin($currentCustomerId)) {
                return $resultRedirect->setPath('customer/account');
            }

            $linkId = (int)$this->request->getParam('id');

            if ($linkId) {
                // Find Link
                $collection = $this->collectionFactory->create();
                $collection->addFieldToFilter('link_id', $linkId);
                $link = $collection->getFirstItem();

                if ($link && $link->getId()) {
                    $targetCustomerId = $link->getCustomerId();

                    // Validate permissions
                    $this->companyManagement->validateManageUser($currentCustomerId, (int)$targetCustomerId);

                    // Prevent self-delete (specific to Delete action)
                    if ($targetCustomerId == $currentCustomerId) {
                        $this->messageManager->addErrorMessage(__('You cannot delete yourself.'));
                        return $resultRedirect->setPath('*/*/index');
                    }

                    // Remove from Company
                    $this->companyManagement->removeCustomer($targetCustomerId);

                    try {
                        // Delegate deletion to Service
                        $this->companyManagement->deleteCustomerById($targetCustomerId);
                        $this->messageManager->addSuccessMessage(
                            __('The user and customer account have been deleted.')
                        );
                    } catch (\Exception $e) {
                        $this->messageManager->addWarningMessage(
                            __('User removed from company, but could not delete customer account: %1', $e->getMessage())
                        );

                        // Try Disable
                        $customerModel = $this->customerFactory->create()->load($targetCustomerId);
                        if ($customerModel->getId()) {
                            $customerModel->setData('approve_account', 0); // Disable if cannot delete
                            $customerModel->save();
                        }
                    }
                } else {
                    $this->messageManager->addErrorMessage(__('User not found.'));
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/index');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error deleting user: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
