<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Api\Data;

interface RoleInterface
{
    public const ROLE_ID = 'role_id';
    public const ROLE_NAME = 'role_name';
    public const PERMISSIONS = 'permissions';
    public const ADMIN_ROLE_ID = 1;

    /**
     * Get Role ID
     *
     * @return int|null
     */
    public function getRoleId();

    /**
     * Set Role ID
     *
     * @param int $roleId
     * @return $this
     */
    public function setRoleId($roleId);

    /**
     * Get Role Name
     *
     * @return string|null
     */
    public function getRoleName();

    /**
     * Set Role Name
     *
     * @param string $roleName
     * @return $this
     */
    public function setRoleName($roleName);

    /**
     * Get Permissions
     *
     * @return string|null
     */
    public function getPermissions();

    /**
     * Set Permissions
     *
     * @param string $permissions
     * @return $this
     */
    public function setPermissions($permissions);
}
