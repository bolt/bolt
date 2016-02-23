<?php

namespace Bolt\Session;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This connects the client to the session using cookies through the HttpKernel.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class SessionListener implements EventSubscriberInterface
{
    /** @var SessionInterface */
    protected $session;
    /** @var OptionsBag */
    protected $options;

    /**
     * Constructor.
     *
     * @param SessionInterface $session
     * @param OptionsBag       $options
     */
    public function __construct(SessionInterface $session, OptionsBag $options)
    {
        $this->session = $session;
        $this->options = $options;
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

        $request = $event->getRequest();
        $request->setSession($this->session);

        $name = $this->session->getName();
        if ($this->options->getBoolean('restrict_realm')) {
            $name .= md5($request->getHttpHost() . $request->getBaseUrl());
            $this->session->setName($name);
        }

        if ($request->cookies->has($name)) {
            $this->session->setId($request->cookies->get($name));
            $this->session->start();
        }
    }

    /**
     * Add the session cookie to the response if it is started.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest() || !$this->session->isStarted()) {
            return;
        }
        $this->session->save();
        $cookie = $this->generateCookie();
        $event->getResponse()->headers->setCookie($cookie);
    }

    protected function generateCookie()
    {
        $lifetime = $this->options->getInt('cookie_lifetime');
        if ($lifetime !== 0) {
            $lifetime += time();
        }

        return new Cookie(
            $this->session->getName(),
            $this->session->getId(),
            $lifetime,
            $this->options['cookie_path'],
            $this->options['cookie_domain'] ?: null,
            $this->options->getBoolean('cookie_secure'),
            $this->options->getBoolean('cookie_httponly')
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST  => ['onRequest', 128],
            KernelEvents::RESPONSE => ['onResponse', -128],
        ];
    }
}
