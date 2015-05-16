<?php
namespace Bolt\EventListener;

use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Whoops\Run;

/**
 * Whoops! listener.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class WhoopsExceptionListener implements EventSubscriberInterface
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
     * Handle errors thrown in the application.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $method = Run::EXCEPTION_HANDLER;
        $exception = $event->getException();

        ob_start();
        $this->app['whoops']->$method($exception);
        $response = ob_get_clean();
        $code = $exception instanceof HttpException ? $exception->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR;

        $event->setResponse(new Response($response, $code));
    }

    /**
     * Return the events to subscribe to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION  => array('onKernelException', 512),
        );
    }
}
