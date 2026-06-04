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

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class FixApproveAccountNullValues implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();

        $attributeId = $connection->fetchOne(
            $connection->select()
                ->from(['a' => $this->moduleDataSetup->getTable('eav_attribute')], ['attribute_id'])
                ->join(
                    ['et' => $this->moduleDataSetup->getTable('eav_entity_type')],
                    'et.entity_type_id = a.entity_type_id'
                )
                ->where('et.entity_type_code = ?', 'customer')
                ->where('a.attribute_code = ?', 'approve_account')
        );

        if ($attributeId) {
            $connection->update(
                $this->moduleDataSetup->getTable('customer_entity_int'),
                ['value' => 0],
                ['attribute_id = ?' => (int)$attributeId, 'value IS NULL']
            );
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [AddApproveAccountAttribute::class];
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }
}
