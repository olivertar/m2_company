<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface CompanyCustomerInterface extends ExtensibleDataInterface
{
    public const COMPANY_ID = 'company_id';
    public const CUSTOMER_ID = 'customer_id';
    public const ROLE_ID = 'role_id';
    /**
     * Get Company ID
     *
     * @return int|null
     */
    public function getCompanyId();

    /**
     * Set Company ID
     *
     * @param int $companyId
     * @return $this
     */
    public function setCompanyId($companyId);

    /**
     * Get Customer ID
     *
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Set Customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Get Role ID
     *
     * @return int|null
     */
    public function getRoleId();

    /**
     * Set Role ID
     *
     * @param int $roleId
     * @return $this
     */
    public function setRoleId($roleId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Orangecat\Company\Api\Data\CompanyCustomerExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Orangecat\Company\Api\Data\CompanyCustomerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Orangecat\Company\Api\Data\CompanyCustomerExtensionInterface $extensionAttributes
    );
}
