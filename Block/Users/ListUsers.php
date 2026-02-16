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

class ListUsers extends Template
{
    /**
     * @var \Orangecat\Company\Model\ResourceModel\CompanyCustomer\Collection
     */
    private $userCollection;

    /**
     * @var array
     */
    protected $roleOptions = [];

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CompanyManagement $companyManagement
     * @param CompanyCustomerCollectionFactory $companyCustomerCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param RoleCollectionFactory $roleCollectionFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\Data\Helper\PostHelper $postHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Session $customerSession,
        private CompanyManagement $companyManagement,
        private CompanyCustomerCollectionFactory $companyCustomerCollectionFactory,
        private CustomerRepositoryInterface $customerRepository,
        private RoleCollectionFactory $roleCollectionFactory,
        private \Magento\Eav\Model\Config $eavConfig,
        private \Magento\Framework\Data\Helper\PostHelper $postHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get users for current company
     *
     * @return \Orangecat\Company\Model\ResourceModel\CompanyCustomer\Collection
     */
    public function getUsers()
    {
        if (!$this->userCollection) {
            $companyId = $this->getCurrentCompanyId();
            if ($companyId) {
                // Get all links for this company
                $this->userCollection = $this->companyCustomerCollectionFactory->create();
                $this->userCollection->addFieldToFilter('company_id', $companyId);
                // Explicitly select link_id to ensure getId() returns it
                $this->userCollection->addFieldToSelect('link_id');
                $this->userCollection->addFieldToSelect('customer_id');
                $this->userCollection->addFieldToSelect('role_id');
                $this->userCollection->addFieldToSelect('company_id');

                // Join with customer table to get names and emails
                $this->userCollection->getSelect()->joinLeft(
                    ['cust' => $this->userCollection->getTable('customer_entity')],
                    'main_table.customer_id = cust.entity_id',
                    ['email', 'firstname', 'lastname']
                );

                // Join with EAV attribute for approve_account
                $attribute = $this->eavConfig->getAttribute(
                    \Magento\Customer\Model\Customer::ENTITY,
                    'approve_account'
                );
                $attributeId = $attribute->getAttributeId();

                // Join customer_entity_int (assuming approve_account is int)
                $this->userCollection->getSelect()->joinLeft(
                    ['cust_attr' => $this->userCollection->getTable('customer_entity_int')],
                    'main_table.customer_id = cust_attr.entity_id AND cust_attr.attribute_id = ' . $attributeId,
                    ['approve_account' => 'cust_attr.value']
                );

                // Join with role table to get role names
                $this->userCollection->getSelect()->joinLeft(
                    ['role' => $this->userCollection->getTable('mycompany_role')],
                    'main_table.role_id = role.role_id',
                    ['role_name']
                );
            }
        }
        return $this->userCollection;
    }

    /**
     * Get current logged in user's company ID
     *
     * @return int|null
     */
    public function getCurrentCompanyId()
    {
        $customerId = $this->customerSession->getCustomerId();
        return $this->companyManagement->getCompanyIdByCustomerId($customerId);
    }

    /**
     * Get Create User URL
     *
     * @return string
     */
    public function getCreateUrl()
    {
        return $this->getUrl('company/users/create');
    }

    /**
     * Get Edit User URL
     *
     * @param int $id Link ID
     * @return string
     */
    public function getEditUrl($id)
    {
        return $this->getUrl('company/users/edit', ['id' => $id]);
    }

    /**
     * Get Delete User URL
     *
     * @param int $id Link ID
     * @return string
     */
    public function getDeleteUrl($id)
    {
        return $this->getUrl('company/users/delete', ['id' => $id]);
    }

    /**
     * Get Post Data for Action
     *
     * @param string $url
     * @return string
     */
    public function getPostData($url)
    {
        return $this->postHelper->getPostData($url);
    }

    /**
     * Get Status Toggle URL
     *
     * @param int $id Link ID
     * @return string
     */
    public function getStatusUrl($id)
    {
        return $this->getUrl('company/users/status', ['id' => $id]);
    }

    /**
     * Get Role Name by ID
     * (Optional helper if join doesn't work as expected or for separate lookup)
     *
     * @param int $roleId
     * @return string
     */
    public function getRoleName($roleId)
    {
        if (empty($this->roleOptions)) {
            $collection = $this->roleCollectionFactory->create();
            foreach ($collection as $role) {
                $this->roleOptions[$role->getId()] = $role->getRoleName();
            }
        }
        return $this->roleOptions[$roleId] ?? 'Unknown';
    }
    /**
     * Get current logged in customer ID
     *
     * @return int|null
     */
    public function getCurrentCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }
}
