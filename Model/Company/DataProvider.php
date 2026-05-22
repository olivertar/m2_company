<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Model\Company;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Orangecat\Company\Model\ResourceModel\Company\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as LinkCollectionFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Orangecat\Company\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;
use Orangecat\Company\Model\ResourceModel\Company\Collection;

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
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param LinkCollectionFactory $linkCollectionFactory
     * @param RoleCollectionFactory $roleCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        protected DataPersistorInterface $dataPersistor,
        protected LinkCollectionFactory $linkCollectionFactory,
        protected RoleCollectionFactory $roleCollectionFactory,
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
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $company) {
            $data = $company->getData();

            // Inject current admin ID for the grid selection
            $adminInfo = $this->getCompanyAdminInfo($company->getId());
            if ($adminInfo) {
                $data['company_admin_id'] = $adminInfo['id'];
                // Also set the value for the insertListing to pick up if possible (though it usually syncs via imports)
            }

            $this->loadedData[$company->getId()] = $data;
        }

        $data = $this->dataPersistor->get('orangecat_company');
        if (!empty($data)) {
            $company = $this->collection->getNewEmptyItem();
            $company->setData($data);
            $this->loadedData[$company->getId()] = $company->getData();
            $this->dataPersistor->clear('orangecat_company');
        }

        return $this->loadedData;
    }

    /**
     * Get company administrator information
     *
     * @param int $companyId
     * @return array|null
     */
    private function getCompanyAdminInfo($companyId)
    {
        $roleCollection = $this->roleCollectionFactory->create();
        $roleCollection->addFieldToFilter('role_name', 'Company Admin');
        $adminRole = $roleCollection->getFirstItem();

        if (!$adminRole->getId()) {
            return null;
        }

        $linkCollection = $this->linkCollectionFactory->create();
        $linkCollection->addFieldToFilter('company_id', $companyId);
        $linkCollection->addFieldToFilter('role_id', $adminRole->getId());
        $link = $linkCollection->getFirstItem();

        if ($link->getId()) {
            return ['id' => $link->getCustomerId()];
        }

        return null;
    }
}
