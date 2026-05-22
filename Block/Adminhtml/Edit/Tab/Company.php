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

use Magento\Backend\Block\Template\Context;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Ui\Component\Layout\Tabs\TabWrapper;

/**
 * Company Company Assignment Tab
 */
class Company extends TabWrapper
{
    /**
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @var bool
     */
    protected $isAjaxLoaded = true;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(Context $context, Registry $registry, array $data = [])
    {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        // Allow even for new customers?
        // Assignments happen via ID linking. If new customer has no ID, we can't save link easily with
        // Observer unless we process it after save.
        // But observer runs after save, so customer has ID then.
        // However, standard Magento tabs like Reviews usually require existing customer.
        // Let's mimic ReviewTab: return $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        // User wants to assign company. If it's a new customer, maybe they want to assign immediately.
        // If I return true, Ajax call happens. Controller needs customer ID?
        // If no customer ID, grid can still load (listing all companies).
        // Radio selection sends `customer[company_id]`. Observer handles updated logic.
        // So I will return true always or check specific permission.
        return true;
    }

    /**
     * Return Tab label
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Company Assignment');
    }

    /**
     * Return URL link to Tab content
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl(
            'mycompany/customer/companyGrid',
            ['_current' => true, 'initial' => 1]
        );
    }
}
