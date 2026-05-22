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

namespace Orangecat\Company\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as CompanyCustomerCollectionFactory;
use Magento\Framework\App\Request\Http as HttpRequest;

class Config
{
    private const XML_PATH_ENABLED = 'mycompany/email/enable_email_notification';
    private const XML_PATH_EMAIL = 'mycompany/email/notification_email';
    private const XML_PATH_TEMPLATE = 'mycompany/email/email_template';
    private const XML_PATH_COMPANY_NO_PASSWORD_TEMPLATE = 'mycompany/email/company_no_password_template';
    private const XML_PATH_STATUS_CHANGE_TEMPLATE = 'mycompany/email/company_status_change_template';

    private const XML_PATH_ALLOW_REGISTRATION = 'mycompany/customers/allow_frontend_customer_registration';
    private const XML_PATH_REQUIRE_APPROVAL = 'mycompany/customers/require_approval_for_customer';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param CompanyCustomerCollectionFactory $companyCustomerCollectionFactory
     * @param HttpRequest $request
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private CompanyCustomerCollectionFactory $companyCustomerCollectionFactory,
        private HttpRequest $request
    ) {
    }

    /**
     * Get config value
     *
     * @param string $path
     * @return mixed
     */
    public function getConfig(string $path)
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @var bool
     */
    private $isApiContext = false;

    /**
     * Set API context
     *
     * @param bool $flag
     */
    public function setApiCompanyContext(bool $flag)
    {
        $this->isApiContext = $flag;
    }

    /**
     * Check if request is company registration context
     *
     * @return bool
     */
    public function isCompanyRegistrationContext(): bool
    {
        if ($this->isApiContext) {
            return true;
        }
        $route = $this->request->getRouteName();
        return $route === 'company' || $route === 'mycompany';
    }

    /**
     * Check if customer is a company admin
     *
     * @param int $customerId
     * @return bool
     */
    public function isCompanyUser(int $customerId): bool
    {
        $collection = $this->companyCustomerCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId);

        return $collection->getSize() > 0;
    }

    /**
     * Check if email notification is enabled
     *
     * @return bool
     */
    public function isNotificationEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get notification email address(es)
     *
     * @return string
     */
    public function getNotificationEmail(): string
    {
        return (string)$this->getConfig(self::XML_PATH_EMAIL);
    }

    /**
     * Get email template ID
     *
     * @return string
     */
    public function getEmailTemplate(): string
    {
        return (string)$this->getConfig(self::XML_PATH_TEMPLATE);
    }

    /**
     * Check if frontend customer registration is allowed
     *
     * @return bool
     */
    public function isFrontendCustomerRegistrationAllowed(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ALLOW_REGISTRATION, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check if approval is required for customer
     *
     * @return bool
     */
    public function isCustomerApprovalRequired(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_REQUIRE_APPROVAL, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get email template for company users created without password
     *
     * @return string
     */
    public function getCompanyNoPasswordEmailTemplate(): string
    {
        return (string)$this->getConfig(self::XML_PATH_COMPANY_NO_PASSWORD_TEMPLATE);
    }

    /**
     * Get email template for company status change
     *
     * @return string
     */
    public function getCompanyStatusChangeEmailTemplate(): string
    {
        return (string)$this->getConfig(self::XML_PATH_STATUS_CHANGE_TEMPLATE);
    }
}
