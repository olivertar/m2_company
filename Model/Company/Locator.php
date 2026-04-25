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

namespace Orangecat\Company\Model\Company;

use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\Company\Api\Data\CompanyInterface;
use Orangecat\Company\Api\Data\CompanyInterfaceFactory;
use Magento\Framework\App\RequestInterface;
use Exception;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as LinkCollectionFactory;

/**
 * Getting information about the company for adminhtml area
 */
class Locator
{
    /**
     * @var array
     */
    private array $cache = [];

    /**
     * @param CompanyRepositoryInterface $companyRepository
     * @param CompanyInterfaceFactory $companyFactory
     * @param RequestInterface $request
     * @param LinkCollectionFactory $companyCustomerCollectionFactory
     */
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
        private CompanyInterfaceFactory $companyFactory,
        private RequestInterface $request,
        private LinkCollectionFactory $companyCustomerCollectionFactory
    ) {
    }

    /**
     * Get the company
     *
     * @return CompanyInterface
     */
    public function getCompany(): CompanyInterface
    {
        $cacheKey = 'company';
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $company = $this->companyRepository->get(
                (int)$this->request->getParam('entity_id')
            );
        } catch (Exception $exception) {
            $company = $this->companyFactory->create();
        }

        return $this->cache[$cacheKey] = $company;
    }

    /**
     * Get company admin customer ID
     *
     * @param int $companyId
     * @return int|null
     */
    public function getCompanyAdminId(int $companyId): ?int
    {
        $collection = $this->companyCustomerCollectionFactory->create();
        $collection->addFieldToFilter('company_id', $companyId);
        $collection->addFieldToFilter('role_id', 1); // 1 is Admin Role
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();
        return $item->getId() ? (int)$item->getCustomerId() : null;
    }
}
