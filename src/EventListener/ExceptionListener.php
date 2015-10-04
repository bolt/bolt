<?php
namespace Bolt\EventListener;

use Bolt\Controller\Zone;
use Bolt\Render;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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

    /** @var string */
    protected $rootPath;
    /** @var Render */
    protected $render;
    /** @var SessionInterface  */
    protected $session;
    /** @var boolean  */
    protected $isDebug;

    /**
     * ExceptionListener constructor.
     *
     * @param string          $rootPath
     * @param Render          $render
     * @param LoggerInterface $logger
     */
    public function __construct($rootPath, Render $render, LoggerInterface $logger, SessionInterface $session, $isDebug)
    {
        $this->rootPath = $rootPath;
        $this->render = $render;
        $this->setLogger($logger);
        $this->session = $session;
        $this->isDebug = $isDebug;
    }

    /**
     * Handle errors thrown in the application.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        // Log the error message
        $message = $exception->getMessage();
        $level = LogLevel::CRITICAL;
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            $level = LogLevel::WARNING;
        }
        $this->logger->log($level, $message, ['event' => 'exception', 'exception' => $exception]);

        if ($exception instanceof HttpExceptionInterface && !Zone::isBackend($event->getRequest())) {
            $message = "The page could not be found, and there is no 'notfound' set in 'config.yml'. Sorry about that.";
        }

        $context = [
            'class'   => get_class($exception),
            'message' => $message,
            'code'    => $exception->getCode(),
            'trace'   => $this->getSafeTrace($exception),
        ];

        // Note: This uses the template from app/theme_defaults. Not app/view/twig.
        $response = $this->render->render('error.twig', ['context' => $context]);
        $event->setResponse($response);
    }

    /**
     * Get the exception trace that is safe to display publicly
     *
     * @param Exception $exception
     *
     * @return array
     */
    protected function getSafeTrace(Exception $exception)
    {
        if (!$this->isDebug && !($this->session->isStarted() && $this->session->has('authentication'))) {
            return [];
        }

        $trace = $exception->getTrace();
        foreach ($trace as $key => $value) {
            if (!empty($value['file']) && strpos($value['file'], '/vendor/') > 0) {
                unset($trace[$key]['args']);
            }

            // Don't display the full path.
            if (isset($trace[$key]['file'])) {
                $trace[$key]['file'] = str_replace($this->rootPath, '[root]/', $trace[$key]['file']);
            }
        }

        return $trace;
    }

    /**
     * Return the events to subscribe to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -8],
        ];
    }
}
