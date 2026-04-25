<?php
/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Block\Users;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session;
use Orangecat\Company\Model\CompanyManagement;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as CompanyCustomerCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Orangecat\Company\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;

class Edit extends Template
{
    /**
     * @var \Orangecat\Company\Model\CompanyCustomer
     */
    private $currentUserLink;

    /**
     * @var \Magento\Customer\Api\Data\CustomerInterface
     */
    private $currentUser;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CompanyManagement $companyManagement
     * @param CompanyCustomerCollectionFactory $companyCustomerCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param RoleCollectionFactory $roleCollectionFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerModelFactory
     * @param \Magento\Framework\Data\Helper\PostHelper $postHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Session $customerSession,
        private CompanyManagement $companyManagement,
        private CompanyCustomerCollectionFactory $companyCustomerCollectionFactory,
        private CustomerRepositoryInterface $customerRepository,
        private RoleCollectionFactory $roleCollectionFactory,
        private \Magento\Customer\Model\CustomerFactory $customerModelFactory,
        private \Magento\Framework\Data\Helper\PostHelper $postHelper,
        private \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get the user being edited (if any)
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getUserStatus()
    {
        if ($this->getUser()) {
            $customerModel = $this->customerModelFactory->create()->load($this->getUser()->getId());
            return $customerModel->getData('approve_account'); // 0 or 1
        }
        return 1;
    }

    /**
     * Get User
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getUser()
    {
        if ($this->currentUser === null) {
            $id = $this->getRequest()->getParam('id'); // This is the link ID or customer ID passed in URL
            // Assuming passing Link ID for safety/uniqueness in context of company links
            // But usually Customer ID is better universally.
            // Let's assume the ID param is Link ID based on `users/list.phtml` logic previously used ($user->getId()).

            if ($id) {
                try {
                    $collection = $this->companyCustomerCollectionFactory->create();
                    $collection->addFieldToFilter('link_id', $id);
                    $link = $collection->getFirstItem();

                    if ($link && $link->getId()) {
                        // Verify this link belongs to current admin's company
                        $currentAdminCompanyId = $this->companyManagement->getCompanyIdByCustomerId(
                            $this->customerSession->getCustomerId()
                        );

                        if ((int)$link->getCompanyId() === (int)$currentAdminCompanyId) {
                            $this->currentUserLink = $link;
                            $this->currentUser = $this->customerRepository->getById($link->getCustomerId());
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error expecting user load: ' . $e->getMessage());
                }
            }
        }
        return $this->currentUser;
    }

    /**
     * Get User Link
     *
     * @return \Orangecat\Company\Model\CompanyCustomer|null
     */
    public function getUserLink()
    {
        if ($this->currentUserLink === null) {
            $this->getUser(); // trigger load
        }
        return $this->currentUserLink;
    }

    /**
     * Get available roles
     *
     * @return array
     */
    public function getRoles()
    {
        $collection = $this->roleCollectionFactory->create();
        $collection->addFieldToFilter('role_id', ['neq' => 1]);
        return $collection->getItems();
    }

    /**
     * Get Company Buyer Role ID
     *
     * @return int|null
     */
    public function getCompanyBuyerRoleId()
    {
        $collection = $this->roleCollectionFactory->create();
        $collection->addFieldToFilter('role_name', 'Company Buyer');
        $collection->setPageSize(1);
        $role = $collection->getFirstItem();
        return $role->getId() ? (int)$role->getId() : null;
    }

    /**
     * Get Save Action URL
     *
     * @return string
     */
    public function getSaveUrl()
    {
        return $this->getUrl('company/users/save');
    }

    /**
     * Get Back URL
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('company/users/index');
    }
    /**
     * Get Delete Action URL
     *
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('company/users/delete', ['id' => $this->getRequest()->getParam('id')]);
    }

    /**
     * Get Delete Post Data
     *
     * @return string
     */
    public function getDeletePostData()
    {
        return $this->postHelper->getPostData($this->getDeleteUrl());
    }
}
