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
     * Write an entry to the permission audit log
     */
    private function audit($msg) {
        // For now, just log the message.
        // TODO: implement configurable audit logging.
        error_log($msg);
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
                $this->audit("Granting '$permissionName' " .
                    ($contenttype ? "for $contenttype " : "") .
                    "to root user");
                return true;
        }
        foreach ($roleNames as $roleName) {
            if ($this->checkRolePermission($roleName, $permissionName, $contenttype)) {
                $this->audit("Granting '$permissionName' " .
                    ($contenttype ? "for $contenttype " : "") .
                    "based on role $roleName");
                return true;
            }
        }
        $this->audit("Denying '$permissionName' " .
            ($contenttype ? "for $contenttype" : "") .
            "; available roles: " . implode(', ', $roleNames));
        return false;
    }

    /**
     * Checks whether the specified $roleName grants permission $permissionName
     * for the $contenttype in question (NULL for global permissions).
     */
    private function checkRolePermission($roleName, $permissionName, $contenttype = null) {
        if ($contenttype === null) {
            return $this->checkRoleGlobalPermission($roleName, $permissionName);
        }
        else {
            return $this->checkRoleContentTypePermission($roleName, $permissionName, $contenttype);
        }
    }

    private function checkRoleGlobalPermission($roleName, $permissionName) {
        $roles = $this->getRolesByGlobalPermission($permissionName);
        if (!is_array($roles)) {
            throw new \Exception("Configuration error: $permissionName is not granted to any roles.");
        }
        return in_array($roleName, $roles);
    }

    private function checkRoleContentTypePermission($roleName, $permissionName, $contenttype) {
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

    /**
     * Gets the effective roles for a given user.
     * The effective roles include the roles that were explicitly assigned,
     * as well as the built-in automatic roles.
     * @param mixed $user An array or array-access object that contains a
     *                    'roles' key; if no user is given, "guest" access is
     *                    assumed.
     * @return array A list of effective role names for this user.
     */
    public function getEffectiveRolesForUser($user) {
        if (isset($user['roles']) && is_array($user['roles'])) {
            $userRoles = $user['roles'];
            $userRoles[] = Permissions::ROLE_EVERYONE;
        }
        else {
            $userRoles = array();
        }
        $userRoles[] = Permissions::ROLE_ANONYMOUS;
        return $userRoles;
    }

    /**
     * Runs a permission check. Permissions are encoded as strings, where
     * the ':' character acts as a separator for dynamic parts and
     * sub-permissions.
     * Apart from the route-based rules defined in permissions.yml, the
     * following special cases are available:
     *
     * "overview:$contenttype" - view the overview for the content type. Alias
     *                           for "contenttype:$contenttype:view".
     * "contenttype:$contenttype",
     * "contenttype:$contenttype:view",
     * "contenttype:$contenttype:view:$id" - View any item or a particular item
     *                                       of the specified content type.
     * "contenttype:$contenttype:edit",
     * "contenttype:$contenttype:edit:$id" - Edit any item or a particular item
     *                                       of the specified content type.
     * "contenttype:$contenttype:create" - Create a new item of the specified
     *                                     content type. (It doesn't make sense
     *                                     to provide this permission on a
     *                                     per-item basis, for obvious reasons)
     * "contenttype:$contenttype:change-ownership",
     * "contenttype:$contenttype:change-ownership:$id" - Change the ownership
     *                                of the specified content type or item.
     *
     * @param string $what The desired permission, as elaborated upon above.
     * @param mixed $user Optional: the user to check permissions against.
     * @return bool TRUE if the permission is granted, FALSE if denied.
     */
    public function isAllowed($what, $user)
    {
        $this->audit("Checking permission '$what' for user '{$user['username']}'");
        $userRoles = $this->getEffectiveRolesForUser($user);

        $parts = explode(':', $what);
        switch ($parts[0]) {
            case 'overview':
                list ($_, $contenttype) = $parts;
                if (empty($contenttype)) {
                    return true;
                }
                else {
                    $permission = 'view';
                }
            case 'contenttype':
                list($_, $contenttype, $permission, $contentid) = $parts;
                if (empty($permission)) {
                    $permission = 'view';
                }
                // Handle special case for owner.
                // It's a bit unfortunate that we have to fetch the content
                // item for this, but since we're in the back-end, we probably
                // won't see a lot of traffic here, so it's probably
                // forgivable.
                if (!empty($id)) {
                    $content = $this->app['storage']->getContent("$contenttype/$contentid");
                    if (intval($content['ownerid']) &&
                        (intval($content['ownerid']) === intval($user['id']))) {
                        $userRoles[] = Permissions::ROLE_OWNER;
                    }
                }
                break;
            case 'editcontent':
                // editcontent is handled separately in Backend/editcontent()
                // This is because editing content is governed by two separate
                // permissions per content type, "create" and "edit".
                return true;
            default:
                $permission = $what;
                $contenttype = null;
                break;
        }

        return $this->checkPermission($userRoles, $permission, $contenttype);
    }
}
