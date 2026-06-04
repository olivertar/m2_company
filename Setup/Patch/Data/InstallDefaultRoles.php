<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Orangecat\Company\Model\RoleFactory;
use Orangecat\Company\Model\ResourceModel\Role as RoleResource;

class InstallDefaultRoles implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var RoleFactory
     */
    private $roleFactory;

    /**
     * @var RoleResource
     */
    private $roleResource;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param RoleFactory $roleFactory
     * @param RoleResource $roleResource
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        RoleFactory $roleFactory,
        RoleResource $roleResource
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->roleFactory = $roleFactory;
        $this->roleResource = $roleResource;
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

        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('mycompany_role');

        foreach ($defaultRoles as $roleData) {
            $exists = (int)$connection->fetchOne(
                $connection->select()->from($table, ['COUNT(*)'])->where('role_name = ?', $roleData['role_name'])
            );
            if (!$exists) {
                $role = $this->roleFactory->create();
                $role->setData($roleData);
                $this->roleResource->save($role);
            }
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
