<?php
namespace Bolt\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run as Whoops;

/**
 * Whoops! listener.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class WhoopsListener implements EventSubscriberInterface
{
    /** @var Whoops */
    protected $whoops;
    /** @var SessionInterface */
    protected $session;
    /** @var bool */
    protected $showWhileLoggedOff;

    /**
     * Constructor function.
     *
     * @param Whoops           $whoops
     * @param SessionInterface $session
     * @param bool             $showWhileLoggedOff
     */
    public function __construct(Whoops $whoops, SessionInterface $session, $showWhileLoggedOff)
    {
        $this->whoops = $whoops;
        $this->session = $session;
        $this->showWhileLoggedOff = $showWhileLoggedOff;
    }

    /**
     * Add JSON handler to Whoops if Ajax request
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest() || !$event->getRequest()->isXmlHttpRequest()) {
            return;
        }
        $this->whoops->pushHandler(new JsonResponseHandler());
    }

    /**
     * Handle errors thrown in the application.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $hasUser = $this->session->isStarted() && $this->session->has('authentication');
        if (!$hasUser && !$this->showWhileLoggedOff) {
            return;
        }

        // Register Whoops as an error handler
        $this->whoops->register();

        $exception = $event->getException();

        ob_start();
        $this->whoops->handleException($exception);
        $response = ob_get_clean();
        $code = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        $event->setResponse(new Response($response, $code));
    }

    /**
     * Return the events to subscribe to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST   => 'onRequest',
            KernelEvents::EXCEPTION => ['onKernelException', 512],
        ];
    }
}
