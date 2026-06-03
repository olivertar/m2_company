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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;

class Status implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    /**
     * @param Session $customerSession
     * @param CompanyManagement $companyManagement
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param \Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory $collectionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
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
        private FormKeyValidator $formKeyValidator
    ) {
    }

    /**
     * Update user status
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
            // Check if admin
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

                    // Validate
                    $this->companyManagement->validateManageUser($currentCustomerId, (int)$targetCustomerId);

                    // Prevent self-status change
                    if ($targetCustomerId == $currentCustomerId) {
                        $this->messageManager->addErrorMessage(__('You cannot change your own status.'));
                        return $resultRedirect->setPath('*/*/index');
                    }

                    // Toggle Status (approve_account)
                    try {
                        $customer = $this->customerRepository->getById($targetCustomerId);
                        $attribute = $customer->getCustomAttribute('approve_account');
                        $currentStatus = $attribute ? (int)$attribute->getValue() : 0;
                        $newStatus = $currentStatus === 1 ? 0 : 1;

                        $customer->setCustomAttribute('approve_account', $newStatus);
                        $this->customerRepository->save($customer);

                        $statusLabel = $newStatus === 1 ? __('enabled') : __('disabled');
                        $this->messageManager->addSuccessMessage(__('The user has been %1.', $statusLabel));
                    } catch (NoSuchEntityException $e) {
                        $this->messageManager->addErrorMessage(__('User not found.'));
                    } catch (\Exception $e) {
                        $this->messageManager->addErrorMessage(__('Error updating status: %1', $e->getMessage()));
                    }
                } else {
                    $this->messageManager->addErrorMessage(__('User not found.'));
                }
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error updating user status: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
