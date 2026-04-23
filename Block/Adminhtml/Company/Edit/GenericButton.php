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

namespace Orangecat\Company\Block\Adminhtml\Company\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class GenericButton
{
    /**
     * @param Context $context
     * @param \Orangecat\Company\Api\CompanyRepositoryInterface $companyRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        protected Context $context,
        protected \Orangecat\Company\Api\CompanyRepositoryInterface $companyRepository,
        private \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        try {
            return $this->context->getRequest()->getParam('id');
        } catch (\Exception $e) {
            $this->logger->error('Error getting ID in GenericButton: ' . $e->getMessage(), ['exception' => $e]);
        }
        return null;
    }

    /**
     * Get URL
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
