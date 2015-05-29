<?php

namespace Bolt\Session;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This connects the client to the session using cookies through the HttpKernel.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class StorageListener implements EventSubscriberInterface
{
    /** @var SessionStorage */
    protected $storage;

    /**
     * Constructor.
     *
     * @param SessionStorage $storage
     */
    public function __construct(SessionStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Set the session ID from request cookies
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $cookies = $event->getRequest()->cookies;
        $name = $this->storage->getName();
        if ($cookies->has($name)) {
            $this->storage->setId($cookies->get($name));
        }
    }

    /**
     * Add the session cookie to the response if it is started.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest() || !$this->storage->isStarted()) {
            return;
        }
        $this->storage->save();
        $cookie = $this->storage->generateCookie();
        $event->getResponse()->headers->setCookie($cookie);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 128],
            KernelEvents::RESPONSE => ['onResponse', -128],
        ];
    }
}
