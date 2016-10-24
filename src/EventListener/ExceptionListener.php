<?php
namespace Bolt\EventListener;

use Bolt\Config;
use Bolt\Controller;
use Bolt\Exception\BootException;
use Bolt\Request\ProfilerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
class ExceptionListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use ProfilerAwareTrait;

    /** @var Config */
    private $config;
    /** @var Controller\Exception */
    protected $exceptionController;

    /**
     * Constructor.
     *
     * @param Config               $config
     * @param Controller\Exception $exceptionController
     * @param LoggerInterface      $logger
     */
    public function __construct(Config $config, Controller\Exception $exceptionController, LoggerInterface $logger)
    {
        $this->config = $config;
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
        $message = $exception->getMessage();

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        // Log the error message
        $level = LogLevel::CRITICAL;
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            $level = LogLevel::WARNING;
        }
        $this->logger->log($level, $message, ['event' => 'exception', 'exception' => $exception]);

        // Get and send the response
        if ($this->isJsonRequest($event->getRequest())) {
            $response = new JsonResponse(
                [
                    'success'   => false,
                    'errorType' => get_class($exception),
                    'code'      => $statusCode,
                    'message'   => $message,
                ]
            );
        } elseif ($this->config->get('general/debug_error_use_symfony')) {
            return null;
        } else {
            $response = $this->exceptionController->kernelException($event);
        }

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

    /**
     * Checks if the request content type is JSON.
     *
     * @param Request $request
     *
     * @return bool
     */
    protected function isJsonRequest(Request $request)
    {
        return strpos($request->headers->get('Content-Type'), 'application/json') === 0;
    }
}
