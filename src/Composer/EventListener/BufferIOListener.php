<?php
namespace Bolt\Composer\EventListener;

use Bolt\Composer\PackageManager;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\BufferIO;

/**
 * General routing listeners.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BufferIOListener implements EventSubscriberInterface
{
    /** @var \Bolt\Composer\PackageManager $manager */
    protected $manager;

    /**
     * Constructor function.
     *
     * @param PackageManager $manager
     */
    public function __construct(PackageManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Composer pre-package action events.
     *
     * @param PackageEvent $event
     */
    public function onPrePackage(PackageEvent $event)
    {
        if ($event->getIO() instanceof BufferIO) {
            $this->manager->setOutput($event->getIO()->getOutput());
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
            $this->manager->setOutput($event->getIO()->getOutput());
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
