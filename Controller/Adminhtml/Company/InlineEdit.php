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
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Orangecat\Company\Api\CompanyRepositoryInterface;

class InlineEdit extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Orangecat_Company::company_save';

    /**
     * Fields allowed for inline editing.
     *
     * @var string[]
     */
    private const ALLOWED_INLINE_FIELDS = [
        'name',
        'status',
    ];

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CompanyRepositoryInterface $companyRepository
     * @param Validator $formKeyValidator
     */
    public function __construct(
        Context $context,
        protected JsonFactory $jsonFactory,
        protected CompanyRepositoryInterface $companyRepository,
        private Validator $formKeyValidator
    ) {
        parent::__construct($context);
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

        if (!$this->getRequest()->isPost() || !$this->formKeyValidator->validate($this->getRequest())) {
            $messages[] = __('Invalid request. Please refresh the page and try again.');
            $error = true;
            return $resultJson->setData([
                'messages' => $messages,
                'error' => $error
            ]);
        }

        $postItems = $this->getRequest()->getParam('items', []);
        if (!count($postItems)) {
            $messages[] = __('Please correct the data sent.');
            $error = true;
        } else {
            foreach (array_keys($postItems) as $entityId) {
                try {
                    /** @var \Orangecat\Company\Model\Company $company */
                    $company = $this->companyRepository->get($entityId);
                    $filteredData = array_intersect_key(
                        $postItems[$entityId],
                        array_flip(self::ALLOWED_INLINE_FIELDS)
                    );
                    if (!empty($filteredData)) {
                        $company->addData($filteredData);
                        $this->companyRepository->save($company);
                    }
                } catch (\Exception $e) {
                    $messages[] = $this->getErrorWithCompanyId(
                        $company,
                        __($e->getMessage())
                    );
                    $error = true;
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
