<?php
namespace Bolt\Routing\Listener;

use Bolt\Controller\Zone;
use Bolt\Render;
use Exception;
use Psr\Log\LoggerInterface;
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
class KernelExceptionListener implements EventSubscriberInterface
{
    /** @var string */
    protected $rootPath;
    /** @var Render */
    protected $render;
    /** @var LoggerInterface */
    protected $logger;

    /**
     * KernelExceptionListener constructor.
     *
     * @param string          $rootPath
     * @param Render          $render
     * @param LoggerInterface $logger
     */
    public function __construct($rootPath, Render $render, LoggerInterface $logger)
    {
        $this->rootPath = $rootPath;
        $this->render = $render;
        $this->logger = $logger;
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
        $this->logger->critical($message, array('event' => 'exception', 'exception' => $exception));

        if ($exception instanceof HttpExceptionInterface && Zone::isFrontend($event->getRequest())) {
            $message = "The page could not be found, and there is no 'notfound' set in 'config.yml'. Sorry about that.";
        }

        $context = array(
            'class'   => get_class($exception),
            'message' => $message,
            'code'    => $exception->getCode(),
            'trace'   => $this->getSafeTrace($exception),
        );

        // Note: This uses the template from app/theme_defaults. Not app/view/twig.
        $response = $this->render->render('error.twig', array('context' => $context));
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
        $trace = $exception->getTrace();
        foreach ($trace as $key => $value) {
            if (!empty($value['file']) && strpos($value['file'], '/vendor/') > 0) {
                unset($trace[$key]['args']);
            }

            // Don't display the full path.
            if (isset($trace[$key]['file'])) {
                $trace[$key]['file'] = str_replace($this->rootPath, '[root]', $trace[$key]['file']);
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
        return array(
            KernelEvents::EXCEPTION => array('onKernelException', -8),
        );
    }
}
