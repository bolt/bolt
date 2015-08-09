<?php
namespace Bolt\EventListener;

use Bolt\Config;
use Bolt\Storage;
use Doctrine\DBAL\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Storage listeners.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class StorageEventListener implements EventSubscriberInterface
{
    /** @var \Bolt\Storage $storage */
    protected $storage;
    /** @var \Bolt\Config $config */
    protected $config;

    /**
     * Constructor function.
     *
     * @param Storage $storage
     * @param Config  $config
     */
    public function __construct(Storage $storage, Config $config)
    {
        $this->storage = $storage;
        $this->config = $config;
    }

    /**
     * Kernel request listener callback.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $contenttypes = $this->config->get('contenttypes', array());

        foreach ($contenttypes as $contenttype) {
            $contenttype = $this->storage->getContentType($contenttype['slug']);

            // Check if we need to 'publish' any 'timed' records, or 'depublish' any expired records.
            $this->storage->publishTimedRecords($contenttype);
            $this->storage->depublishExpiredRecords($contenttype);
        }
    }

    /**
     * Return the events to subscribe to.
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST  => array('onKernelRequest', 31)
        );
    }
}
