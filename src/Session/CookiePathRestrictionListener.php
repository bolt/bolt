<?php
namespace Bolt\Session;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * This listener will set the cookie_path for the given session's options
 * to the current request path.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CookiePathRestrictionListener implements EventSubscriberInterface
{
    /** @var OptionsBag */
    protected $options;

    /**
     * CookiePathRestrictionListener constructor.
     *
     * @param OptionsBag $options
     */
    public function __construct(OptionsBag $options)
    {
        $this->options = $options;
    }

    /**
     * Set the cookie_path from the current request's path
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        $request = $event->getRequest();

        $path = $request->getBaseUrl() . $request->getPathInfo();
        $this->options->set('cookie_path', $path);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
        ];
    }
}
