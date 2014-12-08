<?php

namespace Bolt;

use Bolt\Translation\Translator as Trans;

/**
 * This class implements role-based permissions.
 */
class Permissions
{

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

    /**
     * A special role that is used to tag the viewers of a resource when some
     * field is defined in database for some contenttype
     * granting possibility to set percontent permissions in addtion
     * of per contenttype permissions.
     */
    const ROLE_VIEWERS = 'viewers';

    private $app;

    // per-request permission cache
    private $rqcache;

    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
        $this->rqcache = array();
    }

    /**
     * Write an entry to the permission audit log
     */
    private function audit($msg)
    {
        // For now, just log the message.
        switch ($this->app['config']->get('general/debug_permission_audit_mode')) {
            case 'error-log':
                error_log($msg);
                break;
            default:
                // ignore; no audit logging
                break;
        }
    }

    /**
     * Gets a list of all the roles that can be assigned to users explicitly.
     * This includes all the custom roles from permissions.yml, plus the
     * special 'root' role, but not the special roles 'anonymous', 'everyone',
     * and 'owner' (these are assigned automatically).
     */
    public function getDefinedRoles()
    {
        $roles = $this->app['config']->get('permissions/roles');
        $roles[self::ROLE_ROOT] = array(
            'label' => 'Root',
            'description' => Trans::__('Built-in superuser role, automatically grants all permissions'),
            'builtin' => true
        );

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
    public function getRole($roleName)
    {
        switch ($roleName) {
            case self::ROLE_ANONYMOUS:
                return array(
                    'label' => Trans::__('Anonymous'),
                    'description' => Trans::__('Built-in role, automatically granted at all times, even if no user is logged in'),
                    'builtin' => true,
                );

            case self::ROLE_EVERYONE:
                return array(
                    'label' => Trans::__('Everybody'),
                    'description' => Trans::__('Built-in role, automatically granted to every registered user'),
                    'builtin' => true,
                );

            case self::ROLE_OWNER:
                return array(
                    'label' => Trans::__('Owner'),
                    'description' => Trans::__('Built-in role, only valid in the context of a resource, and automatically assigned to the owner of that resource.'),
                    'builtin' => true,
                );

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
     * @param  array      $user    An array as returned by Users::getUser()
     * @param  Content    $content An optional Content object to check ownership
     * @throws \Exception
     * @return array      An associative array of roles for the given user
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

        return
            array_combine(
                $userRoleNames,
                array_map(
                    function ($roleName) {
                        return $this->getRole($roleName);
                    },
                    $userRoleNames
                )
            );
    }

    /**
     * Low-level permission check. Given a set of available roles, a
     * permission, and an optional content type, this method checks whether
     * the permission may be granted.
     * @param  array  $roleNames      An array of effective role names. This must
     *                                include any of the appropriate automatic
     *                                roles, as these are not added at this point.
     * @param  string $permissionName Which permission to check
     * @param  string $contenttype
     * @return bool   TRUE if granted, FALSE if not.
     */
    public function checkPermission($roleNames, $permissionName, $contenttype = null, $contentid = null, $content = null, $user = null)
    {
        // ROLE_ROOT has always access to everything
        $roleNames = array_unique($roleNames);
        if (in_array(Permissions::ROLE_ROOT, $roleNames)) {
                $this->audit(
                    "Granting '$permissionName' " .
                    ($contenttype ? "for $contenttype " : "") .
                    ($contenttype ? "for contenttype: $contenttype " : "") .
                    ($contenttype ? "with id: $contentid " : "") .
                    "to root user with username: '".$user['username']."'"
                );

                return true;
        }
        if(isset($contenttype)) {
            /*
                We want to know who can access to that content or list of contents from same content type.
                First we retrieve permissions from config according to:
                  - $permissionName which could be frontend or more complex $what string
                  - $contenttype: 'pages', 'entries', 'showcases' by default plus all manually added in contenttypes.yml
                  - $contentid should be the id to reference the content. Could be the slug sometimes or never, no idea.
                Then if $user is a valid connected user
                  we check if its own roles grant him access to this content type or specific content 
                  or if he is owner as users have always access to all contents they own.
                  return true for granted or false for refused
                else we check if this content is granted to ROLE_ANONYMOUS
                  return true for granted or false for refused
            */
            $contentRoles = array_unique($this->getRolesByContentTypePermission($permissionName, $contenttype, $contentid, $content));

            if(isset($user)) {
                // to have access our user must belong to one role which is also in content roles.
                foreach($contentRoles as $contentRole) {
                    if(in_array($contentRole, $user['roles'])) {
                        $grantingRoles[] = $contentRole;
                    }
                }

                // or user has to be owner of the content:
                if(intval($content->values['ownerid'])
                && intval($content->values['ownerid']) === intval($user['id'])
                ) {
                    $grantingRoles[] = Permissions::ROLE_OWNER;
                }

                // user have access if $grantingRoles exists
                if(is_array($grantingRoles)) {
                    $this->audit(
                        "Granting '$permissionName' " .
                        ($contenttype ? "for $contenttype " : "") .
                        ($contenttype ? "with id: $contentid " : "") .
                        "based on role '".implode(', ', $grantingRoles)."' " .
                        "to user with username: '".$user['username']."' " .
                        "when content roles were: '".implode(', ', $contentRoles)."'"
                    );
                    return true;
                }

                // or if content is granted for ROLE_ANONYMOUS
                // this could have been managed by next case but logging would have been complex.
                if(in_array(Permissions::ROLE_ANONYMOUS, $contentRoles)) {
                    $this->audit(
                        "Granting '$permissionName' " .
                        ($contenttype ? "for $contenttype " : "") .
                        ($contenttype ? "with id: $contentid " : "") .
                        "based on role Permissions::ROLE_ANONYMOUS " .
                        "to user with username: '".$user['username']."' " .
                        "when content roles were: '".implode(', ', $contentRoles)."'"
                    );
                    return true;
                }
            } else {
                if(in_array(Permissions::ROLE_ANONYMOUS, $contentRoles)) {
                    $this->audit(
                        "Granting '$permissionName' " .
                        ($contenttype ? "for $contenttype " : "") .
                        ($contenttype ? "with id: $contentid " : "") .
                        "based on role Permissions::ROLE_ANONYMOUS " .
                        "to not connected user."
                    );
                    return true;
                }
            }
        } else {

        // keeping usual check if no contenttype given
            foreach ($roleNames as $roleName) {
                if ($this->checkRolePermission($roleName, $permissionName, $contenttype, $contentid, $content)) {
                    $this->audit(
                        "Granting '$permissionName' " .
                        ($contenttype ? "for $contenttype " : "") .
                        "based on role $roleName"
                    );

                    return true;
                }
            }
        }

        $this->audit(
            "Denying '$permissionName' " .
            ($contenttype ? "for $contenttype" : "") .
            ($contenttype ? " with id: $contentid" : "") .
            "; user roles were: " . implode(', ', $roleNames) .
            ($contenttype ? " and content roles were: " . implode(', ', $contentRoles) : "")
        );

        return false;
    }

    /**
     * Checks whether the specified $roleName grants permission $permissionName
     * for the $contenttype in question (NULL for global permissions).
     */
    private function checkRolePermission($roleName, $permissionName, $contenttype = null, $contentid = null, $content = null)
    {
        if ($contenttype === null) {
            return $this->checkRoleGlobalPermission($roleName, $permissionName);
        } else {
            return $this->checkRoleContentTypePermission($roleName, $permissionName, $contenttype, $contentid, $content);
        }
    }

    private function checkRoleGlobalPermission($roleName, $permissionName)
    {
        $roles = $this->getRolesByGlobalPermission($permissionName);
        if (!is_array($roles)) {
            error_log("Configuration error: $permissionName is not granted to any roles.");

            return false;
        }

        return in_array($roleName, $roles);
    }

    private function checkRoleContentTypePermission($roleName, $permissionName, $contenttype, $contentid = null, $content = null)
    {
        $roles = $this->getRolesByContentTypePermission($permissionName, $contenttype);

        return in_array($roleName, $roles);
    }

    /**
     * Lists the roles that would grant the specified global permission.
     */
    public function getRolesByGlobalPermission($permissionName)
    {
        return $this->app['config']->get("permissions/global/$permissionName");
    }

    /**
     * Gets the configured global roles.
     */
    public function getGlobalRoles()
    {
        return $this->app['config']->get("permissions/global");
    }

    /**
     * Lists the roles that would grant the specified permission for the
     * specified content type. Sort of a reverse lookup on the permission
     * check.
     */
    public function getRolesByContentTypePermission($permissionName, $contenttype, $contentid = null, $content = null)
    {
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
        /**
         * Only for frontend. For now at least.
         * If ROLE_VIEWERS is set there is two cases:
         * - $contentid is set -> we get viewers field content
         * - $contentid is not set -> we replace 'viewers' with 'anonymous' in $contenttypeRoles
         * to grant access to list pages (ex: http://your.site.tld/pages)
         */
        if(strval($permissionName) == 'frontend' && is_array($contenttypeRoles) && in_array(self::ROLE_VIEWERS, $contenttypeRoles)) {
            if(isset($contentid) || $content instanceof \Bolt\Content) {
                // we add roles from current $content to $contenttypeRoles
                $contenttypeRoles = $this->getRolesByContentPermission($permissionName, $contenttype, $contentid, $contenttypeRoles, $content);
            } else {
                // if not called from isProtected() we force ROLE_ANYNOMOUS in place of ROLE_VIEWERS
                if(strval($content) != 'no_ROLE_ANONYMOUS') {
                    // force anonymous for content types content (ie when accessing /pages/ or /entries/
                    // In that case content filtering will be performed during lists creation.
                    $contenttypeRoles = array_replace($contenttypeRoles,
                                            array_fill_keys(
                                                array_keys($contenttypeRoles, self::ROLE_VIEWERS),
                                                self::ROLE_ANONYMOUS
                                            )
                                        );
                }
            }
        }
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
     * @param  mixed $user An array or array-access object that contains a
     *                     'roles' key; if no user is given, "guest" access is
     *                     assumed.
     * @return array A list of effective role names for this user.
     */
    public function getEffectiveRolesForUser($user)
    {
        if (isset($user['roles']) && is_array($user['roles'])) {
            $userRoles = $user['roles'];
            $userRoles[] = Permissions::ROLE_EVERYONE;
        } else {
            $userRoles = array();
        }
        $userRoles[] = Permissions::ROLE_ANONYMOUS;

        $userRoles = array_unique($userRoles);

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
     * Further, permissions can be combined with the special keywords 'and' and
     * 'or' (case-insensitive), or their symbolic aliases '&' (or '&&') and '|'
     * (or '||'). To override the default precedence (with 'or' binding tighter
     * than 'and'), or to make precedence explicit, use parentheses. Ex.:
     *
     * "contenttype:$contenttype:edit or contenttype:$contenttype:view"
     *
     * @param  string $what        The desired permission, as elaborated upon above.
     * @param  mixed  $user        The user to check permissions against.
     * @param  string $contenttype Optional: Content type slug. If specified,
     *                             $what is taken to be a relative permission (e.g. 'edit')
     *                             rather than an absolute one (e.g. 'contenttype:pages:edit').
     * @param  int    $contentid   Only used if $contenttype is given, to further
     *                             specifiy the content item.
     * @return bool   TRUE if the permission is granted, FALSE if denied.
     */
    public function isAllowed($what, $user, $contenttype = null, $contentid = null, $content = null)
    {
        // $contenttype must be a string, not an array.
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        $this->audit("Checking permission query '$what' for user '{$user['username']}' with contenttype '$contenttype' and contentid '$contentid'");

        // First, let's see if we have the check in the per-request cache.
        $rqCacheKey = $user['id'] . '//' . $what . '//' . $contenttype . '//' . $contentid;
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

        if(!isset($contentid)) {
          $what = explode(':', $what);
          if(isset($what[3])) {
              $contentid = $what[3];
          }
        }
        $isAllowed = $this->isAllowedRule($rule, $user, $userRoles, $contenttype, $contentid, $content);

        // Cache for the current request
        $this->rqcache[$rqCacheKey] = $isAllowed;

        return $isAllowed;
    }

    private function isAllowedRule($rule, $user, $userRoles, $contenttype, $contentid, $content)
    {
        switch ($rule['type']) {
            case PermissionParser::P_TRUE:
                return true;
            case PermissionParser::P_FALSE:
                return false;
            case PermissionParser::P_SIMPLE:
                return $this->isAllowedSingle($rule['value'], $user, $userRoles, $contenttype, $contentid, $content);
            case PermissionParser::P_OR:
                foreach ($rule['value'] as $subrule) {
                    if ($this->isAllowedRule($subrule, $user, $userRoles, $contenttype, $contentid, $content)) {
                        return true;
                    }
                }

                return false;
            case PermissionParser::P_AND:
                foreach ($rule['value'] as $subrule) {
                    if (!$this->isAllowedRule($subrule, $user, $userRoles, $contenttype, $contentid, $content)) {
                        return false;
                    }
                }

                return true;
            default:
                throw new \Exception("Invalid permission check rule of type " . $rule['type'] . ", expected P_SIMPLE, P_AND or P_OR");
        }
    }

    private function isAllowedSingle($what, $user, $userRoles, $contenttype = null, $contentid = null, $content = null)
    {
        if ($contenttype) {
            $parts = array(
                        'contenttype',
                        $contenttype,
                        $what,
                        $contentid,
                        );
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
                $permission = $contentid = null;
                if (isset($parts[2])) {
                    $permission = $parts[2];
                }
                if (isset($parts[3])) {
                    $contentid = $parts[3];
                }
                if (empty($permission)) {
                    $permission = 'view';
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

        return $this->checkPermission($userRoles, $permission, $contenttype, $contentid, $content, $user);
    }

    /**
     * Gets the required permission for transitioning any content item from
     * one status to another. An empty status value indicates a non-existant
     * item (create/delete).
     * @param $fromStatus
     * @param $toStatus
     * @throws \Exception
     * @return mixed      The name of the required permission suffix (e.g.
     *                    'publish'), or NULL if no permission is required.
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
                if (empty($fromStatus)) {
                    return null;
                } else {
                    return 'depublish';
                }
                break;
            case 'timed':
            case 'published':
                return 'publish';
            default:
                throw new \Exception("Invalid content status transition: $fromStatus -> $toStatus");
        }
    }

    public function isContentStatusTransitionAllowed($fromStatus, $toStatus, $user, $contenttype, $contentid = null)
    {
        $perm = $this->getContentStatusTransitionPermission($fromStatus, $toStatus);
        if ($perm === null) {
            // Bypass permission check if no actual transition is to happen
            return true;
        }

        return $this->isAllowed($perm, $user, $contenttype, $contentid, $content);
    }

    /**
     * Addition for per content permissions
     */

    // for it's called only once without $content.
    public function isProtected($permissionName, $contenttype, $content = null)
    {
        // small tweak to avoid getRolesByContentTypePermission() to replace ROLE_VIEWERS by ROLE_ANONYMOUS
        $content = 'no_ROLE_ANONYMOUS';
        $effectiveRoles = $this->getRolesByContentTypePermission($permissionName, $contenttype, null, $content);
        if($this->app['config']->get('general/frontend_permission_checks')
        && is_array($effectiveRoles)
        && in_array(self::ROLE_VIEWERS, $effectiveRoles)
        ) {
            return true;
        } else {
            return false;
        }
    }

    // For now this function is launched only if $permissionName == 'frontend'.
    public function getRolesByContentPermission($permissionName, $content, $contenttype, $contentid, $contenttypeRoles)
    {
        // should be always set but...
        if(isset($contentid) || $content instanceof \Bolt\Content) {
            // using returnsingle in getContent() to avoid addition of filter in WHERE clause.
            if(!$content instanceof \Bolt\Content)
                $content = $this->app['storage']->getContent($contenttype.'/'.$contentid, array('hydrate' => false, 'returnsingle' => true));

            // if $content[self::ROLE_VIEWERS] is null we set it to self::ROLE_ANONYMOUS.
            // This to avoid the need to change the whole in case user set self::ROLE_VIEWERS on an already filed database
            if(!isset($content[self::ROLE_VIEWERS])) $content[self::ROLE_VIEWERS] = self::ROLE_ANONYMOUS;

            // we clean and explode 'viewers' field in an array
            $content[self::ROLE_VIEWERS] = str_replace(" ", "", $content[self::ROLE_VIEWERS]);
            $contentRoles = explode(",", $content[self::ROLE_VIEWERS]);

            // remove 'ROLE_VIEWERS' keyword from permissions. We don't want user defined role named 'ROLE_VIEWERS'
            foreach($contenttypeRoles as $contenttypeRole) {
                if($contenttypeRole != Permissions::ROLE_VIEWERS)
                    $tmpRoles[] = $contenttypeRole;
            }

            // merging $contentRoles which roles declared on content level and
            // $tmpRoles which is roles declared on content type level minus 'ROLE_VIEWERS'
            if(is_array($tmpRoles))
                $contentRoles = array_unique(array_merge($contentRoles, $tmpRoles));
        }

        return $contentRoles;
    }
}
