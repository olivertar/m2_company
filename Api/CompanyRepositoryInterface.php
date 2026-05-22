<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Orangecat\Company\Api\Data\CompanyInterface;

interface CompanyRepositoryInterface
{
    /**
     * Save Company
     *
     * @param \Orangecat\Company\Api\Data\CompanyInterface $company
     * @return \Orangecat\Company\Api\Data\CompanyInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(CompanyInterface $company);

    /**
     * Get Company by ID
     *
     * @param int $companyId
     * @return \Orangecat\Company\Api\Data\CompanyInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($companyId);

    /**
     * Delete Company
     *
     * @param \Orangecat\Company\Api\Data\CompanyInterface $company
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(CompanyInterface $company);

    /**
     * Delete Company by ID
     *
     * @param int $companyId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($companyId);

    /**
     * Get Company List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Orangecat\Company\Api\Data\CompanySearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
