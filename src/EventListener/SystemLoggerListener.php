<?php

namespace Bolt\EventListener;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Log kernel events to system logger.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class SystemLoggerListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->setLogger($logger);
    }

    /**
     * Log exceptions thrown in the application.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onException(GetResponseForExceptionEvent $event)
    {
        if ($event->isMasterRequest()) {
            return;
        }

        $exception = $event->getException();

        $level = LogLevel::CRITICAL;
        if ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() < 500) {
            $level = LogLevel::WARNING;
        }

        $this->logger->log($level, $exception->getMessage(), ['event' => 'exception', 'exception' => $exception]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            // After exceptions are resolved, before default at -8
            KernelEvents::EXCEPTION => ['onException', -4],
        ];
    }
}
