<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Block\Account;

use Magento\Framework\View\Element\Html\Link;

class CompanyRegisterLink extends Link
{
    /**
     * Get href
     *
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl('company/account/create');
    }
}
