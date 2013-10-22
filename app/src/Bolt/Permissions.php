<?php

namespace Bolt;

use Silex;

/**
 * This class implements role-based permissions.
 */
class Permissions {
    const ROLE_EVERYONE = 'everyone';
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
            case self::ROLE_EVERYONE:
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
        $userRoleNames[] = self::ROLE_EVERYONE;
        if ($content && $content['user'] && $content['user']['id'] === $user['id']) {
            $userRoleNames[] = self::ROLE_OWNER;
        }
        $userRoleNames[] = self::ROLE_OWNER;
        return
            array_combine($userRoleNames,
                array_map(function($roleName) use ($app) { return $this->getRole($roleName); },
                $userRoleNames));
    }

    public function checkPermission($roleNames, $permissionName, $contenttype = null) {
        $roleNames = array_unique($roleNames);
        if (in_array(Permissions::ROLE_ROOT, $roleNames)) {
                error_log("Granting '$permissionName' " .
                    ($contenttype ? "for $contenttype " : "") .
                    "to root user");
                return true;
        }
        foreach ($roleNames as $roleName) {
            if ($this->checkRolePermission($roleName, $permissionName, $contenttype)) {
                error_log("Granting '$permissionName' " .
                    ($contenttype ? "for $contenttype " : "") .
                    "based on role $roleName");
                return true;
            }
        }
        error_log("Not granting '$permissionName' " .
            ($contenttype ? "for $contenttype " : "") .
            "; available roles: " . implode(', ', $roleNames));
        return false;
    }

    /**
     * Checks whether the specified $roleName grants permission $permissionName
     * for the $contenttype in question (NULL for global permissions).
     */
    public function checkRolePermission($roleName, $permissionName, $contenttype = null) {
        if ($contenttype === null) {
            return $this->checkRoleGlobalPermission($roleName, $permissionName);
        }
        else {
            return $this->checkRoleContentTypePermission($roleName, $permissionName, $contenttype);
        }
    }

    public function checkRoleGlobalPermission($roleName, $permissionName) {
        $roles = $this->getRolesByGlobalPermission($permissionName);
        if (!is_array($roles)) {
            throw new \Exception("Configuration error: $permissionName is not granted to any roles.");
        }
        return in_array($roleName, $roles);
    }

    public function checkRoleContentTypePermission($roleName, $permissionName, $contenttype) {
        $roles = $this->getRolesByContentTypePermission($permissionName, $contenttype);
        return in_array($roleName, $roles);
    }

    public function getRolesByGlobalPermission($permissionName) {
        return $this->app['config']->get("permissions/global/$permissionName");
    }

    public function getGlobalRoles() {
        return $this->app['config']->get("permissions/global");
    }

    public function getRolesByContentTypePermission($permissionName, $contenttype) {
        // Here's how it works:
        // - if a permission is granted through 'contenttype-all', it is effectively granted
        // - if a permission is granted through 'contenttypes/$contenttype', it is effectively granted
        // - if 'contenttypes/$contenttype/$permissionName' is not set, *and* the permission is granted through 'contenttype-default', it is effectively granted
        // - otherwise, the permission is denied
        $overrideRoles = $this->app['config']->get("permissions/contenttype-all/$permissionName");
        if (!is_array($overrideRoles)) {
            $overrideRoles = array();
        }
        $contenttypeRoles = $this->app['config']->get("permissions/contenttypes/$contenttype/$permissionName");
        if (!is_array($contenttypeRoles)) {
            $contenttypeRoles = $this->app['config']->get("permissions/contenttype-default/$permissionName");
        }
        if (!is_array($contenttypeRoles)) {
            $contenttypeRoles = array();
        }
        $effectiveRoles = array_unique(array_merge($overrideRoles, $contenttypeRoles));
        return $effectiveRoles;
    }

}
