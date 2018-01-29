<?php

namespace Bolt\Form\FormType;

use Bolt\Helpers\ListMutator;
use Bolt\Storage\Entity;

/**
 * User form DTO.
 *
 * @internal
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class UserData
{
    /** @var int */
    private $id;
    /** @var string */
    private $userName;
    /** @var string */
    private $password;
    /** @var string */
    private $email;
    /** @var string */
    private $displayName;
    /** @var \DateTime */
    private $lastSeen;
    /** @var string */
    private $lastIp;
    /** @var array */
    private $stack = [];
    /** @var bool */
    private $enabled;
    /** @var array */
    private $roles = [];

    /**
     * Constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param Entity\Users $entity
     *
     * @return UserData
     */
    public static function createFromEntity(Entity\Users $entity)
    {
        $user = new self();
        $user->id = $entity->getId();
        $user->userName = $entity->getUserName();
        $user->password = $entity->getPassword();
        $user->email = $entity->getEmail();
        $user->displayName = $entity->getDisplayname();
        $user->lastSeen = $entity->getLastSeen();
        $user->lastIp = $entity->getLastIp();
        $user->stack = $entity->getStack();
        $user->enabled = $entity->getEnabled();
        $user->roles = $entity->getRoles();

        return $user;
    }

    public function applyToEntity(Entity\Users $entity, ListMutator $mutator = null)
    {
        $entity->setUserName($this->userName);
        $entity->setPassword($this->password);
        $entity->setEmail($this->email);
        $entity->setDisplayName($this->displayName);
        $entity->setLastSeen($this->lastSeen);
        $entity->setLastIp($this->lastIp);
        $entity->setStack($this->stack);
        $entity->setEnabled($this->enabled);
        if ($mutator) {
            $entity->setRoles($mutator($entity->getRoles(), $this->roles));
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string $userName
     *
     * @return UserData
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
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
     *
     * @return UserData
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
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
     *
     * @return UserData
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     *
     * @return UserData
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastSeen()
    {
        return $this->lastSeen;
    }

    /**
     * @param \DateTime $lastSeen
     *
     * @return UserData
     */
    public function setLastSeen($lastSeen)
    {
        $this->lastSeen = $lastSeen;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastIp()
    {
        return $this->lastIp;
    }

    /**
     * @param string $lastIp
     *
     * @return UserData
     */
    public function setLastIp($lastIp)
    {
        $this->lastIp = $lastIp;

        return $this;
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
     *
     * @return UserData
     */
    public function setStack($stack)
    {
        $this->stack = $stack;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return UserData
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
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
     *
     * @return UserData
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }
}
