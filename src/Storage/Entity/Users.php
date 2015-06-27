<?php
namespace Bolt\Storage\Entity;

use Bolt\AccessControl\Permissions;

/**
 * Entity for User.
 *
 * @method integer   getId()
 * @method string    getUsername()
 * @method string    getPassword()
 * @method string    getEmail()
 * @method \DateTime getLastseen()
 * @method string    getLastip()
 * @method string    getDisplayname()
 * @method array     getStack()
 * @method string    getShadowpassword()
 * @method string    getShadowtoken()
 * @method string    getShadowvalidity()
 * @method intger    getFailedlogins()
 * @method \DateTime getThrottleduntil()
 * @method setId($id)
 * @method setUsername($username)
 * @method setPassword($password)
 * @method setEmail($email)
 * @method setLastseen(\DateTime $lastseen)
 * @method setLastip($lastip)
 * @method setDisplayname($displayname)
 * @method setStack(array $stack)
 * @method setShadowpassword($shadowpassword)
 * @method setShadowtoken($shadowtoken)
 * @method setShadowvalidity($shadowvalidity)
 * @method setFailedlogins($failedlogins)
 * @method setThrottleduntil(\DateTime $throttleduntil)
 */
class Users extends Entity
{
    protected $id;
    protected $username;
    protected $password;
    protected $email;
    protected $lastseen;
    protected $lastip;
    protected $displayname;
    protected $stack = [];
    protected $enabled = 1;
    protected $shadowpassword = '';
    protected $shadowtoken = '';
    protected $shadowvalidity;
    protected $failedlogins = 0;
    protected $throttleduntil;
    protected $roles = [];

    /**
     * Getter for enabled flag
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return (bool) $this->enabled;
    }

    /**
     * Setter for enabled flag
     *
     * @param string|integer|boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (int) $enabled;
    }

    /**
     * Getter for roles to ensure Permissions::ROLE_EVERYONE always exists.
     *
     * @return array
     */
    public function getRoles()
    {
        if (!in_array(Permissions::ROLE_EVERYONE, $this->roles)) {
            $this->roles[] = Permissions::ROLE_EVERYONE;
        }

        return $this->roles;
    }

    /**
     * Setter for roles to ensure the array is always unique.
     *
     * @param array $roles
     */
    public function setRoles(array $roles)
    {
        $this->roles = array_unique($roles);
    }
}
