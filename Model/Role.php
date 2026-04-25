<?php
/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Model;

use Magento\Framework\Model\AbstractModel;
use Orangecat\Company\Model\ResourceModel\Role as RoleResource;

use Orangecat\Company\Api\Data\RoleInterface;

class Role extends AbstractModel implements RoleInterface
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(RoleResource::class);
    }

    /**
     * Get role ID
     *
     * @return int
     */
    public function getRoleId()
    {
        return $this->getData(self::ROLE_ID);
    }

    /**
     * Set role ID
     *
     * @param int $roleId
     * @return $this
     */
    public function setRoleId($roleId)
    {
        return $this->setData(self::ROLE_ID, $roleId);
    }

    /**
     * Get role name
     *
     * @return string
     */
    public function getRoleName()
    {
        return $this->getData(self::ROLE_NAME);
    }

    /**
     * Set role name
     *
     * @param string $roleName
     * @return $this
     */
    public function setRoleName($roleName)
    {
        return $this->setData(self::ROLE_NAME, $roleName);
    }

    /**
     * Get permissions
     *
     * @return string
     */
    public function getPermissions()
    {
        return $this->getData(self::PERMISSIONS);
    }

    /**
     * Set permissions
     *
     * @param string $permissions
     * @return $this
     */
    public function setPermissions($permissions)
    {
        return $this->setData(self::PERMISSIONS, $permissions);
    }
}
