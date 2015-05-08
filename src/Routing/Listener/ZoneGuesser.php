<?php
namespace Bolt\Routing\Listener;

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

        if ($request->isXmlHttpRequest() || $this->isPathApplicable($request, Zone::ASYNC)) {
            Zone::set($request, Zone::ASYNC);
        } elseif ($this->isPathApplicable($request, Zone::BACKEND)) {
            Zone::set($request, Zone::BACKEND);
        } else {
            Zone::set($request, Zone::FRONTEND);
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
        return array(
            KernelEvents::REQUEST => array('onKernelRequest', 31), // Right after route is matched
        );
    }
}
