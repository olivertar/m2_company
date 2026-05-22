<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Orangecat\Company\Api\RoleRepositoryInterface;
use Orangecat\Company\Api\Data\RoleInterface;
use Orangecat\Company\Api\Data\RoleSearchResultsInterfaceFactory;
use Orangecat\Company\Model\ResourceModel\Role as RoleResource;
use Orangecat\Company\Model\ResourceModel\Role\CollectionFactory as RoleCollectionFactory;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * @param RoleResource $resource
     * @param RoleFactory $roleFactory
     * @param RoleCollectionFactory $collectionFactory
     * @param RoleSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private RoleResource $resource,
        private RoleFactory $roleFactory,
        private RoleCollectionFactory $collectionFactory,
        private RoleSearchResultsInterfaceFactory $searchResultsFactory,
        private CollectionProcessorInterface $collectionProcessor,
        private \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * @inheritdoc
     */
    public function save(\Orangecat\Company\Api\Data\RoleInterface $role)
    {
        try {
            $this->resource->save($role);
        } catch (\Exception $exception) {
            $this->logger->error('Could not save role: ' . $exception->getMessage(), ['exception' => $exception]);
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $role;
    }

    /**
     * @inheritdoc
     */
    public function get($roleId)
    {
        $role = $this->roleFactory->create();
        $this->resource->load($role, $roleId);
        if (!$role->getId()) {
            throw new NoSuchEntityException(__('Role with id "%1" does not exist.', $roleId));
        }
        return $role;
    }

    /**
     * @inheritdoc
     */
    public function delete(\Orangecat\Company\Api\Data\RoleInterface $role)
    {
        try {
            $this->resource->delete($role);
        } catch (\Exception $exception) {
            $this->logger->error('Could not delete role: ' . $exception->getMessage(), ['exception' => $exception]);
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($roleId)
    {
        return $this->delete($this->get($roleId));
    }

    /**
     * @inheritdoc
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }
}
