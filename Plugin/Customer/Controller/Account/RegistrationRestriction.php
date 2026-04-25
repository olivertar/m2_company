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

namespace Orangecat\Company\Plugin\Customer\Controller\Account;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Exception\NotFoundException;
use Orangecat\Company\Model\Config;

class RegistrationRestriction
{
    /**
     * @param Config $config
     */
    public function __construct(
        private Config $config
    ) {
    }

    /**
     * Check registration restriction before executing action
     *
     * @param ActionInterface $subject
     * @return void
     * @throws NotFoundException
     */
    public function beforeExecute(ActionInterface $subject)
    {
        if (!$this->config->isFrontendCustomerRegistrationAllowed()) {
            throw new NotFoundException(__('Page not found.'));
        }
    }
}
