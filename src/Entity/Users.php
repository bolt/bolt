<?php
namespace Bolt\Entity;

use Bolt\Entity\Entity;

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
    protected $stack;
    protected $enabled;
    protected $shadowpassword;
    protected $shadowtoken;
    protected $shadowvalidity;
    protected $failedlogins;
    protected $throttleduntil;
    protected $roles;
    
}
