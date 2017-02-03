<?php
namespace Bolt\EventListener;

use Bolt\Config;
use Bolt\Controller;
use Bolt\Exception\BootException;
use Bolt\Request\ProfilerAwareTrait;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * HTTP kernel exception routing listener.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class ExceptionListener implements EventSubscriberInterface
{
    use ProfilerAwareTrait;

    /** @var Controller\Exception */
    protected $exceptionController;

    /**
     * Constructor.
     *
     * @param Controller\Exception $exceptionController
     */
    public function __construct(Controller\Exception $exceptionController)
    {
        $this->exceptionController = $exceptionController;
    }

    /**
     * Handle boot initialisation exceptions.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onBootException(GetResponseForExceptionEvent $event)
    {
        if ($this->isProfilerRequest($event->getRequest())) {
            return;
        }

        $exception = $event->getException();
        if ($exception instanceof BootException) {
            $event->setResponse($exception->getResponse());
            $event->stopPropagation();
        }
    }

    /**
     * Handle errors thrown in the application.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->isProfilerRequest($event->getRequest())) {
            return;
        }

        $exception = $event->getException();

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        $response = $this->exceptionController->kernelException($event);

        $response->setStatusCode($statusCode);
        $event->setResponse($response);
    }

    /**
     * Return the events to subscribe to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onBootException', Application::EARLY_EVENT],
                ['onKernelException', -8],
            ],
        ];
    }
}
