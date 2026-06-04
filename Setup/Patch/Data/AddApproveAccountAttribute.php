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

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddApproveAccountAttribute implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CustomerSetupFactory $customerSetupFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerSetup->addAttribute(Customer::ENTITY, 'approve_account', [
            'type' => 'int',
            'label' => 'Approve account',
            'input' => 'select',
            'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'required' => false,
            'visible' => true,
            'default' => 0,
            'user_defined' => true,
            'position' => 9998,
            'system' => 0,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true,
        ]);

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'approve_account');
        $attribute->addData([
            'attribute_set_id' => $customerSetup->getDefaultAttributeSetId(Customer::ENTITY),
            'attribute_group_id' => $customerSetup->getDefaultAttributeGroupId(Customer::ENTITY),
            'used_in_forms' => ['adminhtml_customer', 'customer_account_create', 'customer_account_edit'],
        ]);
        $attribute->save();

        return $this;
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
