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
use Orangecat\Company\Api\Data\RoleInterface;

interface RoleRepositoryInterface
{
    /**
     * Save Role
     *
     * @param \Orangecat\Company\Api\Data\RoleInterface $role
     * @return \Orangecat\Company\Api\Data\RoleInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(RoleInterface $role);

    /**
     * Get Role by ID
     *
     * @param int $roleId
     * @return \Orangecat\Company\Api\Data\RoleInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($roleId);

    /**
     * Delete Role
     *
     * @param \Orangecat\Company\Api\Data\RoleInterface $role
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(RoleInterface $role);

    /**
     * Delete Role by ID
     *
     * @param int $roleId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($roleId);

    /**
     * Get Role List
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Orangecat\Company\Api\Data\RoleSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
