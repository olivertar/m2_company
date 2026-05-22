<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Plugin\Customer\Block\Account;

use Magento\Customer\Block\Account\RegisterLink as RegisterLinkBlock;
use Orangecat\Company\Model\Config;

class RegisterLink
{
    /**
     * @param Config $config
     */
    public function __construct(
        private Config $config
    ) {
    }

    /**
     * Hide registration link if registration is restricted
     *
     * @param RegisterLinkBlock $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(RegisterLinkBlock $subject, string $result): string
    {
        if (!$this->config->isFrontendCustomerRegistrationAllowed()) {
            return '';
        }
        return $result;
    }
}
