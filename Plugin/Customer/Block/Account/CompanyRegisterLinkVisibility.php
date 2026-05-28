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

use Magento\Customer\Model\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Orangecat\Company\Block\Account\CompanyRegisterLink as CompanyRegisterLinkBlock;

class CompanyRegisterLinkVisibility
{
    /**
     * @param HttpContext $httpContext
     */
    public function __construct(
        private HttpContext $httpContext
    ) {
    }

    /**
     * Hide company register link when customer is logged in
     *
     * @param CompanyRegisterLinkBlock $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(CompanyRegisterLinkBlock $subject, string $result): string
    {
        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return '';
        }
        return $result;
    }
}
