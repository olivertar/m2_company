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

namespace Orangecat\Company\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Store\Model\ScopeInterface;
use Orangecat\Company\Model\Config;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\Collection;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as CompanyCustomerCollectionFactory;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private Config $config;
    private ScopeConfigInterface $scopeConfigMock;
    private CompanyCustomerCollectionFactory $collectionFactoryMock;
    private HttpRequest $requestMock;

    protected function setUp(): void
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->collectionFactoryMock = $this->createMock(CompanyCustomerCollectionFactory::class);
        $this->requestMock = $this->createMock(HttpRequest::class);

        $this->config = new Config(
            $this->scopeConfigMock,
            $this->collectionFactoryMock,
            $this->requestMock
        );
    }

    public function testGetConfig(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('some/path', ScopeInterface::SCOPE_STORE)
            ->willReturn('value');

        $this->assertEquals('value', $this->config->getConfig('some/path'));
    }

    public function testIsCompanyRegistrationContextReturnsTrueForApi(): void
    {
        $this->config->setApiCompanyContext(true);

        $this->assertTrue($this->config->isCompanyRegistrationContext());
    }

    public function testIsCompanyRegistrationContextReturnsTrueForCompanyRoute(): void
    {
        $this->config->setApiCompanyContext(false);
        $this->requestMock->expects($this->once())
            ->method('getRouteName')
            ->willReturn('company');

        $this->assertTrue($this->config->isCompanyRegistrationContext());
    }

    public function testIsCompanyRegistrationContextReturnsTrueForMycompanyRoute(): void
    {
        $this->config->setApiCompanyContext(false);
        $this->requestMock->expects($this->once())
            ->method('getRouteName')
            ->willReturn('mycompany');

        $this->assertTrue($this->config->isCompanyRegistrationContext());
    }

    public function testIsCompanyRegistrationContextReturnsFalseForOtherRoute(): void
    {
        $this->config->setApiCompanyContext(false);
        $this->requestMock->expects($this->once())
            ->method('getRouteName')
            ->willReturn('customer');

        $this->assertFalse($this->config->isCompanyRegistrationContext());
    }

    public function testIsCompanyUserReturnsTrueWhenCollectionHasItems(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('customer_id', 5);
        $collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(1);

        $this->assertTrue($this->config->isCompanyUser(5));
    }

    public function testIsCompanyUserReturnsFalseWhenCollectionEmpty(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('customer_id', 5);
        $collectionMock->expects($this->once())
            ->method('getSize')
            ->willReturn(0);

        $this->assertFalse($this->config->isCompanyUser(5));
    }

    public function testIsNotificationEnabled(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('mycompany/email/enable_email_notification', ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $this->assertTrue($this->config->isNotificationEnabled());
    }

    public function testGetNotificationEmail(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('mycompany/email/notification_email', ScopeInterface::SCOPE_STORE)
            ->willReturn('admin@example.com');

        $this->assertEquals('admin@example.com', $this->config->getNotificationEmail());
    }

    public function testGetEmailTemplate(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('mycompany/email/email_template', ScopeInterface::SCOPE_STORE)
            ->willReturn('template_id');

        $this->assertEquals('template_id', $this->config->getEmailTemplate());
    }

    public function testIsFrontendCustomerRegistrationAllowed(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('mycompany/customers/allow_frontend_customer_registration', ScopeInterface::SCOPE_STORE)
            ->willReturn(false);

        $this->assertFalse($this->config->isFrontendCustomerRegistrationAllowed());
    }

    public function testIsCustomerApprovalRequired(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('isSetFlag')
            ->with('mycompany/customers/require_approval_for_customer', ScopeInterface::SCOPE_STORE)
            ->willReturn(true);

        $this->assertTrue($this->config->isCustomerApprovalRequired());
    }

    public function testGetCompanyNoPasswordEmailTemplate(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('mycompany/email/company_no_password_template', ScopeInterface::SCOPE_STORE)
            ->willReturn('no_password_template');

        $this->assertEquals('no_password_template', $this->config->getCompanyNoPasswordEmailTemplate());
    }

    public function testGetCompanyStatusChangeEmailTemplate(): void
    {
        $this->scopeConfigMock->expects($this->once())
            ->method('getValue')
            ->with('mycompany/email/company_status_change_template', ScopeInterface::SCOPE_STORE)
            ->willReturn('status_change_template');

        $this->assertEquals('status_change_template', $this->config->getCompanyStatusChangeEmailTemplate());
    }
}
