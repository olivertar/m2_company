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

namespace Orangecat\Company\Controller\Adminhtml\Company;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;

class CustomerGrid extends Action
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Get customer grid for company edit
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $layout = $this->layoutFactory->create();
        $gridBlock = $layout->createBlock(
            \Orangecat\Company\Block\Adminhtml\Company\Edit\Tab\CustomerGrid::class,
            'company.company_admin.grid'
        );

        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setContents($gridBlock->toHtml());
        return $resultRaw;
    }
}
