<?php

namespace Bolt\Composer\EventListener;

use Bolt\Composer\PackageManager;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\BufferIO;
use Psr\Log\LoggerInterface;

/**
 * Composer action listeners.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BufferIOListener implements EventSubscriberInterface
{
    /** @var \Bolt\Composer\PackageManager $manager */
    protected $manager;
    /** @var LoggerInterface */
    protected $logger;

    /**
     * Constructor function.
     *
     * @param PackageManager  $manager
     * @param LoggerInterface $logger
     */
    public function __construct(PackageManager $manager, LoggerInterface $logger)
    {
        $this->manager = $manager;
        $this->logger = $logger;
    }

    /**
     * Composer pre-package action events.
     *
     * @param PackageEvent $event
     */
    public function onPrePackage(PackageEvent $event)
    {
        if ($event->getIO() instanceof BufferIO) {
            $this->logger->debug($event->getIO()->getOutput(), ['event' => 'extensions']);
        }
    }

    /**
     * Composer post-package action events.
     *
     * @param PackageEvent $event
     */
    public function onPostPackage(PackageEvent $event)
    {
        if ($event->getIO() instanceof BufferIO) {
            $this->logger->debug($event->getIO()->getOutput(), ['event' => 'extensions']);
        }
    }

    /**
     * Return the events to subscribe to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PackageEvents::PRE_PACKAGE_INSTALL    => 'onPrePackage',
            PackageEvents::PRE_PACKAGE_UPDATE     => 'onPrePackage',
            PackageEvents::PRE_PACKAGE_UNINSTALL  => 'onPrePackage',
            PackageEvents::POST_PACKAGE_INSTALL   => 'onPostPackage',
            PackageEvents::POST_PACKAGE_UPDATE    => 'onPostPackage',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'onPostPackage',
        ];
    }
}
