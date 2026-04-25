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

namespace Orangecat\Company\Block\Account;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Framework\App\Request\DataPersistorInterface;

class Create extends Template
{
    /**
     * @param Context $context
     * @param Country $countrySource
     * @param DataPersistorInterface $dataPersistor
     * @param array $data
     */
    public function __construct(
        Context $context,
        private Country $countrySource,
        private DataPersistorInterface $dataPersistor,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormAction(): string
    {
        return $this->getUrl('company/account/createPost');
    }

    /**
     * Get country options
     *
     * @return array
     */
    public function getCountryOptions(): array
    {
        return $this->countrySource->toOptionArray();
    }

    /**
     * Get saved form data
     *
     * @return array
     */
    public function getFormData(): array
    {
        $data = $this->dataPersistor->get('company_account_create');
        if (!$data) {
            return [];
        }
        $this->dataPersistor->clear('company_account_create');
        return $data;
    }
}
