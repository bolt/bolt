<?php
namespace Bolt\Routing\Listener;

use Bolt\Controller\Zone;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * General routing listeners.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class GeneralListener implements EventSubscriberInterface
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
    }

    public function onResponse(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
    }

    /**
     * Return the events to subscribe to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST  => array('onKernelRequest', 31), // Right after route is matched
            KernelEvents::RESPONSE => 'onResponse',
        );
    }
}
