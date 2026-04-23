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

namespace Orangecat\Company\Block\Adminhtml\Company\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Registry;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as LinkCollectionFactory;
use Orangecat\Company\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;

class CustomerGrid extends Extended
{
    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $collectionFactory
     * @param Registry $coreRegistry
     * @param LinkCollectionFactory $linkCollectionFactory
     * @param RoleCollectionFactory $roleCollectionFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        protected CollectionFactory $collectionFactory,
        protected Registry $coreRegistry,
        protected LinkCollectionFactory $linkCollectionFactory,
        protected RoleCollectionFactory $roleCollectionFactory,
        protected \Magento\Store\Model\System\Store $systemStore,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('company_admin_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);

        // Initial Filter Logic removed from here to allow Reset Filter to show all
        // Logic moved to _prepareCollection with isAjax check
    }

    /**
     * Prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect(['firstname', 'lastname', 'email', 'website_id']);

        // Initial Load Logic (Non-Ajax): Show Assigned Admin or Empty
        // Interaction Logic (Ajax - Search/Reset): Show All (Standard behavior if no filters applied)
        if (!$this->getRequest()->getParam('isAjax')) {
            $adminId = $this->getCompanyAdminId();
            if ($adminId) {
                $collection->addFieldToFilter('entity_id', $adminId);
            } else {
                $collection->addFieldToFilter('entity_id', '-1');
            }
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return $this
     * @throws \Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_company_admin',
            [
                'type' => 'radio',
                'html_name' => 'company_admin_id',
                'align' => 'center',
                'index' => 'entity_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select',
                'values' => [$this->getCompanyAdminId()],
                'width' => '50px',
                'renderer' => \Orangecat\Company\Block\Adminhtml\Edit\Tab\Renderer\CompanyAdminRadio::class
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'firstname',
            [
                'header' => __('First Name'),
                'index' => 'firstname'
            ]
        );

        $this->addColumn(
            'lastname',
            [
                'header' => __('Last Name'),
                'index' => 'lastname'
            ]
        );

        $this->addColumn(
            'email',
            [
                'header' => __('Email'),
                'index' => 'email'
            ]
        );

        $this->addColumn(
            'website_id',
            [
                'header' => __('Website'),
                'index' => 'website_id',
                'type' => 'options',
                'options' => $this->systemStore->getWebsiteOptionHash(true)
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Get grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        // Pass entity_id to keep context
        $companyId = $this->getRequest()->getParam('entity_id');
        return $this->getUrl('mycompany/company/customerGrid', ['entity_id' => $companyId]);
    }

    /**
     * Find Admin Customer ID for Current Company
     *
     * @return int|null
     */
    protected function getCompanyAdminId()
    {
        // Try to get from Registry (Form loads loaded company)
        // Note: Registry key depends on how Edit Controller sets it. usually 'current_company' or via request param.
        // Let's rely on Request 'entity_id' as safe bet if registry is unsure, but DataProvider usually sets registry.

        $companyId = $this->getRequest()->getParam('entity_id');
        if (!$companyId) {
            return null;
        }

        // 1. Get Admin Role ID
        $roleCollection = $this->roleCollectionFactory->create();
        $roleCollection->addFieldToFilter('role_name', 'Company Admin');
        $role = $roleCollection->getFirstItem();

        if (!$role->getId()) {
            return null;
        }
        $adminRoleId = $role->getId();

        // 2. Find Link
        $linkCollection = $this->linkCollectionFactory->create();
        $linkCollection->addFieldToFilter('company_id', $companyId);
        $linkCollection->addFieldToFilter('role_id', $adminRoleId);
        $link = $linkCollection->getFirstItem();

        return $link->getId() ? (int)$link->getCustomerId() : null;
    }
}
