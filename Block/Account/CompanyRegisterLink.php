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
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Context as CustomerContext;

class CompanyRegisterLink extends Link
{
    /**
     * @param Context $context
     * @param HttpContext $httpContext
     * @param array $data
     */
    public function __construct(
        Context $context,
        private HttpContext $httpContext,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get href
     *
     * @return string
     */
    public function getHref()
    {
        return $this->getUrl('company/account/create');
    }

    /**
     * To html
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            return '';
        }
        return parent::_toHtml();
    }
}
