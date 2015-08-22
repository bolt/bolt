<?php
namespace Bolt\Profiler;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bundle\WebProfilerBundle\EventListener\WebDebugToolbarListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Enables debug toolbar if applicable
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DebugToolbarEnabler implements ServiceProviderInterface, EventSubscriberInterface
{
    /** @var WebDebugToolbarListener */
    protected $listener;
    /** @var EventDispatcherInterface */
    protected $dispatcher;
    /** @var boolean */
    protected $debugLoggedOff;

    /**
     * Enable toolbar if logged in or debug mode is enabled
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!$session = $event->getRequest()->getSession()) {
            return;
        }

        if ($this->debugLoggedOff || ($session->isStarted() && $session->has('authentication'))) {
            $this->dispatcher->addSubscriber($this->listener);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $this->debugLoggedOff = $app['config']->get('general/debug_show_loggedoff', false);
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $this->listener = $app['web_profiler.toolbar.listener'];
        $this->dispatcher = $app['dispatcher'];
        $this->dispatcher->addSubscriber($this);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 126], // Right after SessionListener
        ];
    }
}
