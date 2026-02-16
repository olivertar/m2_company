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

namespace Orangecat\Company\Ui\Component\Form\Fieldset;

use Magento\Ui\Component\Form\Fieldset;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\App\RequestInterface;
use Psr\Log\LoggerInterface;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as LinkCollectionFactory;
use Orangecat\Company\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Magento\Framework\View\Element\UiComponentInterface;

/**
 * Fieldset for Company Admin logic (Custom Tab)
 * Controls visibility of "New Admin" form vs "Customer Grid"
 */
class CompanyAdminFieldset extends Fieldset
{
    /**
     * @param ContextInterface $context
     * @param RequestInterface $request
     * @param LinkCollectionFactory $linkCollectionFactory
     * @param RoleCollectionFactory $roleCollectionFactory
     * @param LoggerInterface $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        protected RequestInterface $request,
        protected LinkCollectionFactory $linkCollectionFactory,
        protected RoleCollectionFactory $roleCollectionFactory,
        protected LoggerInterface $logger,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
    }

    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        $allParams = $this->request->getParams();
        $entityId = $this->request->getParam('entity_id') ?: $this->request->getParam('id');
        $hasAdmin = false;

        if ($entityId) {
            $hasAdmin = !empty($this->getCompanyAdminId((int)$entityId));
        }

        // Configure Children Visibility based on Admin Status

        // 1. New Admin Form Fieldset (Replaced container with fieldset)
        // Default: Visible if NO admin
        // We use getChildComponent since components array might not be populated via key access directly by name
        $newAdminFieldset = $this->getChildComponent('new_admin_fieldset');
        if ($newAdminFieldset) {
            $config = $newAdminFieldset->getData('config') ?: [];
            $config['visible'] = !$hasAdmin;
            $newAdminFieldset->setData('config', $config);
        }

        // 2. Admin Grid (InsertListing)
        // Default: Visible if HAS admin
        $gridContainer = $this->getChildComponent('company_customer_listing');
        if ($gridContainer) {
            $config = $gridContainer->getData('config') ?: [];
            $config['visible'] = $hasAdmin;
            $gridContainer->setData('config', $config);
        }

        // Add 'component' property to 'company_admin' to ensure it renders correctly if missing
        // This is the class itself, so parent handles this.

        parent::prepare();
    }

    /**
     * Get direct child component by name from components array
     *
     * @param string $name
     * @return UiComponentInterface|null
     */
    public function getChildComponent($name)
    {
        // Components array keys are usually names if structured by factory,
        // but let's iterate to be safe or check if key exists.
        if (isset($this->components[$name])) {
            return $this->components[$name];
        }

        foreach ($this->components as $component) {
            if ($component->getName() === $name) {
                return $component;
            }
        }
        return null;
    }

    /**
     * Find Admin Customer ID for Company
     *
     * @param int $companyId
     * @return int|null
     */
    protected function getCompanyAdminId($companyId)
    {
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
