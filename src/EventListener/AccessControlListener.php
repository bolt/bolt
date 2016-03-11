<?php

namespace Bolt\EventListener;

use Bolt\Filesystem\FilesystemInterface;
use Bolt\Session\SessionStorage;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * AccessControl listener class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AccessControlListener implements EventSubscriberInterface
{
    /** @var FilesystemInterface */
    protected $filesystem;
    /** @var SessionStorage*/
    protected $sessionStorage;
    /** @var \Bolt\Storage\EntityManager */
    protected $em;

    /**
     * Constructor.
     *
     * @param FilesystemInterface $filesystem
     * @param SessionStorage      $sessionStorage
     * @param EntityManager       $em
     */
    public function __construct(FilesystemInterface $filesystem, SessionStorage $sessionStorage, EntityManager $em)
    {
        $this->filesystem = $filesystem;
        $this->sessionStorage = $sessionStorage;
        $this->em = $em;
    }

    public static function getSubscribedEvents()
    {
    }
}
