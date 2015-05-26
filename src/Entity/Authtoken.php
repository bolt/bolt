<?php
namespace Bolt\Entity;

use Bolt\Entity\Entity;

/**
 * Entity for Auth Tokens.
 */
class Authtoken extends Entity
{
    
    protected $id;
    protected $username;
    protected $token;
    protected $salt;
    protected $lastseen;
    protected $ip;
    protected $useragent;
    protected $validity;

}
