<?php

namespace Bolt;

use Silex;

/**
 * This class implements role-based permissions.
 */
class Permissions {
    const ROLE_EVERYBODY = 'everybody';
    const ROLE_ROOT = 'root';
    const ROLE_OWNER = 'owner';

    public static function getDefinedRoles(Application $app) {
        $permissions = $app['config']->get('permissions/roles');
        $permissions[self::ROLE_ROOT] = array('label' => 'Root', 'description' => 'Built-in superuser role, automatically grants all permissions', 'builtin' => true);
        return $permissions;
    }
}
