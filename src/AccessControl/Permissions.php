<?php

namespace Bolt\AccessControl;

use Bolt\Legacy\Content;
use Bolt\Storage\Entity;
use Bolt\Translation\Translator as Trans;
use Silex;

/**
 * This class implements role-based permissions.
 */
class Permissions
{
    /**
     * Anonymous user: this role is automatically assigned to everyone,
     * including "non-users" (not logged in).
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

    /** @var \Silex\Application */
    private $app;
    /** @var array Per-request permission cache */
    private $rqcache;
    /** @var array The list of ContentType permissions */
    private $contentTypePermissions = [
        'create'           => false,
        'change-ownership' => false,
        'delete'           => false,
        'edit'             => false,
        'publish'          => false,
        'depublish'        => false,
        'view'             => false,
    ];

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->rqcache = [];
    }

    /**
     * Write an entry to the permission audit log.
     *
     * @param string $msg
     */
    private function audit($msg)
    {
        // Log the message if enabled
        if ($this->app['config']->get('general/debug_permission_audit_mode', false)) {
            $this->app['logger.system']->addInfo($msg, ['event' => 'authentication']);
        }
    }

    /**
     * Gets a list of all the roles that can be assigned to users explicitly.
     * This includes all the custom roles from permissions.yml, plus the
     * special 'root' role, but not the special roles 'anonymous', 'everyone',
     * and 'owner' (these are assigned automatically).
     *
     * @return array
     */
    public function getDefinedRoles()
    {
        $roles = $this->app['config']->get('permissions/roles');
        $roles[self::ROLE_ROOT] = [
            'label'       => 'Root',
            'description' => Trans::__('Built-in superuser role, automatically grants all permissions'),
            'builtin'     => true
        ];

        return $roles;
    }

    /**
     * Gets meta-information on the specified role.
     *
     * @param string $roleName
     *
     * @return array An associative array describing the role. Keys are:
     *               - 'label': A human-readable role name, suitable as a label in the
     *               backend
     *               - 'description': A description of what this role is supposed to do.
     *               - 'builtin': Optional; if present and true-ish, this is a built-in
     *               role and cannot be overridden in permissions.yml.
     */
    public function getRole($roleName)
    {
        switch ($roleName) {
            case self::ROLE_ANONYMOUS:
                return [
                    'label'       => Trans::__('Anonymous'),
                    'description' => Trans::__('Built-in role, automatically granted at all times, even if no user is logged in'),
                    'builtin'     => true,
                ];

            case self::ROLE_EVERYONE:
                return [
                    'label'       => Trans::__('Everybody'),
                    'description' => Trans::__('Built-in role, automatically granted to every registered user'),
                    'builtin'     => true,
                ];

            case self::ROLE_OWNER:
                return [
                    'label'       => Trans::__('Owner'),
                    'description' => Trans::__('Built-in role, only valid in the context of a resource, and automatically assigned to the owner of that resource.'),
                    'builtin'     => true,
                ];

            default:
                $roles = $this->getDefinedRoles();
                if (isset($roles[$roleName])) {
                    return $roles[$roleName];
                } else {
                    return null;
                }
        }
    }

    /**
     * Gets the roles for a given user. If a content type is specified, the
     * "owner" role is added if appropriate.
     *
     * @param array   $user    An array as returned by Users::getUser()
     * @param Content $content An optional Content object to check ownership
     *
     * @throws \Exception
     *
     * @return array An associative array of roles for the given user
     */
    public function getUserRoles($user, Content $content = null)
    {
        $userRoleNames = $user['roles'];
        if (!is_array($userRoleNames)) {
            throw new \Exception('Expected a user-like array, but the "roles" property is not an array');
        }
        $userRoleNames[] = self::ROLE_EVERYONE;
        if ($content && $content['user'] && $content['user']['id'] === $user['id']) {
            $userRoleNames[] = self::ROLE_OWNER;
        }
        $userRoleNames[] = self::ROLE_OWNER;

        $self = $this;

        return array_combine(
            $userRoleNames,
            array_map(
                function ($roleName) use ($self) {
                    return $self->getRole($roleName);
                },
                $userRoleNames
            )
        );
    }

    /**
     * Gets the roles the current user can manipulate.
     *
     * @param array $currentUser
     *
     * @return string[] list of role names
     */
    public function getManipulatableRoles(array $currentUser)
    {
        $manipulatableRoles = [];

        foreach ($this->getDefinedRoles() as $roleName => $role) {
            if ($this->checkPermission($currentUser['roles'], 'manipulate', 'roles-hierarchy', $roleName)) {
                $manipulatableRoles[] = $roleName;
            }
        }

        return $manipulatableRoles;
    }

    /**
     * Checks if the current user is able to manipulate the given user.
     *
     * @param array $user
     * @param array $currentUser
     *
     * @return bool
     */
    public function isAllowedToManipulate(array $user, array $currentUser)
    {
        return $this->checkPermission($currentUser['roles'], 'manipulate', 'roles-hierarchy', $user);
    }

    /**
     * Low-level permission check. Given a set of available roles, a
     * permission, and an optional content type, this method checks whether
     * the permission may be granted.
     *
     * @param array  $roleNames      An array of effective role names. This must
     *                               include any of the appropriate automatic
     *                               roles, as these are not added at this point.
     * @param string $permissionName Which permission to check
     * @param string $type
     * @param mixed  $item
     *
     * @return bool TRUE if granted, FALSE if not.
     */
    public function checkPermission($roleNames, $permissionName, $type = null, $item = null)
    {
        // Handle BC
        if ($type !== null && $item === null) {
            $item = $type;
            $type = 'contenttype';
        }

        if (is_array($item) && isset($item['username'])) {
            $itemStr = sprintf(' for user "%s"', $item['username']);
        } elseif ($item) {
            $itemStr = " for $item";
        } else {
            $itemStr = '';
        }

        $roleNames = array_unique($roleNames);
        if (in_array(Permissions::ROLE_ROOT, $roleNames)) {
            $this->audit(
                sprintf(
                    'Granting "%s"%s to root user',
                    $permissionName,
                    $itemStr
                )
            );

            return true;
        }
        foreach ($roleNames as $roleName) {
            if ($this->checkRolePermission($roleName, $permissionName, $type ?: 'global', $item)) {
                $this->audit(
                    sprintf(
                        'Granting "%s"%s based on role %s',
                        $permissionName,
                        $itemStr,
                        $roleName
                    )
                );

                return true;
            }
        }
        $this->audit(
            sprintf(
                'Denying "%s"%s; available roles: %s',
                $permissionName,
                $itemStr,
                implode(', ', $roleNames)
            )
        );

        return false;
    }

    /**
     * Checks whether the specified $roleName grants permission $permissionName
     * for the $contenttype in question (NULL for global permissions).
     *
     * @param string $roleName
     * @param string $permissionName
     * @param string $type
     * @param mixed  $item
     *
     * @return bool
     */
    private function checkRolePermission($roleName, $permissionName, $type = 'global', $item = null)
    {
        if ($type === 'global') {
            return $this->checkRoleGlobalPermission($roleName, $permissionName);
        } elseif ($type === 'roles-hierarchy') {
            return $this->checkRoleHierarchyPermission($roleName, $permissionName, $item);
        } elseif ($type === 'contenttype') {
            return $this->checkRoleContentTypePermission($roleName, $permissionName, $item);
        }

        throw new \InvalidArgumentException('Unknown permission type to check');
    }

    /**
     * Check if a given role has the specified permission.
     *
     * @param string $roleName
     * @param string $permissionName
     *
     * @return boolean
     */
    private function checkRoleGlobalPermission($roleName, $permissionName)
    {
        $roles = $this->getRolesByGlobalPermission($permissionName);
        if (!is_array($roles)) {
            $this->app['logger.system']->addInfo("Configuration error: $permissionName is not granted to any roles.", ['event' => 'authentication']);

            return false;
        }

        return in_array($roleName, $roles);
    }

    /**
     * Check if a hierarchy role has a sub-role.
     *
     * @param string       $roleName
     * @param string       $permissionName
     * @param string|array $role
     *
     * @return boolean
     */
    private function checkRoleHierarchyPermission($roleName, $permissionName, $role)
    {
        // Can current user manipulate role?
        if (is_string($role)) {
            $permissions = $this->app['config']->get("permissions/roles-hierarchy/$permissionName/$role", []);

            return in_array($roleName, $permissions);
        }

        // Can current user manipulate user?
        $user = $role;
        foreach ($user['roles'] as $role) {
            $permissions = $this->app['config']->get("permissions/roles-hierarchy/$permissionName/$role", []);
            if (in_array($roleName, $permissions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a role has a specific Contenttype permission.
     *
     * @param string $roleName
     * @param string $permissionName
     * @param string $contenttype
     *
     * @return boolean
     */
    private function checkRoleContentTypePermission($roleName, $permissionName, $contenttype)
    {
        $roles = $this->getRolesByContentTypePermission($permissionName, $contenttype);

        return in_array($roleName, $roles);
    }

    /**
     * Get the list of ContentType permissions available.
     *
     * @return boolean[]
     */
    public function getContentTypePermissions()
    {
        return $this->contentTypePermissions;
    }

    /**
     * Return a list of ContentType permissions that a user has for the ContentType.
     *
     * @param string             $contentTypeSlug
     * @param array|Entity\Users $user
     *
     * @return boolean[]
     */
    public function getContentTypeUserPermissions($contentTypeSlug, $user)
    {
        $permissions = [];
        foreach (array_keys($this->contentTypePermissions) as $contentTypePermission) {
            $permissions[$contentTypePermission] = $this->isAllowed($contentTypePermission, $user, $contentTypeSlug);
        }

        return $permissions;
    }

    /**
     * Lists the roles that would grant the specified global permission.
     *
     * @param string $permissionName
     *
     * @return string[]
     */
    public function getRolesByGlobalPermission($permissionName)
    {
        return $this->app['config']->get("permissions/global/$permissionName");
    }

    /**
     * Gets the configured global permissions.
     *
     * @return array
     */
    public function getGlobalRoles()
    {
        return $this->app['config']->get("permissions/global");
    }

    /**
     * Lists the roles that would grant the specified permission for the
     * specified content type. Sort of a reverse lookup on the permission
     * check.
     *
     * @param string $permissionName
     * @param string $contenttype
     *
     * @return array
     */
    public function getRolesByContentTypePermission($permissionName, $contenttype)
    {
        // Here's how it works:
        // - if a permission is granted through 'contenttype-all', it is effectively granted
        // - if a permission is granted through 'contenttypes/$contenttype', it is effectively granted
        // - if 'contenttypes/$contenttype/$permissionName' is not set, *and* the permission is granted through 'contenttype-default', it is effectively granted
        // - otherwise, the permission is denied
        $overrideRoles = $this->app['config']->get("permissions/contenttype-all/$permissionName");
        if (!is_array($overrideRoles)) {
            $overrideRoles = [];
        }
        $contenttypeRoles = $this->app['config']->get("permissions/contenttypes/$contenttype/$permissionName");
        if (!is_array($contenttypeRoles)) {
            $contenttypeRoles = $this->app['config']->get("permissions/contenttype-default/$permissionName");
        }
        if (!is_array($contenttypeRoles)) {
            $contenttypeRoles = [];
        }
        $effectiveRoles = array_unique(array_merge($overrideRoles, $contenttypeRoles));

        return $effectiveRoles;
    }

    /**
     * Gets the effective roles for a given user.
     * The effective roles include the roles that were explicitly assigned,
     * as well as the built-in automatic roles.
     *
     * @param mixed $user An array or array-access object that contains a
     *                    'roles' key; if no user is given, "guest" access is
     *                    assumed.
     *
     * @return array A list of effective role names for this user.
     */
    public function getEffectiveRolesForUser($user)
    {
        if (isset($user['roles']) && is_array($user['roles'])) {
            $userRoles = $user['roles'];
            $userRoles[] = Permissions::ROLE_EVERYONE;
        } else {
            $userRoles = [];
        }
        $userRoles[] = Permissions::ROLE_ANONYMOUS;

        return $userRoles;
    }

    /**
     * Runs a permission check. Permissions are encoded as strings, where
     * the ':' character acts as a separator for dynamic parts and
     * sub-permissions.
     * Apart from the route-based rules defined in permissions.yml, the
     * following special cases are available:.
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
     * Further, permissions can be combined with the special keywords 'and' and
     * 'or' (case-insensitive), or their symbolic aliases '&' (or '&&') and '|'
     * (or '||'). To override the default precedence (with 'or' binding tighter
     * than 'and'), or to make precedence explicit, use parentheses. Ex.:
     *
     * "contenttype:$contenttype:edit or contenttype:$contenttype:view"
     *
     * @param string               $what      The desired permission, as elaborated upon above.
     * @param mixed                $user      The user to check permissions against.
     * @param string|array|Content $content   Optional: Content object/array or ContentType slug.
     *                                        If specified, $what is taken to be a relative permission (e.g. 'edit')
     *                                        rather than an absolute one (e.g. 'contenttype:pages:edit').
     * @param integer              $contentId Only used if $content is given, to further specifiy the content item.
     *
     * @return boolean TRUE if the permission is granted, FALSE if denied.
     */
    public function isAllowed($what, $user, $content = null, $contentId = null)
    {
        if (is_array($content)) {
            $contenttypeSlug = $content['slug'];
        } elseif ($content instanceof \Bolt\Legacy\Content) {
            $contenttypeSlug = $content->contenttype['slug'];
        } else {
            $contenttypeSlug = $content;
        }

        $this->audit("Checking permission query '$what' for user '{$user['username']}' with contenttype '$contenttypeSlug' and contentid '$contentId'");

        // First, let's see if we have the check in the per-request cache.
        $rqCacheKey = $user['id'] . '//' . $what . '//' . $contenttypeSlug . '//' . $contentId;
        if (isset($this->rqcache[$rqCacheKey])) {
            return $this->rqcache[$rqCacheKey];
        }

        $cacheKey = "_permission_rule:$what";
        if ($this->app['cache']->contains($cacheKey)) {
            $rule = json_decode($this->app['cache']->fetch($cacheKey), true);
        } else {
            $parser = new PermissionParser();
            $rule = $parser->run($what);
            $this->app['cache']->save($cacheKey, json_encode($rule));
        }
        $userRoles = $this->getEffectiveRolesForUser($user);
        $isAllowed = $this->isAllowedRule($rule, $user, $userRoles, $content, $contenttypeSlug, $contentId);

        // Cache for the current request
        $this->rqcache[$rqCacheKey] = $isAllowed;

        return $isAllowed;
    }

    /**
     * Check if a user is allowed a rule 'type'.
     *
     * @param array                $rule
     * @param array                $user
     * @param array                $userRoles
     * @param string|array|Content $content
     * @param string               $contenttypeSlug
     * @param integer              $contentid
     *
     * @throws \Exception
     *
     * @return boolean
     */
    private function isAllowedRule($rule, $user, $userRoles, $content, $contenttypeSlug, $contentid)
    {
        switch ($rule['type']) {
            case PermissionParser::P_TRUE:
                return true;
            case PermissionParser::P_FALSE:
                return false;
            case PermissionParser::P_SIMPLE:
                return $this->isAllowedSingle($rule['value'], $user, $userRoles, $content, $contenttypeSlug, $contentid);
            case PermissionParser::P_OR:
                foreach ($rule['value'] as $subrule) {
                    if ($this->isAllowedRule($subrule, $user, $userRoles, $content, $contenttypeSlug, $contentid)) {
                        return true;
                    }
                }

                return false;
            case PermissionParser::P_AND:
                foreach ($rule['value'] as $subrule) {
                    if (!$this->isAllowedRule($subrule, $user, $userRoles, $content, $contenttypeSlug, $contentid)) {
                        return false;
                    }
                }

                return true;
            default:
                throw new \Exception("Invalid permission check rule of type " . $rule['type'] . ", expected P_SIMPLE, P_AND or P_OR");
        }
    }

    /**
     * Check if a user has a specific role.
     *
     * @param string               $what
     * @param array                $user
     * @param array                $userRoles
     * @param string|array|Content $content
     * @param string               $contenttypeSlug
     * @param integer              $contentId
     *
     * @return boolean
     */
    private function isAllowedSingle($what, $user, $userRoles, $content = null, $contenttypeSlug = null, $contentId = null)
    {
        if ($content !== null) {
            $parts = [
                'contenttype',
                $contenttypeSlug,
                $what,
                $contentId,
            ];
        } else {
            $parts = explode(':', $what);
        }

        switch ($parts[0]) {
            case 'overview':
                $contenttype = null;
                if (isset($parts[1])) {
                    $contenttype = $parts[1];
                }
                if (empty($contenttype)) {
                    if (in_array(Permissions::ROLE_EVERYONE, $userRoles)) {
                        $this->audit("Granting 'overview' for everyone (hard-coded override)");

                        return true;
                    } else {
                        $this->audit("Denying 'overview' for anonymous user (hard-coded override)");

                        return false;
                    }
                } else {
                    $permission = 'view';
                }
                break;

            case 'relatedto':
                $contenttype = isset($parts[1]) ? $parts[1] : '';
                if (empty($contenttype)) {
                    $this->audit("Granting 'relatedto' globally (hard-coded override)");

                    return true;
                } else {
                    $permission = 'view';
                }
                break;

            case 'contenttype':
                $contenttype = $parts[1];
                $permission = $contentId = null;
                if (isset($parts[2])) {
                    $permission = $parts[2];
                }
                if (isset($parts[3])) {
                    $contentId = $parts[3];
                }
                if (empty($permission)) {
                    $permission = 'view';
                }

                // Handle special case for owner.
                if (empty($contentId)) {
                    break;
                }

                // If content was not passed but our rule contains the content
                // we need, lets fetch the Content object @see #3909
                if (is_string($content) || ($contenttype && $contentId)) {
                    $content = $this->app['storage']->getContent("$contenttype/$contentId", ['hydrate' => false]);
                }

                if (intval($content['ownerid']) && (intval($content['ownerid']) === intval($user['id']))) {
                    $userRoles[] = Permissions::ROLE_OWNER;
                }
                break;

            case 'editcontent':
            case 'contentaction':
            case 'deletecontent':
                // editcontent is handled separately in Backend/editcontent()
                // This is because editing content is governed by two separate
                // permissions per content type, "create" and "edit".
                // Similarly, contentaction and deletecontent are handled
                // separately in Backend/contentaction() and
                // Backend/deletecontent(), respectively, because transitions
                // are governed by a set of separate permissions.
                $this->audit("Granting '{$parts[0]}' (hard-coded override)");

                return true;

            default:
                $permission = $what;
                $contenttype = null;
                break;
        }

        return $this->checkPermission($userRoles, $permission, $contenttype);
    }

    /**
     * Gets the required permission for transitioning any content item from
     * one status to another. An empty status value indicates a non-existant
     * item (create/delete).
     *
     * @param string $fromStatus
     * @param string $toStatus
     *
     * @throws \Exception
     *
     * @return string|null The name of the required permission suffix (e.g.
     *                     'publish'), or NULL if no permission is required.
     */
    public function getContentStatusTransitionPermission($fromStatus, $toStatus)
    {
        // No change: no special permission required.
        if ($fromStatus === $toStatus) {
            return null;
        }
        switch ($toStatus) {
            case 'draft':
            case 'held':
                return empty($fromStatus) ? null : 'depublish';

            case 'timed':
            case 'publish':
            case 'published':
                return 'publish';

            default:
                throw new \Exception("Invalid content status transition: '$fromStatus' -> '$toStatus'");
        }
    }

    /**
     * Check to see if a user is allowed to change that status of a Contenttype
     * record to a target status.
     *
     * @param string  $fromStatus
     * @param string  $toStatus
     * @param array   $user
     * @param string  $contenttype
     * @param integer $contentid
     *
     * @return boolean
     */
    public function isContentStatusTransitionAllowed($fromStatus, $toStatus, $user, $contenttype, $contentid = null)
    {
        $perm = $this->getContentStatusTransitionPermission($fromStatus, $toStatus);
        if ($perm === null) {
            // Bypass permission check if no actual transition is to happen
            return true;
        }

        return $this->isAllowed($perm, $user, $contenttype, $contentid);
    }
}
