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

namespace Orangecat\Company\Controller\Account;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\ResultFactory;
use Orangecat\Company\Model\Config;

/**
 * Display company registration form
 */
class Create implements HttpGetActionInterface
{
    /**
     * @param PageFactory $resultPageFactory
     * @param Config $config
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        private PageFactory $resultPageFactory,
        private Config $config,
        private ResultFactory $resultFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        if (!$this->config->isFrontendCustomerRegistrationAllowed()) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            return $resultForward->forward('noroute');
        }

        return $this->resultPageFactory->create();
    }
}
