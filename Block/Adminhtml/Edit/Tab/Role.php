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

namespace Orangecat\Company\Block\Adminhtml\Edit\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Customer\Controller\RegistryConstants;
use Orangecat\Company\Model\Config\Source\CompanyRole;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as LinkCollectionFactory;

class Role extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Orangecat_Company::role_selector.phtml';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CompanyRole $companyRoleSource
     * @param LinkCollectionFactory $linkCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Registry $registry,
        private CompanyRole $companyRoleSource,
        private LinkCollectionFactory $linkCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->companyRoleSource->toOptionArray();
    }

    /**
     * Get current role ID
     *
     * @return int|null
     */
    public function getCurrentRoleId(): ?int
    {
        // Try getting from Registry (set by Controller or default)
        $customerId = $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);

        if (!$customerId) {
            // Try Request param if registry failed (though controller sets it)
            $customerId = $this->getRequest()->getParam('id');
        }

        if (!$customerId) {
            return null;
        }

        $collection = $this->linkCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', (int)$customerId);
        /** @var \Orangecat\Company\Model\CompanyCustomer $link */
        $link = $collection->getFirstItem();

        return $link->getId() ? (int)$link->getRoleId() : null;
    }

    /**
     * Get field name
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return 'customer[role_id]';
    }

    /**
     * Get field ID
     *
     * @return string
     */
    public function getFieldId(): string
    {
        return 'company_role_id';
    }
}
