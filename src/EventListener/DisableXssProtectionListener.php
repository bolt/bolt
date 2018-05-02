<?php

namespace Bolt\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Disable browser's XSS detection for given routes.
 *
 * These routes should still verify the request data with a CSRF token.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DisableXssProtectionListener implements EventSubscriberInterface
{
    /** @var string[] */
    protected $routes;

    /**
     * Constructor.
     *
     * @param string[] $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Add X-XSS-Protection header if route matches, request is unsafe, and response has body.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->isMethodSafe(false)) {
            return;
        }

        // In case we're using 'preview', the `_route` has been set to `contentlink`, because we're
        // viewing a single page, but `_original_route` will be `preview`, which should take
        // precedence, if set. See PR https://github.com/bolt/bolt/pull/7458
        /** @deprecated since 3.4 to be removed in 4.0 */
        $route = $request->attributes->get('_internal_route', $request->attributes->get('_route'));

        if (!in_array($route, $this->routes)) {
            return;
        }

        $response = $event->getResponse();

        if ($response->isRedirection() || $response->isEmpty() || $response->isInformational()) {
            return;
        }

        $response->headers->set('X-XSS-Protection', 0);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }
}
