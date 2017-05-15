<?php

namespace Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class Authtoken extends Entity
{
    /** @var int */
    protected $id;
    /** @var int */
    protected $user_id;
    /** @var string */
    protected $token;
    /** @var string */
    protected $salt;
    /** @var \DateTime */
    protected $lastseen;
    /** @var string */
    protected $ip;
    /** @var string */
    protected $useragent;
    /** @var \DateTime */
    protected $validity;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return \DateTime
     */
    public function getLastseen()
    {
        return $this->lastseen;
    }

    /**
     * @param \DateTime $lastseen
     */
    public function setLastseen($lastseen)
    {
        $this->lastseen = $lastseen;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getUseragent()
    {
        return $this->useragent;
    }

    /**
     * Setter for the user agent string.
     *
     * @param string $useragent
     */
    public function setUseragent($useragent)
    {
        $this->useragent = substr(strip_tags($useragent), 0, 128);
    }

    /**
     * @return \DateTime
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     * @param \DateTime $validity
     */
    public function setValidity($validity)
    {
        $this->validity = $validity;
    }
}
