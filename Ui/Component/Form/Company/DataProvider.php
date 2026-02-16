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

namespace Orangecat\Company\Ui\Component\Form\Company;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Orangecat\Company\Model\ResourceModel\Company\Collection;
use Orangecat\Company\Model\ResourceModel\Company\CollectionFactory;

/**
 * Data provider for company form
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var array
     */
    protected $_loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        CollectionFactory $collectionFactory,
        protected DataPersistorInterface $dataPersistor,
        protected RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->_loadedData)) {
            return $this->_loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $company) {
            $companyData = $company->getData();
            $this->_loadedData[$company->getId()] = [
                'general' => [
                    'entity_id' => $companyData['entity_id'] ?? null,
                    'name' => $companyData['name'] ?? null,
                    'name_legal' => $companyData['name_legal'] ?? null,
                    'email' => $companyData['email'] ?? null,
                    'tax_id' => $companyData['tax_id'] ?? null,
                    'status' => $companyData['status'] ?? null,
                    'website_id' => $companyData['website_id'] ?? null,
                ],
                'address' => [
                    'address' => $companyData['address'] ?? null,
                    'city' => $companyData['city'] ?? null,
                    'country' => $companyData['country'] ?? null,
                    'region' => $companyData['region'] ?? null,
                    'postalcode' => $companyData['postalcode'] ?? null,
                    'telephone' => $companyData['telephone'] ?? null,
                ]
            ];
        }

        $data = $this->dataPersistor->get('company_company');
        if (!empty($data)) {
            $company = $this->collection->getNewEmptyItem();
            $company->setData($data);
            $this->_loadedData[$company->getId()] = $company->getData();
            $this->dataPersistor->clear('company_company');
        }

        return $this->_loadedData;
    }
}
