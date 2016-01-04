<?php

namespace Bolt\EventListener;
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
    protected $managerFactory;

    /**
     * PagerListener constructor.
     *
     * @param \Closure $pagerManagerFactory
     */
    public function __construct(\Closure $pagerManagerFactory)
    {
        $this->managerFactory = $pagerManagerFactory;
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

        /** @var $manager \Bolt\Pager\PagerManager */
        $manager = $this->managerFactory->__invoke();
        $manager->initialize($event->getRequest());
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
