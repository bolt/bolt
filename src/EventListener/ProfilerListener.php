<?php

namespace Bolt\EventListener;

use Bolt\AccessControl\Token\Token;
use Bolt\Request\ProfilerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Symfony Profiler listener.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ProfilerListener implements EventSubscriberInterface
{
    use ProfilerAwareTrait;

    /** @var SessionInterface */
    private $session;
    /** @var bool */
    private $debug;
    /** @var bool */
    private $debugLoggedOff;

    /**
     * Constructor.
     *
     * @param SessionInterface $session
     * @param bool             $debug
     * @param bool             $debugLoggedOff
     */
    public function __construct(SessionInterface $session, $debug, $debugLoggedOff)
    {
        $this->session = $session;
        $this->debug = $debug;
        $this->debugLoggedOff = $debugLoggedOff;
    }

    /**
     * Request listener to prevent access to profiler routes when debugging is
     * not enabled, or the user is logged off & debugging is not configured to
     * show when logged off.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->isProfilerRequest($request)) {
            return;
        }

        $token = $this->session->isStarted() ? $this->session->get('authentication') : null;
        if ($this->debug && ($token instanceof Token || $this->debugLoggedOff)) {
            return;
        }

        throw new NotFoundHttpException();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest'],
        ];
    }
}
