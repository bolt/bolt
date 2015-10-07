<?php
namespace Bolt\AccessControl\Token;

use Bolt\Storage\Entity;

/**
 * Authentication tokens.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Token
{
    /** @var Entity\Users */
    protected $user;
    /** @var Entity\Authtoken */
    protected $token;
    /** @var integer */
    protected $checked;

    /**
     * Constructor.
     *
     * @param Entity\Users     $userEntity
     * @param Entity\Authtoken $tokenEntity
     */
    public function __construct(Entity\Users $userEntity, Entity\Authtoken $tokenEntity)
    {
        $this->user = $userEntity;
        $this->token = $tokenEntity;
        $this->checked = time();
    }

    public function __toString()
    {
        try {
            return $this->token->getToken();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Check if stored user object is enabled.
     *
     * @return boolean|null
     */
    public function isEnabled()
    {
        return $this->user->getEnabled();
    }

    /**
     * Get stored user entity object.
     *
     * @return Entity\Users
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set fresh user entity object.
     *
     * @param Entity\Users $user
     */
    public function setUser(Entity\Users $user)
    {
        $this->user = $user;
    }

    /**
     * Get stored token entity object.
     *
     * @return Entity\Authtoken
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set fresh token entity object.
     *
     * @param Entity\Authtoken $token
     */
    public function setToken(Entity\Authtoken $token)
    {
        $this->token = $token;
    }

    /**
     * Get time of last check against datebase.
     *
     * @return integer
     */
    public function getChecked()
    {
        return $this->checked;
    }

    /**
     * Set time of last database check to now.
     */
    public function setChecked()
    {
        $this->checked = time();
    }
}
