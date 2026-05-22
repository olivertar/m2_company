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

interface CompanyManagementInterface
{
    /**
     * Assign Customer to Company
     *
     * @param int $companyId
     * @param int $customerId
     * @param int $roleId
     * @param array $data
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function assignCustomer($companyId, $customerId, $roleId, array $data = []);

    /**
     * Remove Customer from Company
     *
     * @param int $customerId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeCustomer($customerId);

    /**
     * Get Company ID by Customer ID
     *
     * @param int $customerId
     * @return int|null
     */
    public function getCompanyIdByCustomerId($customerId);

    /**
     * Get Company Role for Customer
     *
     * @param int $customerId
     * @return int|null
     */
    public function getRoleIdByCustomerId($customerId);

    /**
     * Check if customer is a Company Admin
     *
     * @param int $customerId
     * @return bool
     */
    public function isCompanyAdmin(int $customerId): bool;

    /**
     * Validate if admin can manage the target user.
     * Checks:
     * 1. Admin has company and role 1.
     * 2. Target user belongs to same company.
     *
     * @param int $adminId
     * @param int|null $targetUserId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return void
     */
    public function validateManageUser(int $adminId, ?int $targetUserId): void;
}
