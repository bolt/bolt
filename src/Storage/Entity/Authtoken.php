<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 *
 * @method integer   getId()
 * @method string    getUsername()
 * @method string    getToken()
 * @method string    getSalt()
 * @method \DateTime getLastseen()
 * @method string    getIp()
 * @method string    getUseragent()
 * @method string    getValidity()
 * @method setId($id)
 * @method setUsername($username)
 * @method setToken($token)
 * @method setSalt($salt)
 * @method setLastseen($lastseen)
 * @method setIp($ip)
 * @method setUseragent($useragent)
 * @method setValidity($validity)
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
