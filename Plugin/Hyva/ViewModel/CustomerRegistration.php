<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Plugin\Hyva\ViewModel;

use Hyva\Theme\ViewModel\CustomerRegistration as HyvaCustomerRegistration;
use Orangecat\Company\Model\Config;

class CustomerRegistration
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
     * @param HyvaCustomerRegistration $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsAllowed(HyvaCustomerRegistration $subject, bool $result): bool
    {
        if (!$this->config->isFrontendCustomerRegistrationAllowed()) {
            return false;
        }
        return $result;
    }
}
