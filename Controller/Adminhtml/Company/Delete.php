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
use Orangecat\Company\Api\CompanyRepositoryInterface;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Orangecat_Company::company_delete';

    /**
     * @param Context $context
     * @param CompanyRepositoryInterface $companyRepository
     */
    public function __construct(
        Context $context,
        protected CompanyRepositoryInterface $companyRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Delete company action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('entity_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $this->companyRepository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The company has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        $this->messageManager->addErrorMessage(__('We can\'t find a company to delete.'));
        return $resultRedirect->setPath('*/*/');
    }
}
