<?php

namespace Bolt;

use Silex;

/**
 * This class implements role-based permissions.
 */
class Permissions {
    /**
     * Anonymous user: this role is automatically assigned to everyone,
     * including "non-users" (not logged in)
     */
    const ROLE_ANONYMOUS = 'anonymous';

    /**
     * Everyone means 'everyone with an account'; this role is automatically
     * assigned to every actual user, but not to anonymous access.
     */
    const ROLE_EVERYONE = 'everyone';

    /**
     * Superuser role; if assigned to a user, this role overrides all
     * permission checks, granting everything - pretty much like the 'root'
     * user on *nix systems.
     */
    const ROLE_ROOT = 'root';

    /**
     * A special role that is used to tag the owner of a resource; it is only
     * valid for permission checks that are specific to one particular content
     * item.
     */
    const ROLE_OWNER = 'owner';

    private $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    /**
     * Gets a list of all the roles that can be assigned to users explicitly.
     * This includes all the custom roles from permissions.yml, plus the
     * special 'root' role, but not the special roles 'anonymous', 'everyone',
     * and 'owner' (these are assigned automatically).
     */
    public function getDefinedRoles() {
        $roles = $this->app['config']->get('permissions/roles');
        $roles[self::ROLE_ROOT] = array('label' => 'Root', 'description' => 'Built-in superuser role, automatically grants all permissions', 'builtin' => true);
        return $roles;
    }

    /**
     * Gets meta-information on the specified role.
     * @param string $roleName
     * @return array An associative array describing the role. Keys are:
     * - 'label': A human-readable role name, suitable as a label in the
     *            backend
     * - 'description': A description of what this role is supposed to do.
     * - 'builtin': Optional; if present and true-ish, this is a built-in
     *              role and cannot be overridden in permissions.yml.
     */
    public function getRole($roleName) {
        switch ($roleName) {
            case self::ROLE_ANONYMOUS:
                return array('label' => 'Anonymous', 'description' => 'Built-in role, automatically granted at all times, even if no user is logged in', 'builtin' => true);
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

    /**
     * Low-level permission check. Given a set of available roles, a
     * permission, and an optional content type, this method checks whether
     * the permission may be granted.
     * @param array $roleNames - an array of effective role names. This must
     *                           include any of the appropriate automatic
     *                           roles, as these are not added at this point.
     * @param string $permissionName - which permission to check
     * @param string $contenttype
     * @return bool TRUE if granted, FALSE if not.
     */
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

    /**
     * Lists the roles that would grant the specified global permission.
     */
    public function getRolesByGlobalPermission($permissionName) {
        return $this->app['config']->get("permissions/global/$permissionName");
    }

    /**
     * Gets the configured global roles.
     */
    public function getGlobalRoles() {
        return $this->app['config']->get("permissions/global");
    }

    /**
     * Lists the roles that would grant the specified permission for the
     * specified content type. Sort of a reverse lookup on the permission
     * check.
     */
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
