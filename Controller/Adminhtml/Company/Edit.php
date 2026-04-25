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
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Edit company action
 */
class Edit extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Orangecat_Company::company_save';

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        private PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Orangecat_Company::company')
            ->addBreadcrumb(__('Company'), __('Company'))
            ->addBreadcrumb(__('Manage Companies'), __('Manage Companies'));

        $entityId = $this->getRequest()->getParam('entity_id');
        if ($entityId) {
            $resultPage->getConfig()->getTitle()->prepend(__('Edit Company'));
        } else {
            $resultPage->getConfig()->getTitle()->prepend(__('New Company'));
        }

        return $resultPage;
    }
}
