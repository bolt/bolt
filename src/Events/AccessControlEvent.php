<?php

namespace Bolt\Events;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

/**
 * AccessControl event class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AccessControlEvent extends Event
{
    /** @var string */
    private $clientIp;
    /** @var integer */
    private $dateTime;
    /** @var string */
    private $uri;
    /** @var string */
    private $userName;
    /** @var integer */
    private $reason;
    /** @var boolean */
    private $dispatched = false;

    /**
     * Constructor.
     *
     * NOTE:
     * For security reasons we don't store the request object here so the
     * values in the event remain immutable.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->clientIp = $request->getClientIp();
        $this->dateTime = $request->server->get('REQUEST_TIME');
        $this->uri = $request->getUri();
        $this->userName = $request->request->get('username');
    }

    /**
     * @internal
     *
     * @return AccessControlEvent
     */
    public function setDispatched()
    {
        $this->dispatched = true;

        return $this;
    }

    /**
     * @internal
     *
     * @param integer $reason
     *
     * @return AccessControlEvent
     */
    public function setReason($reason)
    {
        if ($this->dispatched) {
            throw new \RuntimeException('Attempting to set reason after dispatch.');
        }
        $this->reason = $reason;
        $this->dispatched = true;

        return $this;
    }

    /**
     * Return the failure reason code.
     *
     * @return integer
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Return the IP address requesting the access.
     *
     * @return string
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * Return the timestamp the request occurred.
     *
     * @return integer
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * Return the requested URI of the access event.
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Return the given user name of the access event.
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @internal
     *
     * @param string $userName
     */
    public function setUserName($userName)
    {
        if ($this->userName !== null) {
            throw new \RuntimeException('Attempted to change event user name.');
        }
        $this->userName = $userName;
    }
}
