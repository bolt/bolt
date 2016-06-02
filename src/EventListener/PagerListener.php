<?php

namespace Bolt\EventListener;

use Bolt\Pager\PagerManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PagerListener
 *
 * @author Rix Beck <rix@neologik.hu>
 */
class PagerListener implements EventSubscriberInterface
{
    protected $manager;

    /**
     * PagerListener constructor.
     *
     * @param PagerManager $manager
     */
    public function __construct(PagerManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Resume the session if it has been started previously or debugging is enabled
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->manager->initialize($event->getRequest());
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest'],
            ],
        ];
    }
}
