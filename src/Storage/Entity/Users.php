<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for User.
 */
class Users extends Entity
{
    /** @var int */
    protected $id;
    /** @var string */
    protected $username;
    /** @var string */
    protected $password;
    /** @var string */
    protected $email;
    /** @var \DateTime */
    protected $lastseen;
    /** @var string */
    protected $lastip;
    /** @var string */
    protected $displayname;
    /** @var array */
    protected $stack = [];
    /** @var bool */
    protected $enabled;
    /** @var string */
    protected $shadowpassword;
    /** @var string */
    protected $shadowtoken;
    /** @var string */
    protected $shadowvalidity;
    /** @var int */
    protected $failedlogins = 0;
    /** @var \DateTime */
    protected $throttleduntil;
    /** @var array */
    protected $roles = [];

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
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
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
    public function getLastip()
    {
        return $this->lastip;
    }

    /**
     * @param string $lastip
     */
    public function setLastip($lastip)
    {
        $this->lastip = $lastip;
    }

    /**
     * @return string
     */
    public function getDisplayname()
    {
        return $this->displayname;
    }

    /**
     * @param string $displayname
     */
    public function setDisplayname($displayname)
    {
        $this->displayname = $displayname;
    }

    /**
     * @return array
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * @param array $stack
     */
    public function setStack($stack)
    {
        $this->stack = $stack;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return (bool) $this->enabled;
    }

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
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (int) $enabled;
    }

    /**
     * @return string
     */
    public function getShadowpassword()
    {
        return $this->shadowpassword;
    }

    /**
     * @param string $shadowpassword
     */
    public function setShadowpassword($shadowpassword)
    {
        $this->shadowpassword = $shadowpassword;
    }

    /**
     * @return string
     */
    public function getShadowtoken()
    {
        return $this->shadowtoken;
    }

    /**
     * @param string $shadowtoken
     */
    public function setShadowtoken($shadowtoken)
    {
        $this->shadowtoken = $shadowtoken;
    }

    /**
     * @return string
     */
    public function getShadowvalidity()
    {
        return $this->shadowvalidity;
    }

    /**
     * @param string $shadowvalidity
     */
    public function setShadowvalidity($shadowvalidity)
    {
        $this->shadowvalidity = $shadowvalidity;
    }

    /**
     * @return int
     */
    public function getFailedlogins()
    {
        return $this->failedlogins;
    }

    /**
     * @param int $failedlogins
     */
    public function setFailedlogins($failedlogins)
    {
        $this->failedlogins = $failedlogins;
    }

    /**
     * @return \DateTime
     */
    public function getThrottleduntil()
    {
        return $this->throttleduntil;
    }

    /**
     * @param \DateTime $throttleduntil
     */
    public function setThrottleduntil($throttleduntil)
    {
        $this->throttleduntil = $throttleduntil;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     */
    public function setRoles(array $roles)
    {
        $this->roles = array_values(array_unique($roles));
    }
}
