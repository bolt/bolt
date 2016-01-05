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
        $request = $event->getRequest();
        // because of vaious type of requests fires event (Frontend/Async/Thumbs/etc.)
        // we're just listening to which has page parameter
        if (PagerManager::isPagingRequest($request)) {
            /** @var $manager \Bolt\Pager\PagerManager */
            $manager = $this->managerFactory->__invoke();
            $manager->initialize($event->getRequest());
        }
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
