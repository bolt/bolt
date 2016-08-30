<?php
namespace Bolt\EventListener;

use Bolt\Controller;
use Bolt\Exception\BootException;
use Bolt\Request\ProfilerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * HTTP kernel exception routing listener.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class ExceptionListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    /** @var Controller\Exception */
    protected $exceptionController;

    /**
     * Constructor.
     *
     * @param Controller\Exception $exceptionController
     * @param LoggerInterface      $logger
     */
    public function __construct(Controller\Exception $exceptionController, LoggerInterface $logger)
    {
        $this->exceptionController = $exceptionController;
        $this->setLogger($logger);
    }

    /**
     * Handle boot initialisation exceptions.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onBootException(GetResponseForExceptionEvent $event)
    {
        if($this->isProfilerRequest($event->getRequest())) {
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
        if($this->isProfilerRequest($event->getRequest())) {
            return;
        }

        // Log the error message
        $exception = $event->getException();
        $message = $exception->getMessage();
        $level = LogLevel::CRITICAL;
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            $level = LogLevel::WARNING;
        }
        $this->logger->log($level, $message, ['event' => 'exception', 'exception' => $exception]);

        // Get and send the response
        $response = $this->exceptionController->kernelException($event);
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
                ['onKernelException', -8]
            ],
        ];
    }
}
