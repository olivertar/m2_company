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

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Orangecat\Company\Model\RoleFactory;
use Orangecat\Company\Model\ResourceModel\Role as RoleResource;

class InstallDefaultRoles implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RoleFactory $roleFactory
     * @param RoleResource $roleResource
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private RoleFactory $roleFactory,
        private RoleResource $roleResource
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $defaultRoles = [
            ['role_name' => 'Company Admin', 'permissions' => json_encode(['all'])],
            ['role_name' => 'Company Manager', 'permissions' => json_encode(['manage_users', 'view_orders'])],
            ['role_name' => 'Company Buyer', 'permissions' => json_encode(['place_order'])],
        ];

        foreach ($defaultRoles as $roleData) {
            $role = $this->roleFactory->create();
            // Check if role exists to avoid duplicates if patch runs again
            // (though patch checks aliases usually, but good practice)
            // Actually implementation of patch system prevents duplicate runs, so we are safe to just insert.
            $role->setData($roleData);
            $this->roleResource->save($role);
        }

        $this->moduleDataSetup->endSetup();
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
