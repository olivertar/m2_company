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

namespace Orangecat\Company\Setup\Patch\Data;

use Magento\Cms\Model\PageFactory;
use Magento\Cms\Model\ResourceModel\Page as PageResource;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class CreateTermsAndConditionsPage implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param PageFactory $pageFactory
     * @param PageResource $pageResource
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private PageFactory $pageFactory,
        private PageResource $pageResource
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $page = $this->pageFactory->create();
        $page->setData([
            'title' => 'Company Terms and Conditions',
            'identifier' => 'company-terms',
            'page_layout' => '1column',
            'meta_keywords' => 'terms, conditions, company, b2b',
            'meta_description' => 'Company registration terms and conditions',
            'content_heading' => 'Terms and Conditions',
            'content' => $this->getDefaultContent(),
            'is_active' => 1,
            'stores' => [0], // All store views
            'sort_order' => 0
        ]);

        $this->pageResource->save($page);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Get default content for the CMS page
     *
     * @return string
     */
    private function getDefaultContent()
    {
        return <<<HTML
<div class="terms-and-conditions">
    <h2>Welcome to the Terms and Conditions Page</h2>
    
    <p><strong>IMPORTANT:</strong> This is a placeholder page.
    Please customize this content to include your company's actual terms and conditions.</p>
    
    <h3>How to Edit This Page</h3>
    <ol>
        <li>Go to the Magento Admin Panel</li>
        <li>Navigate to <strong>Content > Pages</strong></li>
        <li>Find and edit "Company Terms and Conditions"</li>
        <li>Update the content with your actual terms and conditions</li>
    </ol>
    
    <hr>
    
    <h3>Suggested Sections to Include:</h3>
    <ul>
        <li><strong>Acceptance of Terms:</strong> Explanation of how users accept these terms</li>
        <li><strong>Account Registration:</strong> Requirements and responsibilities for company accounts</li>
        <li><strong>User Obligations:</strong> What users can and cannot do</li>
        <li><strong>Privacy Policy:</strong> How personal and company data is handled</li>
        <li><strong>Payment Terms:</strong> Billing, payment methods, and terms</li>
        <li><strong>Limitation of Liability:</strong> Legal disclaimers</li>
        <li><strong>Termination:</strong> Conditions under which accounts may be terminated</li>
        <li><strong>Changes to Terms:</strong> How and when terms may be updated</li>
        <li><strong>Contact Information:</strong> How to reach your company for questions</li>
    </ul>
    
    <hr>
    
    <p><em>Last updated: [Current Date] - Please update this date when you modify the terms.</em></p>
</div>
HTML;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
