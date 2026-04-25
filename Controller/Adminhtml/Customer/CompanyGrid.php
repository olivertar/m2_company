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

namespace Orangecat\Company\Controller\Adminhtml\Customer;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class CompanyGrid extends Action
{
    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        protected RawFactory $resultRawFactory,
        protected LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Get company grid for customer assignment tab
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $this->_objectManager->get(\Magento\Framework\Registry::class)->register(
                \Magento\Customer\Controller\RegistryConstants::CURRENT_CUSTOMER_ID,
                $id
            );
        }

        // Create Blocks
        $layout = $this->layoutFactory->create();
        $gridBlock = $layout->createBlock(
            \Orangecat\Company\Block\Adminhtml\Edit\Tab\CompanyGrid::class,
            'customer.company.grid'
        );

        // Conditional Rendering:
        // - Initial Load (Tab): Show Role Selector + Grid
        // - Grid Refresh (Ajax): Show Grid Only
        $html = '';
        if ($this->getRequest()->getParam('initial')) {
            $roleBlock = $layout->createBlock(
                \Orangecat\Company\Block\Adminhtml\Edit\Tab\Role::class,
                'customer.company.role'
            );
            $html .= $roleBlock->toHtml();
        }

        $html .= $gridBlock->toHtml();

        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($html);
        return $resultRaw;
    }
}
