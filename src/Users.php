<?php

namespace Bolt;

use Bolt\AccessControl\Permissions;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\DBALException;
use Hautelook\Phpass\PasswordHash;
use Silex;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to handle things dealing with users.
 */
class Users
{
    const ANONYMOUS = 0;
    const EDITOR = 2;
    const ADMIN = 4;
    const DEVELOPER = 6;

    /** @var \Doctrine\DBAL\Connection */
    public $db;
    public $config;
    public $usertable;
    public $authtokentable;
    public $users;
    public $session;
    public $currentuser;
    public $allowed;

    /** @var \Silex\Application $app */
    private $app;

    /** @var integer */
    private $hashStrength;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->db = $app['db'];

        $prefix = $this->app['config']->get('general/database/prefix', 'bolt_');

        // Hashstrength has a default of '10', don't allow less than '8'.
        $this->hashStrength = max($this->app['config']->get('general/hash_strength'), 8);

        $this->usertable = $prefix . 'users';
        $this->authtokentable = $prefix . 'authtoken';
        $this->users = array();
        $this->session = $app['session'];

        $this->allowed = array(
            'dashboard'       => self::EDITOR,
            'settings'        => self::ADMIN,
            'login'           => self::ANONYMOUS,
            'logout'          => self::EDITOR,
            'dbcheck'         => self::ADMIN,
            'dbupdate'        => self::ADMIN,
            'clearcache'      => self::ADMIN,
            'prefill'         => self::DEVELOPER,
            'users'           => self::ADMIN,
            'useredit'        => self::ADMIN,
            'useraction'      => self::ADMIN,
            'overview'        => self::EDITOR,
            'editcontent'     => self::EDITOR,
            'editcontent:own' => self::EDITOR,
            'editcontent:all' => self::ADMIN,
            'contentaction'   => self::EDITOR,
            'about'           => self::EDITOR,
            'extensions'      => self::DEVELOPER,
            'files'           => self::EDITOR,
            'files:config'    => self::DEVELOPER,
            'files:theme'     => self::DEVELOPER,
            'files:uploads'   => self::ADMIN,
            'translation'     => self::DEVELOPER,
            'activitylog'     => self::ADMIN,
            'fileedit'        => self::ADMIN
        );
    }

    /**
     * Save changes to a user to the database. (re)hashing the password, if needed.
     *
     * @param array $user
     *
     * @return integer The number of affected rows.
     */
    public function saveUser($user)
    {
        // Make an array with the allowed columns. these are the columns that are always present.
        $allowedcolumns = array(
                'id',
                'username',
                'password',
                'email',
                'lastseen',
                'lastip',
                'displayname',
                'enabled',
                'stack',
                'roles',
            );

        // unset columns we don't need to store.
        foreach (array_keys($user) as $key) {
            if (!in_array($key, $allowedcolumns)) {
                unset($user[$key]);
            }
        }

        if (!empty($user['password']) && $user['password'] != '**dontchange**') {
            $hasher = new PasswordHash($this->hashStrength, true);
            $user['password'] = $hasher->HashPassword($user['password']);
        } else {
            unset($user['password']);
        }

        // make sure the username is slug-like
        $user['username'] = $this->app['slugify']->slugify($user['username']);

        if (empty($user['lastseen'])) {
            $user['lastseen'] = null;
        }

        if (empty($user['enabled']) && $user['enabled'] !== 0) {
            $user['enabled'] = 1;
        }

        if (empty($user['shadowvalidity'])) {
            $user['shadowvalidity'] = null;
        }

        if (empty($user['throttleduntil'])) {
            $user['throttleduntil'] = null;
        }

        if (empty($user['failedlogins'])) {
            $user['failedlogins'] = 0;
        }

        // Make sure the 'stack' is set.
        if (empty($user['stack'])) {
            $user['stack'] = json_encode(array());
        } elseif (is_array($user['stack'])) {
            $user['stack'] = json_encode($user['stack']);
        }

        // Serialize roles array
        if (empty($user['roles']) || !is_array($user['roles'])) {
            $user['roles'] = '[]';
        } else {
            $user['roles'] = json_encode(array_values(array_unique($user['roles'])));
        }

        // Decide whether to insert a new record, or update an existing one.
        if (empty($user['id'])) {
            unset($user['id']);

            return $this->db->insert($this->usertable, $user);
        } else {
            return $this->db->update($this->usertable, $user, array('id' => $user['id']));
        }
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function isValidSession()
    {
        return $this->app['authentication']->isValidSession();
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function checkValidSession()
    {
        return $this->app['authentication']->checkValidSession();
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function getAntiCSRFToken()
    {
        return $this->app['authentication']->getAntiCSRFToken();
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function checkAntiCSRFToken($token = '')
    {
        return $this->app['authentication']->checkAntiCSRFToken($token);
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function getActiveSessions()
    {
        return $this->app['authentication']->getActiveSessions();
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
        $user = $this->getUser($id);

        if (empty($user['id'])) {
            $this->session->getFlashBag()->add('error', Trans::__('That user does not exist.'));

            return false;
        } else {
            $res = $this->db->delete($this->usertable, array('id' => $user['id']));

            if ($res) {
                $this->db->delete($this->authtokentable, array('username' => $user['username']));
            }

            return $res;
        }
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function login($user, $password)
    {
        return $this->app['authentication']->login($user, $password);
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    protected function loginEmail($email, $password)
    {
        return $this->app['authentication']->login($email, $password);
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function loginUsername($username, $password)
    {
        return $this->app['authentication']->login($username, $password);
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function loginAuthtoken()
    {
        return $this->app['authentication']->loginAuthtoken();
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function resetPasswordRequest($username)
    {
        return $this->app['authentication']->resetPasswordRequest($username);
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function resetPasswordConfirm($token)
    {
        return $this->app['authentication']->resetPasswordConfirm($token);
    }

    /**
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function logout()
    {
        return $this->app['authentication']->logout();
    }

    /**
     * Create a stub for a new/empty user.
     *
     * @return array
     */
    public function getEmptyUser()
    {
        $user = array(
            'id'             => '',
            'username'       => '',
            'password'       => '',
            'email'          => '',
            'lastseen'       => '',
            'lastip'         => '',
            'displayname'    => '',
            'enabled'        => '1',
            'shadowpassword' => '',
            'shadowtoken'    => '',
            'shadowvalidity' => '',
            'failedlogins'   => 0,
            'throttleduntil' => ''
        );

        return $user;
    }

    /**
     * Get an array with the current users.
     *
     * @return array
     */
    public function getUsers()
    {
        if (empty($this->users) || !is_array($this->users)) {
            /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
            $queryBuilder = $this->app['db']->createQueryBuilder()
                ->select('*')
                ->from($this->usertable);

            try {
                $this->users = array();
                $tempusers = $queryBuilder->execute()->fetchAll();

                foreach ($tempusers as $user) {
                    $key = $user['username'];
                    $this->users[$key] = $user;
                    $this->users[$key]['password'] = '**dontchange**';

                    $roles = json_decode($this->users[$key]['roles']);
                    if (!is_array($roles)) {
                        $roles = array();
                    }
                    // add "everyone" role to, uhm, well, everyone.
                    $roles[] = Permissions::ROLE_EVERYONE;
                    $this->users[$key]['roles'] = array_unique($roles);
                }
            } catch (\Exception $e) {
                // Nope. No users.
            }
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
        /** @var \Doctrine\DBAL\Query\QueryBuilder $query */
        $query = $this->app['db']->createQueryBuilder()
                        ->select('COUNT(id) as count')
                        ->from($this->usertable);
        $count = $query->execute()->fetch();

        return (integer) $count['count'];
    }

    /**
     * Get a user, specified by ID, username or email address. Return 'false' if no user found.
     *
     * @param integer|string $id
     *
     * @return array
     */
    public function getUser($id)
    {
        // Determine lookup type
        if (is_numeric($id)) {
            $key = 'id';
        } else {
            if (strpos($id, '@') === false) {
                $key = 'username';
            } else {
                $key = 'email';
            }
        }

        /** @var \Doctrine\DBAL\Query\QueryBuilder $queryBuilder */
        $queryBuilder = $this->app['db']->createQueryBuilder()
                        ->select('*')
                        ->from($this->usertable)
                        ->where($key . ' = ?')
                        ->setParameter(0, $id);

        try {
            $user = $queryBuilder->execute()->fetch();
        } catch (\Exception $e) {
            // Nope. No users.
        }

        if (!empty($user)) {
            $user['password'] = '**dontchange**';
            $user['roles'] = json_decode($user['roles']);
            if (!is_array($user['roles'])) {
                $user['roles'] = array();
            }
            // add "everyone" role to, uhm, well, everyone.
            $user['roles'][] = Permissions::ROLE_EVERYONE;
            $user['roles'] = array_unique($user['roles']);

            return $user;
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
        if (is_null($this->currentuser) && $currentuser = $this->app['session']->get('user')) {
            $this->currentuser = $currentuser;
        }

        return $this->currentuser;
    }

    /**
     * Get the current user's property.
     *
     * @return array
     */
    public function getCurrentUserProperty($property)
    {
        $currentuser = $this->getCurrentUser();

        return $currentuser[$property];
    }

    /**
     * Set the current user.
     *
     * @param array $user
     */
    public function setCurrentUser($user)
    {
        $this->currentuser = $user;
    }

    /**
     * Get the username of the current user.
     *
     * @deprecated since v2.3 and to be removed in v3
     *
     * @return string the username of the current user.
     */
    public function getCurrentUsername()
    {
        return $this->getCurrentUserProperty('username');
    }

    /**
     * Check a user's enable status.
     *
     * @param int|bool $id User ID, or false for current user
     *
     * @return boolean
     */
    public function isEnabled($id = false)
    {
        if (!$id) {
            $id = $this->currentuser['id'];
        }

        $query = $this->app['db']->createQueryBuilder()
                        ->select('enabled')
                        ->from($this->usertable)
                        ->where('id = :id')
                        ->setParameters(array(':id' => $id));

        return (boolean) $query->execute()->fetchColumn();
    }

    /**
     * Enable or disable a user, specified by id.
     *
     * @param integer|string $id
     * @param integer        $enabled
     *
     * @return integer
     */
    public function setEnabled($id, $enabled = 1)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            return false;
        }

        $user['enabled'] = $enabled;

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
        $user = $this->getUser($id);

        if (empty($user)) {
            return false;
        }

        return (is_array($user['roles']) && in_array($role, $user['roles']));
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
        $user = $this->getUser($id);

        if (empty($user) || empty($role)) {
            return false;
        }

        // Add the role to the $user['roles'] array
        $user['roles'][] = (string) $role;

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
        $user['roles'] = array_diff($user['roles'], array((string) $role));

        return $this->saveUser($user);
    }

    /**
     * Ensure changes to the user's roles match what the
     * current user has permissions to manipulate.
     *
     * @param string|integer $id       User ID
     * @param array          $newRoles Roles from form submission
     *
     * @return string[] The user's roles with the allowed changes
     */
    public function filterManipulatableRoles($id, array $newRoles)
    {
        $oldRoles = array();
        if ($id && $user = $this->getUser($id)) {
            $oldRoles = $user['roles'];
        }

        $manipulatableRoles = $this->app['permissions']->getManipulatableRoles($this->currentuser);

        $roles = array();
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
     * Check for a user with the 'root' role. There should always be at least one
     * If there isn't we promote the current user.
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
            if (is_array($user['roles']) && in_array('root', $user['roles'])) {
                // We have a 'root' user.
                return true;
            }
        }

        // Make sure the DB is updated. Note, that at this point we currently don't have
        // the permissions to do so, but if we don't, update the DB, we can never add the
        // role 'root' to the current user.
        $this->app['integritychecker']->repairTables();

        // If we reach this point, there is no user 'root'. We promote the current user.
        $this->addRole($this->getCurrentUsername(), 'root');

        // Show a helpful message to the user.
        $this->app['session']->getFlashBag()->add('info', Trans::__("There should always be at least one 'root' user. You have just been promoted. Congratulations!"));
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
        $user = $this->currentuser;

        return $this->app['permissions']->isContentStatusTransitionAllowed($fromStatus, $toStatus, $user, $contenttype, $contentid);
    }

    /**
     * Create a correctly canonicalized value for a field, depending on it's name.
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
     * @deprecated Since Bolt 2.3 and will be removed in Bolt 3.
     */
    public function updateUserLogin($user)
    {
        return $this->app['authentication']->updateUserLogin($user);
    }
}
