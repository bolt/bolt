<?php

namespace Bolt\Events;

use Exception;
use Silex\Application;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

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
    /** @var \Exception */
    private $exception;

    /**
     * Constructor.
     *
     * @param Request   $request
     * @param Exception $exception
     */
    public function __construct(Request $request, \Exception $exception = null)
    {
        $this->exception = $exception;

        $this->clientIp = $request->getClientIp();
        $this->dateTime = $request->server->get('REQUEST_TIME');
        $this->uri = $request->getUri();
        $this->userName = $request->request->get('username');
    }

    /**
     * Return any trapped exceptions.
     *
     * @return Exception
     */
    public function getException()
    {
        return $this->exception;
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
     * Return a DateTime object representing the time the request occurred.
     *
     * @return \DateTime
     */
    public function getDateTime()
    {
        $dt = new \DateTime();

        return $dt->setTimestamp($this->dateTime);
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
     * @return mixed|string
     */
    public function getUserName()
    {
        return $this->userName;
    }
}
