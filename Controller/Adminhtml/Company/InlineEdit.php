<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Controller\Adminhtml\Company;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Orangecat\Company\Api\CompanyRepositoryInterface;

class InlineEdit extends Action
{
    public const ADMIN_RESOURCE = 'Orangecat_Company::company';

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var CompanyRepositoryInterface
     */
    protected $companyRepository;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CompanyRepositoryInterface $companyRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CompanyRepositoryInterface $companyRepository
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->companyRepository = $companyRepository;
    }

    /**
     * Inline edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                $allowedFields = ['name', 'email', 'tax_id', 'status'];
                foreach (array_keys($postItems) as $entityId) {
                    try {
                        /** @var \Orangecat\Company\Model\Company $company */
                        $company = $this->companyRepository->get($entityId);
                        $company->addData(array_intersect_key($postItems[$entityId], array_flip($allowedFields)));
                        $this->companyRepository->save($company);
                    } catch (\Exception $e) {
                        $messages[] = $this->getErrorWithCompanyId(
                            $company,
                            __($e->getMessage())
                        );
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    /**
     * Add company title to error message
     *
     * @param \Orangecat\Company\Api\Data\CompanyInterface $company
     * @param string $errorText
     * @return string
     */
    protected function getErrorWithCompanyId($company, $errorText)
    {
        return '[Company ID: ' . $company->getId() . '] ' . $errorText;
    }
}
