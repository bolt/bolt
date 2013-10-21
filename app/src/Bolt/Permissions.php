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

    private $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    public function getDefinedRoles() {
        $roles = $this->app['config']->get('permissions/roles');
        $roles[self::ROLE_ROOT] = array('label' => 'Root', 'description' => 'Built-in superuser role, automatically grants all permissions', 'builtin' => true);
        return $roles;
    }
}
