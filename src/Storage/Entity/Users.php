<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for User.
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
     * Setter for roles to ensure the array is always unique.
     *
     * @param array $roles
     */
    public function setRoles(array $roles)
    {
        $this->roles = array_unique($roles);
    }
}
