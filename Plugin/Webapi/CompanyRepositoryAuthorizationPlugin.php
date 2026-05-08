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

namespace Orangecat\Company\Plugin\Webapi;

use Magento\Framework\Exception\AuthorizationException;
use Magento\Store\Model\StoreManagerInterface;
use Orangecat\Company\Api\CompanyRepositoryInterface;

class CompanyRepositoryAuthorizationPlugin
{
    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private StoreManagerInterface $storeManager
    ) {
    }

    /**
     * Validate ownership before saving company via API
     *
     * @param CompanyRepositoryInterface $subject
     * @param \Orangecat\Company\Api\Data\CompanyInterface $company
     * @return array
     * @throws AuthorizationException
     */
    public function beforeSave(CompanyRepositoryInterface $subject, $company): array
    {
        if ($company->getId()) {
            $existing = $subject->get($company->getId());
            if ((int)$existing->getWebsiteId() !== (int)$this->storeManager->getStore()->getWebsiteId()) {
                throw new AuthorizationException(__('You are not authorized to modify this company.'));
            }
        }
        return [$company];
    }

    /**
     * Validate ownership before deleting company via API
     *
     * @param CompanyRepositoryInterface $subject
     * @param int $companyId
     * @return array
     * @throws AuthorizationException
     */
    public function beforeDeleteById(CompanyRepositoryInterface $subject, $companyId): array
    {
        $company = $subject->get($companyId);
        if ((int)$company->getWebsiteId() !== (int)$this->storeManager->getStore()->getWebsiteId()) {
            throw new AuthorizationException(__('You are not authorized to delete this company.'));
        }
        return [$companyId];
    }
}
