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

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Orangecat\Company\Api\CompanyRepositoryInterface;
use Orangecat\Company\Api\Data\CompanyInterface;
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Api\RoleRepositoryInterface;
use Orangecat\Company\Model\CompanyCustomer;
use Orangecat\Company\Model\CompanyCustomerFactory;
use Orangecat\Company\Model\CompanyManagement;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer as CompanyCustomerResource;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\Collection;
use Orangecat\Company\Model\ResourceModel\CompanyCustomer\CollectionFactory as CompanyCustomerCollectionFactory;
use PHPUnit\Framework\TestCase;

class CompanyManagementTest extends TestCase
{
    private CompanyManagement $companyManagement;

    private CompanyCustomerResource $resourceMock;
    private CompanyCustomerFactory $companyCustomerFactoryMock;
    private CompanyCustomerCollectionFactory $collectionFactoryMock;
    private CustomerRepositoryInterface $customerRepositoryMock;
    private CompanyRepositoryInterface $companyRepositoryMock;
    private RoleRepositoryInterface $roleRepositoryMock;
    private Registry $registryMock;

    protected function setUp(): void
    {
        $this->resourceMock = $this->createMock(CompanyCustomerResource::class);
        $this->companyCustomerFactoryMock = $this->createMock(CompanyCustomerFactory::class);
        $this->collectionFactoryMock = $this->createMock(CompanyCustomerCollectionFactory::class);
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->companyRepositoryMock = $this->createMock(CompanyRepositoryInterface::class);
        $this->roleRepositoryMock = $this->createMock(RoleRepositoryInterface::class);
        $this->registryMock = $this->createMock(Registry::class);

        $this->companyManagement = new CompanyManagement(
            $this->resourceMock,
            $this->companyCustomerFactoryMock,
            $this->collectionFactoryMock,
            $this->customerRepositoryMock,
            $this->companyRepositoryMock,
            $this->roleRepositoryMock,
            $this->registryMock
        );
    }

    public function testAssignCustomerCreatesNewLink(): void
    {
        $companyId = 1;
        $customerId = 2;
        $roleId = 3;

        $companyMock = $this->createMock(CompanyInterface::class);
        $customerMock = $this->createMock(CustomerInterface::class);
        $roleMock = $this->createMock(RoleInterface::class);

        $this->companyRepositoryMock->expects($this->once())
            ->method('get')
            ->with($companyId)
            ->willReturn($companyMock);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($customerId)
            ->willReturn($customerMock);

        $this->roleRepositoryMock->expects($this->once())
            ->method('get')
            ->with($roleId)
            ->willReturn($roleMock);

        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(null);

        $collectionMock->expects($this->once())
            ->method('addFieldToFilter')
            ->with('customer_id', $customerId);
        $collectionMock->expects($this->once())
            ->method('setPageSize')
            ->with(1);
        $collectionMock->expects($this->once())
            ->method('getFirstItem')
            ->willReturn($linkMock);

        $this->companyCustomerFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($linkMock);

        $linkMock->expects($this->once())->method('setCompanyId')->with($companyId);
        $linkMock->expects($this->once())->method('setCustomerId')->with($customerId);
        $linkMock->expects($this->once())->method('setRoleId')->with($roleId);

        $this->resourceMock->expects($this->once())
            ->method('save')
            ->with($linkMock);

        $result = $this->companyManagement->assignCustomer($companyId, $customerId, $roleId);
        $this->assertTrue($result);
    }

    public function testAssignCustomerUpdatesExistingLink(): void
    {
        $companyId = 1;
        $customerId = 2;
        $roleId = 3;

        $companyMock = $this->createMock(CompanyInterface::class);
        $customerMock = $this->createMock(CustomerInterface::class);
        $roleMock = $this->createMock(RoleInterface::class);

        $this->companyRepositoryMock->method('get')->willReturn($companyMock);
        $this->customerRepositoryMock->method('getById')->willReturn($customerMock);
        $this->roleRepositoryMock->method('get')->willReturn($roleMock);

        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(100);

        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->companyCustomerFactoryMock->expects($this->never())->method('create');

        $linkMock->expects($this->once())->method('setCompanyId')->with($companyId);
        $linkMock->expects($this->once())->method('setRoleId')->with($roleId);

        $this->resourceMock->expects($this->once())->method('save');

        $result = $this->companyManagement->assignCustomer($companyId, $customerId, $roleId);
        $this->assertTrue($result);
    }

    public function testAssignCustomerThrowsWhenCompanyNotFound(): void
    {
        $this->companyRepositoryMock->expects($this->once())
            ->method('get')
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The specified company does not exist.');

        $this->companyManagement->assignCustomer(1, 2, 3);
    }

    public function testAssignCustomerThrowsWhenCustomerNotFound(): void
    {
        $companyMock = $this->createMock(CompanyInterface::class);
        $this->companyRepositoryMock->method('get')->willReturn($companyMock);

        $this->customerRepositoryMock->expects($this->once())
            ->method('getById')
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The specified customer does not exist.');

        $this->companyManagement->assignCustomer(1, 2, 3);
    }

    public function testAssignCustomerThrowsWhenRoleNotFound(): void
    {
        $companyMock = $this->createMock(CompanyInterface::class);
        $customerMock = $this->createMock(CustomerInterface::class);
        $this->companyRepositoryMock->method('get')->willReturn($companyMock);
        $this->customerRepositoryMock->method('getById')->willReturn($customerMock);

        $this->roleRepositoryMock->expects($this->once())
            ->method('get')
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The specified role does not exist.');

        $this->companyManagement->assignCustomer(1, 2, 3);
    }

    public function testAssignCustomerThrowsCouldNotSaveException(): void
    {
        $companyMock = $this->createMock(CompanyInterface::class);
        $customerMock = $this->createMock(CustomerInterface::class);
        $roleMock = $this->createMock(RoleInterface::class);
        $this->companyRepositoryMock->method('get')->willReturn($companyMock);
        $this->customerRepositoryMock->method('getById')->willReturn($customerMock);
        $this->roleRepositoryMock->method('get')->willReturn($roleMock);

        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(null);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->companyCustomerFactoryMock->method('create')->willReturn($linkMock);

        $this->resourceMock->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('DB error'));

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessage('Could not assign customer to company: DB error');

        $this->companyManagement->assignCustomer(1, 2, 3);
    }

    public function testRemoveCustomerDeletesLink(): void
    {
        $customerId = 5;

        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(10);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->resourceMock->expects($this->once())
            ->method('delete')
            ->with($linkMock);

        $result = $this->companyManagement->removeCustomer($customerId);
        $this->assertTrue($result);
    }

    public function testRemoveCustomerReturnsTrueWhenNoLink(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(null);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->resourceMock->expects($this->never())->method('delete');

        $result = $this->companyManagement->removeCustomer(5);
        $this->assertTrue($result);
    }

    public function testRemoveCustomerThrowsCouldNotDeleteException(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(10);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->resourceMock->expects($this->once())
            ->method('delete')
            ->willThrowException(new \Exception('Delete failed'));

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('Could not remove customer from company: Delete failed');

        $this->companyManagement->removeCustomer(5);
    }

    public function testGetCompanyIdByCustomerIdReturnsValue(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(1);
        $linkMock->method('getCompanyId')->willReturn(99);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->assertEquals(99, $this->companyManagement->getCompanyIdByCustomerId(2));
    }

    public function testGetCompanyIdByCustomerIdReturnsNull(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(null);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->assertNull($this->companyManagement->getCompanyIdByCustomerId(2));
    }

    public function testGetRoleIdByCustomerIdReturnsValue(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(1);
        $linkMock->method('getRoleId')->willReturn(5);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->assertEquals(5, $this->companyManagement->getRoleIdByCustomerId(2));
    }

    public function testDeleteCustomerByIdUsesSecureArea(): void
    {
        $customerId = 7;

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('isSecureArea', true);

        $this->customerRepositoryMock->expects($this->once())
            ->method('deleteById')
            ->with($customerId);

        $this->registryMock->expects($this->once())
            ->method('unregister')
            ->with('isSecureArea');

        $this->companyManagement->deleteCustomerById($customerId);
    }

    public function testDeleteCustomerByIdThrowsAndUnregisters(): void
    {
        $customerId = 7;

        $this->registryMock->expects($this->once())
            ->method('register')
            ->with('isSecureArea', true);

        $this->customerRepositoryMock->expects($this->once())
            ->method('deleteById')
            ->willThrowException(new \Exception('Cannot delete'));

        $this->registryMock->expects($this->once())
            ->method('unregister')
            ->with('isSecureArea');

        $this->expectException(CouldNotDeleteException::class);
        $this->expectExceptionMessage('Could not delete customer account: Cannot delete');

        $this->companyManagement->deleteCustomerById($customerId);
    }

    public function testIsCompanyAdminReturnsTrueForAdmin(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(1);
        $linkMock->method('getRoleId')->willReturn(1);
        $linkMock->method('getCompanyId')->willReturn(10);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->assertTrue($this->companyManagement->isCompanyAdmin(2));
    }

    public function testIsCompanyAdminReturnsFalseForNonAdmin(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(1);
        $linkMock->method('getRoleId')->willReturn(2);
        $linkMock->method('getCompanyId')->willReturn(10);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->assertFalse($this->companyManagement->isCompanyAdmin(2));
    }

    public function testIsCompanyAdminReturnsFalseWhenNoCompany(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(1);
        $linkMock->method('getRoleId')->willReturn(1);
        $linkMock->method('getCompanyId')->willReturn(null);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->assertFalse($this->companyManagement->isCompanyAdmin(2));
    }

    public function testValidateManageUserPassesForAdminWithSameCompany(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(1);
        $linkMock->method('getRoleId')->willReturn(1);
        $linkMock->method('getCompanyId')->willReturn(10);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        // No exception expected
        $this->companyManagement->validateManageUser(1, 2);
    }

    public function testValidateManageUserThrowsWhenNotAdmin(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(1);
        $linkMock->method('getRoleId')->willReturn(2);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('You do not have permission to manage users.');

        $this->companyManagement->validateManageUser(1, 2);
    }

    public function testValidateManageUserThrowsWhenDifferentCompany(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $adminLink = $this->createMock(CompanyCustomer::class);
        $adminLink->method('getId')->willReturn(1);
        $adminLink->method('getRoleId')->willReturn(1);
        $adminLink->method('getCompanyId')->willReturn(10);

        $targetLink = $this->createMock(CompanyCustomer::class);
        $targetLink->method('getId')->willReturn(2);
        $targetLink->method('getCompanyId')->willReturn(20);

        $collectionMock->method('getFirstItem')
            ->willReturnOnConsecutiveCalls($adminLink, $adminLink, $adminLink, $targetLink);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('You cannot manage a user who does not belong to your company.');

        $this->companyManagement->validateManageUser(1, 2);
    }

    public function testValidateManageUserPassesWhenTargetUserIsNull(): void
    {
        $collectionMock = $this->createMock(Collection::class);
        $this->collectionFactoryMock->method('create')->willReturn($collectionMock);

        $linkMock = $this->createMock(CompanyCustomer::class);
        $linkMock->method('getId')->willReturn(1);
        $linkMock->method('getRoleId')->willReturn(1);
        $linkMock->method('getCompanyId')->willReturn(10);
        $collectionMock->method('getFirstItem')->willReturn($linkMock);

        // No exception expected
        $this->companyManagement->validateManageUser(1, null);
    }
}
