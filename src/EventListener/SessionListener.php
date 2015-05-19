<?php
namespace Bolt\EventListener;

use Bolt\Logger\FlashLoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class SessionListener implements EventSubscriberInterface
{
    /** @var FlashLoggerInterface */
    protected $flashLogger;
    protected $debug;

    /**
     * SessionListener constructor.
     *
     * @param FlashLoggerInterface $flashLogger
     * @param bool                 $debug
     */
    public function __construct(FlashLoggerInterface $flashLogger, $debug)
    {
        $this->flashLogger = $flashLogger;
        $this->debug = $debug;
    }

    /**
     * Resume the session if it has been started previously or debugging is enabled
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();
        $session = $request->getSession();
        if (($this->debug || $request->hasPreviousSession()) && !$session->isStarted()) {
            $session->start();
        }
    }

    /**
     * Flush flash logger messages to session if it is started
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $session = $event->getRequest()->getSession();
        if ($session instanceof Session && $session->isStarted()) {
            $this->flashLogger->flush($session->getFlashBag());
        }
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array('onRequest', 127), // Right after Session is set in Request
            KernelEvents::RESPONSE => 'onResponse',
        );
    }
}
