<?php

namespace Bolt\EventListener;

use Bolt\Configuration\ResourceManager;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Initialisation checks at the start of boot.
 *
 * @internal Do not extend/call.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BootInitCheckListener implements EventSubscriberInterface
{
    /** @var ResourceManager */
    private $resourceManager;

    /**
     * Constructor.
     *
     * @param ResourceManager $resourceManager
     */
    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    /**
     * Early boot functions.
     *
     * @param GetResponseEvent $event
     *
     * @return Response|null
     */
    public function onBoot(GetResponseEvent $event)
    {
        return $this->resourceManager->getVerifier()->doDatabaseCheck();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onBoot', Application::EARLY_EVENT],
        ];
    }
}
