<?php

namespace Bolt;

use Bolt\AccessControl\Permissions;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Class to handle things dealing with users.
 *
 * @deprecated Deprecated since 3.0, to be removed in 4.0.
 */
class Users
{
    /** @internal Visibility will be changed to 'private' for these two in Bolt 3.0 */
    public $users = [];
    public $currentuser;

    /** @var Repository\UsersRepository */
    protected $repository;

    /** @var \Silex\Application $app */
    private $app;

    /**
     * @param Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return Repository\UsersRepository
     */
    private function getRepository()
    {
        if ($this->repository === null) {
            $this->repository = $this->app['storage']->getRepository('Bolt\Storage\Entity\Users');
        }

        return $this->repository;
    }

    /**
     * Save changes to a user to the database. (re)hashing the password, if needed.
     *
     * @param Entity\Users|array $user
     *
     * @return integer The number of affected rows.
     */
    public function saveUser($user)
    {
        if (is_array($user)) {
            $user = new Entity\Users($user);
        }

        // Make sure the username is slug-like
        $user->setUsername($this->app['slugify']->slugify($user->getUsername()));

        // Save the entity
        return $this->getRepository()->save($user);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function isValidSession()
    {
        $request = Request::createFromGlobals();
        $authCookie = $request->cookies->get($this->app['token.authentication.name']);
        if ($authCookie === null) {
            return false;
        }

        return $this->app['access_control']->isValidSession($request->cookies->get($this->app['token.authentication.name']));
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function checkValidSession()
    {
        $request = Request::createFromGlobals();
        $authCookie = $request->cookies->get($this->app['token.authentication.name']);
        if ($authCookie === null) {
            return false;
        }

        return $this->app['access_control']->isValidSession($request->cookies->get($this->app['token.authentication.name']));
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function getAntiCSRFToken()
    {
        return $this->app['csrf']->getToken('bolt')->getValue();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function checkAntiCSRFToken($token = '')
    {
        if (empty($token)) {
            $token = $this->app['request']->get('bolt_csrf_token');
        }

        $token = new CsrfToken('bolt', $token);
        if ($this->app['csrf']->isTokenValid($token)) {
            return true;
        } else {
            $this->app['logger.flash']->warning('The security token was incorrect. Please try again.');

            return false;
        }
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function getActiveSessions()
    {
        return $this->app['access_control']->getActiveSessions();
    }

    /**
     * Remove a user from the database.
     *
     * @param integer $id
     *
     * @return integer The number of affected rows.
     */
    public function deleteUser($id)
    {
        $user = $this->getRepository()->find($id);

        if (!$user) {
            $this->app['logger.flash']->warning(Trans::__('general.phrase.user-not-exist'));

            return false;
        }

        $userName = $user->getUsername();
        if ($result = $this->getRepository()->delete($user)) {
            /** @var Repository\AuthtokenRepository $authtokenRepository */
            $authtokenRepository = $this->app['storage']->getRepository('Bolt\Storage\Entity\Authtoken');
            $authtokenRepository->deleteTokens($userName);
        }

        return $result;
    }

    /**
     * Create a stub for a new/empty user.
     *
     * @return array
     */
    public function getEmptyUser()
    {
        $userEntity = new Entity\Users();

        return $userEntity->toArray();
    }

    /**
     * Get an array with the current users.
     *
     * @return array[]
     */
    public function getUsers()
    {
        if (!empty($this->users)) {
            return $this->users;
        }

        try {
            if (!$tempusers = $this->getRepository()->getUsers()) {
                return [];
            }

            /** @var \Bolt\Storage\Entity\Users $userEntity */
            foreach ($tempusers as $userEntity) {
                $id = $userEntity->getId();
                $this->users[$id] = $userEntity->toArray();
            }
        } catch (TableNotFoundException $e) {
            return [];
        }

        return $this->users;
    }

    /**
     * Test to see if there are users in the user table.
     *
     * @return integer
     */
    public function hasUsers()
    {
        $rows = $this->getRepository()->hasUsers();

        return $rows ? (integer) $rows['count'] : 0;
    }

    /**
     * Get a user, specified by ID, username or email address.
     *
     * @param integer|string $userId
     *
     * @return array|false
     */
    public function getUser($userId)
    {
        // Make sure users have been 'got' already.
        $this->getUsers();

        // In most cases by far, we'll request an ID, and we can return it here.
        if (array_key_exists($userId, $this->users)) {
            return $this->users[$userId];
        }

        // Fallback: See if we can get it by username or email address.
        try {
            if ($userEntity = $this->getRepository()->getUser($userId)) {
                return $userEntity->toArray();
            }
        } catch (TableNotFoundException $e) {
            return false;
        }

        return false;
    }

    /**
     * Get the current user as an array.
     *
     * @return array
     */
    public function getCurrentUser()
    {
        if ($this->currentuser === null) {
            $this->currentuser = $this->app['session']->isStarted() ? $this->app['session']->get('authentication') : null;
            if ($this->currentuser instanceof AccessControl\Token\Token) {
                $this->currentuser = $this->currentuser->getUser()->toArray();
            }
        }

        return $this->currentuser;
    }

    /**
     * Get the current user's property.
     *
     * @param string $property
     *
     * @return string
     */
    public function getCurrentUserProperty($property)
    {
        $currentuser = $this->getCurrentUser();

        return isset($currentuser[$property]) ? $currentuser[$property] : null;
    }

    /**
     * Get the username of the current user.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @return string
     */
    public function getCurrentUsername()
    {
        return $this->getCurrentUserProperty('username');
    }

    /**
     * Check a user's enable status.
     *
     * @param integer|boolean $id User ID, or false for current user
     *
     * @return boolean
     */
    public function isEnabled($id = false)
    {
        $user = $id ? $this->getUser($id) : $this->getCurrentUser();

        return (boolean) $user['enabled'];
    }

    /**
     * Enable or disable a user, specified by id.
     *
     * @param integer|string  $id
     * @param boolean|integer $enabled
     *
     * @return integer
     */
    public function setEnabled($id, $enabled = true)
    {
        if (!$user = $this->getUser($id)) {
            return false;
        }

        $user['enabled'] = (integer) $enabled;

        return $this->saveUser($user);
    }

    /**
     * Check if a certain user has a specific role.
     *
     * @param string|integer $id
     * @param string         $role
     *
     * @return boolean
     */
    public function hasRole($id, $role)
    {
        if (!$user = $this->getUser($id)) {
            return false;
        }

        return in_array($role, $user['roles']);
    }

    /**
     * Add a certain role from a specific user.
     *
     * @param string|integer $id
     * @param string         $role
     *
     * @return boolean
     */
    public function addRole($id, $role)
    {
        if (empty($role) || !$user = $this->getUser($id)) {
            return false;
        }

        // Add the role to the $user['roles'] array
        $user['roles'][] = $role;

        return $this->saveUser($user);
    }

    /**
     * Remove a certain role from a specific user.
     *
     * @param string|integer $id
     * @param string         $role
     *
     * @return boolean
     */
    public function removeRole($id, $role)
    {
        $user = $this->getUser($id);

        if (empty($user) || empty($role)) {
            return false;
        }

        // Remove the role from the $user['roles'] array.
        $user['roles'] = array_diff($user['roles'], [(string) $role]);

        return $this->saveUser($user);
    }

    /**
     * Ensure changes to the user's roles match what the current user has
     * permissions to manipulate.
     *
     * @param string|integer $id       User ID
     * @param array          $newRoles Roles from form submission
     *
     * @return string[] The user's roles with the allowed changes
     */
    public function filterManipulatableRoles($id, array $newRoles)
    {
        $oldRoles = [];
        if ($id && $user = $this->getUser($id)) {
            $oldRoles = $user['roles'];
        }

        $manipulatableRoles = $this->app['permissions']->getManipulatableRoles($this->getCurrentUser());

        $roles = [];
        // Remove roles if the current user can manipulate that role
        foreach ($oldRoles as $role) {
            if ($role === Permissions::ROLE_EVERYONE) {
                continue;
            }
            if (in_array($role, $newRoles) || !in_array($role, $manipulatableRoles)) {
                $roles[] = $role;
            }
        }
        // Add roles if the current user can manipulate that role
        foreach ($newRoles as $role) {
            if (in_array($role, $manipulatableRoles)) {
                $roles[] = $role;
            }
        }

        return array_unique($roles);
    }

    /**
     * Check for a user with the 'root' role.
     *
     * There should always be at least one If there isn't we promote the current
     * user.
     *
     * @return boolean
     */
    public function checkForRoot()
    {
        // Don't check for root, if we're not logged in.
        if ($this->getCurrentUsername() === false) {
            return false;
        }

        // Loop over the users, check if anybody's root.
        foreach ($this->getUsers() as $user) {
            if (in_array('root', $user['roles'])) {
                // We have a 'root' user.
                return true;
            }
        }

        // Make sure the DB is updated. Note, that at this point we currently don't have
        // the permissions to do so, but if we don't, update the DB, we can never add the
        // role 'root' to the current user.
        $this->app['schema']->update();

        // Show a helpful message to the user.
        $this->app['logger.flash']->info(Trans::__('general.phrase.missing-root-jackpot'));

        // If we reach this point, there is no user 'root'. We promote the current user.
        return $this->addRole($this->getCurrentUsername(), 'root');
    }

    /**
     * Runs a permission check. Permissions are encoded as strings, where
     * the ':' character acts as a separator for dynamic parts and
     * sub-permissions.
     *
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
     * @param string $what        The desired permission, as elaborated upon above.
     * @param string $contenttype
     * @param int    $contentid
     *
     * @return bool TRUE if the permission is granted, FALSE if denied.
     */
    public function isAllowed($what, $contenttype = null, $contentid = null)
    {
        $user = $this->getCurrentUser();

        return $this->app['permissions']->isAllowed($what, $user, $contenttype, $contentid);
    }

    /**
     * Check to see if the current user can change the status on the record.
     *
     * @param string $fromStatus
     * @param string $toStatus
     * @param string $contenttype
     * @param string $contentid
     *
     * @return boolean
     */
    public function isContentStatusTransitionAllowed($fromStatus, $toStatus, $contenttype, $contentid = null)
    {
        $user = $this->getCurrentUser();

        return $this->app['permissions']->isContentStatusTransitionAllowed($fromStatus, $toStatus, $user, $contenttype, $contentid);
    }

    /**
     * Create a correctly canonicalized value for a field, depending on its name.
     *
     * @param string $fieldname
     * @param string $fieldvalue
     *
     * @return string
     */
    private function canonicalizeFieldValue($fieldname, $fieldvalue)
    {
        switch ($fieldname) {
            case 'email':
                return strtolower(trim($fieldvalue));

            case 'username':
                return strtolower(preg_replace('/[^a-zA-Z0-9_\\-]/', '', $fieldvalue));

            default:
                return trim($fieldvalue);
        }
    }

    /**
     * Check if a certain field with a certain value doesn't exist already.
     * Depending on the field type, different pre-massaging of the compared
     * values are applied, because what constitutes 'equal' for the purpose
     * of this filtering depends on the field type.
     *
     * @param string  $fieldname
     * @param string  $value
     * @param integer $currentid
     *
     * @return boolean
     */
    public function checkAvailability($fieldname, $value, $currentid = 0)
    {
        foreach ($this->users as $user) {
            if (($this->canonicalizeFieldValue($fieldname, $user[$fieldname]) ===
                 $this->canonicalizeFieldValue($fieldname, $value)) &&
                ($user['id'] != $currentid)
            ) {
                return false;
            }
        }

        // no clashes found, OK!
        return true;
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function updateUserLogin($user)
    {
        return $this->app['access_control']->updateUserLogin($user);
    }
}
