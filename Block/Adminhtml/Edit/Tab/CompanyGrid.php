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

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Orangecat\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Framework\Registry;
use Magento\Customer\Controller\RegistryConstants;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as LinkCollectionFactory;

class CompanyGrid extends Extended
{
    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $collectionFactory
     * @param LinkCollectionFactory $linkCollectionFactory
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        protected CollectionFactory $collectionFactory,
        protected LinkCollectionFactory $linkCollectionFactory,
        protected Registry $coreRegistry,
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
        $this->setId('company_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        $this->setSaveParametersInSession(true);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareLayout()
    {
        /** @var \Magento\Backend\Block\Widget\Button $buttonBlock */
        $buttonBlock = $this->getLayout()->createBlock(
            \Magento\Backend\Block\Widget\Button::class
        );
        $buttonBlock->setData(
            [
                'label' => __('Unassign from Company'),
                'onclick' => '
                    var radios = document.getElementsByName("customer[company_id]");
                    for(var i=0; i<radios.length; i++) { radios[i].checked = false; }
                    return false;
                ',
                'class' => 'task'
            ]
        );

        $this->setChild('unassign_button', $buttonBlock);

        return parent::_prepareLayout();
    }

    /**
     * Get main buttons html
     *
     * @return string
     */
    public function getMainButtonsHtml()
    {
        $html = parent::getMainButtonsHtml();
        $html .= $this->getChildHtml('unassign_button');
        return $html;
    }

    /**
     * Get grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        // Explicitly pass customer ID to generate correct Secret Key for this route
        $customerId = $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        return $this->getUrl('mycompany/customer/companyGrid', ['id' => $customerId]);
    }

    /**
     * Prepare collection
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->collectionFactory->create();

        // Check for Initial Load Flag
        if ($this->getRequest()->getParam('initial')) {
            $assignedId = $this->getAssignedCompanyId();
            if ($assignedId) {
                // Show only assigned company
                $collection->addFieldToFilter('entity_id', $assignedId);
            } else {
                // Show empty grid
                $collection->addFieldToFilter('entity_id', '-1');
            }
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Get currently assigned company ID
     *
     * @return int|null
     */
    protected function getAssignedCompanyId()
    {
        $customerId = $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);

        if (!$customerId) {
            return null;
        }

        $collection = $this->linkCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);
        $link = $collection->getFirstItem();

        return $link->getId() ? (int)$link->getCompanyId() : null;
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
            'in_company',
            [
                'type' => 'radio',
                'html_name' => 'customer[company_id]',
                'align' => 'center',
                'index' => 'entity_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select',
                'values' => [$this->getAssignedCompanyId()], // Pre-select checked value
                'width' => '50px',
                'renderer' => \Orangecat\Company\Block\Adminhtml\Edit\Tab\Renderer\CompanyRadio::class
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
            'name',
            [
                'header' => __('Company Name'),
                'index' => 'name'
            ]
        );

        $this->addColumn(
            'email',
            [
                'header' => __('Email'),
                'index' => 'email'
            ]
        );

        return parent::_prepareColumns();
    }
}
