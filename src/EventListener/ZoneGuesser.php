<?php
namespace Bolt\EventListener;

use Bolt\Controller\Zone;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Determine what zone a request is relative to.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ZoneGuesser implements EventSubscriberInterface
{
    /** @var \Silex\Application $app */
    protected $app;

    /**
     * Constructor function.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Kernel request listener callback.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (Zone::get($request)) {
            return;
        }

        $this->setZone($request);
    }

    /**
     * Sets the request's zone if needed and returns it.
     *
     * @param Request $request
     *
     * @return string
     */
    public function setZone(Request $request)
    {
        if ($zone = Zone::get($request)) {
            return $zone;
        }

        $zone = $this->determineZone($request);
        Zone::set($request, $zone);

        return $zone;
    }

    /**
     * Determine the zone and return it
     *
     * @param Request $request
     *
     * @return string
     */
    protected function determineZone(Request $request)
    {
        if ($request->isXmlHttpRequest() || $this->isPathApplicable($request, Zone::ASYNC)) {
            return Zone::ASYNC;
        } elseif ($this->isPathApplicable($request, Zone::BACKEND)) {
            return Zone::BACKEND;
        } else {
            return Zone::FRONTEND;
        }
    }

    /**
     * Check is a request applies to a zone.
     *
     * @param Request $request
     * @param string  $zone
     *
     * @return boolean
     */
    protected function isPathApplicable(Request $request, $zone)
    {
        $prefix = $this->app["controller.$zone.mount_prefix"];

        return $this->startsWith($request->getPathInfo(), $prefix);
    }

    /**
     * Check if a path string starts with a given prefix.
     *
     * @param string $path
     * @param string $prefix
     *
     * @return boolean
     */
    protected function startsWith($path, $prefix)
    {
        return strpos($path, '/' . ltrim($prefix, '/')) === 0;
    }

    /**
     * Return the events to subscribe to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 31], // Right after route is matched
        ];
    }
}
