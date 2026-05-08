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

namespace Orangecat\Company\Test\Unit\Observer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Orangecat\Company\Model\Config;
use Orangecat\Company\Observer\AutoApproveCustomer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AutoApproveCustomerTest extends TestCase
{
    private AutoApproveCustomer $observer;
    private Config $configMock;
    private CustomerRepositoryInterface $customerRepositoryMock;
    private CustomerSession $customerSessionMock;
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerSessionMock = $this->createMock(CustomerSession::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        $this->observer = new AutoApproveCustomer(
            $this->configMock,
            $this->customerRepositoryMock,
            $this->customerSessionMock,
            $this->loggerMock
        );
    }

    public function testExecuteReturnsEarlyWhenRegistrationNotAllowed(): void
    {
        $this->configMock->expects($this->once())
            ->method('isFrontendCustomerRegistrationAllowed')
            ->willReturn(false);

        $this->configMock->expects($this->never())->method('isCustomerApprovalRequired');

        $observerMock = $this->createMock(Observer::class);
        $this->observer->execute($observerMock);
        $this->addToAssertionCount(1);
    }

    public function testExecuteAutoApprovesWhenApprovalNotRequired(): void
    {
        $this->configMock->method('isFrontendCustomerRegistrationAllowed')->willReturn(true);
        $this->configMock->method('isCustomerApprovalRequired')->willReturn(false);

        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->expects($this->once())
            ->method('setCustomAttribute')
            ->with('approve_account', 1);

        $this->customerRepositoryMock->expects($this->once())
            ->method('save')
            ->with($customerMock);

        $event = new Event(['customer' => $customerMock]);
        $observer = new Observer();
        $observer->setEvent($event);

        $this->customerSessionMock->expects($this->never())->method('logout');

        $this->observer->execute($observer);
    }

    public function testExecuteRequiresApprovalAndLogsOut(): void
    {
        $this->configMock->method('isFrontendCustomerRegistrationAllowed')->willReturn(true);
        $this->configMock->method('isCustomerApprovalRequired')->willReturn(true);

        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->expects($this->once())
            ->method('setCustomAttribute')
            ->with('approve_account', 0);

        $this->customerRepositoryMock->expects($this->once())
            ->method('save')
            ->with($customerMock);

        $event = new Event(['customer' => $customerMock]);
        $observer = new Observer();
        $observer->setEvent($event);

        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(true);
        $this->customerSessionMock->expects($this->once())
            ->method('logout');

        $this->observer->execute($observer);
    }

    public function testExecuteDoesNotLogoutWhenNotLoggedIn(): void
    {
        $this->configMock->method('isFrontendCustomerRegistrationAllowed')->willReturn(true);
        $this->configMock->method('isCustomerApprovalRequired')->willReturn(true);

        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->expects($this->once())
            ->method('setCustomAttribute')
            ->with('approve_account', 0);

        $this->customerRepositoryMock->expects($this->once())
            ->method('save')
            ->with($customerMock);

        $event = new Event(['customer' => $customerMock]);
        $observer = new Observer();
        $observer->setEvent($event);

        $this->customerSessionMock->expects($this->once())
            ->method('isLoggedIn')
            ->willReturn(false);
        $this->customerSessionMock->expects($this->never())
            ->method('logout');

        $this->observer->execute($observer);
    }

    public function testExecuteLogsErrorOnException(): void
    {
        $this->configMock->method('isFrontendCustomerRegistrationAllowed')->willReturn(true);
        $this->configMock->method('isCustomerApprovalRequired')->willReturn(false);

        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->method('setCustomAttribute')->willReturnSelf();

        $exception = new \Exception('Save failed');
        $this->customerRepositoryMock->expects($this->once())
            ->method('save')
            ->willThrowException($exception);

        $event = new Event(['customer' => $customerMock]);
        $observer = new Observer();
        $observer->setEvent($event);

        $this->loggerMock->expects($this->once())
            ->method('error')
            ->with(
                'Error auto-approving customer: Save failed',
                $this->callback(function ($context) {
                    return isset($context['exception']);
                })
            );

        $this->observer->execute($observer);
    }
}
