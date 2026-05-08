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

namespace Orangecat\Company\Test\Unit\Plugin\Customer\Api;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Model\CompanyCustomer;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\Collection;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory;
use Orangecat\Company\Plugin\Customer\Api\CustomerRepositoryInterfacePlugin;
use PHPUnit\Framework\TestCase;

class CustomerRepositoryInterfacePluginTest extends TestCase
{
    private CustomerRepositoryInterfacePlugin $plugin;
    private CollectionFactory $collectionFactoryMock;

    protected function setUp(): void
    {
        $this->collectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->plugin = new CustomerRepositoryInterfacePlugin($this->collectionFactoryMock);
    }

    public function testBeforeDeleteAllowsNonAdmin(): void
    {
        $subjectMock = $this->createMock(CustomerRepositoryInterface::class);
        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->method('getId')->willReturn(5);

        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $collectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnCallback(function ($field, $value) use ($collectionMock) {
                return $collectionMock;
            });

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(null);
        $collectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($linkMock);

        // No exception expected
        $this->plugin->beforeDelete($subjectMock, $customerMock);
        $this->addToAssertionCount(1);
    }

    public function testBeforeDeleteThrowsForAdmin(): void
    {
        $subjectMock = $this->createMock(CustomerRepositoryInterface::class);
        $customerMock = $this->createMock(CustomerInterface::class);
        $customerMock->method('getId')->willReturn(5);

        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $collectionMock->method('addFieldToFilter')->willReturnSelf();

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(10);
        $linkMock->method('getCompanyId')->willReturn(99);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Company ID: 99');

        $this->plugin->beforeDelete($subjectMock, $customerMock);
    }

    public function testBeforeDeleteByIdAllowsNonAdmin(): void
    {
        $subjectMock = $this->createMock(CustomerRepositoryInterface::class);

        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $collectionMock->expects($this->exactly(2))
            ->method('addFieldToFilter')
            ->willReturnCallback(function ($field, $value) use ($collectionMock) {
                return $collectionMock;
            });

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(null);
        $collectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($linkMock);

        // No exception expected
        $this->plugin->beforeDeleteById($subjectMock, 5);
        $this->addToAssertionCount(1);
    }

    public function testBeforeDeleteByIdThrowsForAdmin(): void
    {
        $subjectMock = $this->createMock(CustomerRepositoryInterface::class);

        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $collectionMock->method('addFieldToFilter')->willReturnSelf();

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(10);
        $linkMock->method('getCompanyId')->willReturn(42);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Company ID: 42');

        $this->plugin->beforeDeleteById($subjectMock, 5);
    }
}
