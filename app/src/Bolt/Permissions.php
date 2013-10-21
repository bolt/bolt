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

    public function getRole($roleName) {
        switch ($roleName) {
            case self::ROLE_EVERYBODY:
                return array('label' => 'Everybody', 'description' => 'Built-in role, automatically granted to every registered user', 'builtin' => true);
            case self::ROLE_OWNER:
                return array('label' => 'Owner', 'description' => 'Built-in role, only valid in the context of a resource, and automatically assigned to the owner of that resource.', 'builtin' => true);
            default:
                $roles = $this->getDefinedRoles();
                if (isset($roles[$roleName])) {
                    return $role[$roleName];
                }
                else {
                    return null;
                }
        }
    }

    /**
     * Gets the roles for a given user. If a content type is specified, the
     * "owner" role is added if appropriate.
     * @param array $user An array as returned by Users::getUser()
     * @param Content $content An optional Content object to check ownership
     * @return array An associative array of roles for the given user
     */
    public function getUserRoles($user, Content $content = null) {
        $allRoles = $this->getDefinedRoles();
        $userRoleNames = $user['roles'];
        if (!is_array($userRoleNames)) {
            throw new \Exception('Expected a user-like array, but the "roles" property is not an array');
        }
        $userRoleNames[] = self::ROLE_EVERYBODY;
        if ($content && $content['user'] && $content['user']['id'] === $user['id']) {
            $userRoleNames[] = self::ROLE_OWNER;
        }
        $userRoleNames[] = self::ROLE_OWNER;
        return
            array_combine($userRoleNames,
                array_map(function($roleName) use ($app) { return $this->getRole($roleName); },
                $userRoleNames));
    }

    public function checkRolePermission($roleName, $permissionName, $contenttype = null) {
        if ($contenttype === null) {
            return $this->checkRoleGlobalPermission($roleName, $permissionName);
        }
        else {
            return $this->checkRoleContentTypePermission($roleName, $permissionName, $contenttype);
        }
    }

    public function checkRoleGlobalPermission($roleName, $permissionName) {
        $roles = $this->app['config']->get("global/$permissionName");
        return in_array($roleName, $roles);
    }

}
