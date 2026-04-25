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

namespace Orangecat\Company\Plugin\Mail\Template;

use Magento\Framework\Mail\Template\TransportBuilder;
use Orangecat\Company\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Model\EmailNotification;

class TransportBuilderPlugin
{
    /**
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private Config $config,
        private ScopeConfigInterface $scopeConfig,
        private \Psr\Log\LoggerInterface $logger
    ) {
    }

    /**
     * Swap email template if customer is a company user
     *
     * @param TransportBuilder $subject
     * @return null
     */
    public function beforeGetTransport(TransportBuilder $subject)
    {
        try {
            // Use Closure to access protected properties
            $getProps = function () {
                return [
                    'templateIdentifier' => $this->templateIdentifier ?? null,
                    'templateVars' => $this->templateVars ?? [],
                    'templateOptions' => $this->templateOptions ?? []
                ];
            };

            // Bind closure to $subject (TransportBuilder instance)
            $propsChecker = $getProps->bindTo($subject, $subject);
            if (!$propsChecker) {
                return;
            }
            $props = $propsChecker();

            $templateId = $props['templateIdentifier'];
            $vars = $props['templateVars'];
            $options = $props['templateOptions'];

            if (!$templateId || empty($vars['customer'])) {
                return;
            }

            // Get standard config value for "New Account No Password"
            // We need store ID from templateOptions if available
            $storeId = $options['store'] ?? null;

            $standardTemplateId = $this->scopeConfig->getValue(
                EmailNotification::XML_PATH_REGISTER_NO_PASSWORD_EMAIL_TEMPLATE,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );

            // Check if current template is the standard one
            if ($templateId == $standardTemplateId) {
                // Check if customer is company user
                // $vars['customer'] is likely a DataObject (CustomerSecure) or CustomerInterface
                $customer = $vars['customer'];
                $customerId = method_exists($customer, 'getId') ? (int)$customer->getId() : null;

                // Check DB for existing user OR request context for new registration
                if (($customerId && $this->config->isCompanyUser($customerId)) ||
                    $this->config->isCompanyRegistrationContext()
                ) {

                    // Swap to Company Template
                    $companyTemplateId = $this->config->getCompanyNoPasswordEmailTemplate();
                    if ($companyTemplateId) {
                        $subject->setTemplateIdentifier($companyTemplateId);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error in TransportBuilderPlugin: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
