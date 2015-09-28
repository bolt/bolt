<?php
namespace Bolt\EventListener;

use Bolt\Controller\Zone;
use Bolt\Translation\Translator as Trans;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * General routing listeners.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class GeneralListener implements EventSubscriberInterface
{
    /** @var \Silex\Application $app */
    protected $app;

    /**
     * Constructor function.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Kernel request listener callback.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $this->mailConfigCheck($request);
        $this->app['access_control']->setRequest($request);
    }

    /**
     * Kernel response listener callback.
     *
     * @param FilterResponseEvent $event
     */
    public function onResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $this->setFrameOptions($request, $response);
    }

    /**
     * No Mail transport has been set. We should gently nudge the user to set
     * the mail configuration.
     *
     * For now, we only pester the user, if an extension needs to be able to
     * send mail, but it's not been set up.
     *
     * @see: the issue at https://github.com/bolt/bolt/issues/2908
     *
     * @param Request $request
     */
    protected function mailConfigCheck(Request $request)
    {
        if (!$request->hasPreviousSession()) {
            return;
        }

        if ($this->app['users']->getCurrentuser() && !$this->app['config']->get('general/mailoptions') && $this->app['extensions']->hasMailSenders()) {
            $error = "One or more installed extensions need to be able to send email. Please set up the 'mailoptions' in config.yml.";
            $this->app['logger.flash']->error(Trans::__($error));
        }
    }

    /**
     * Set the 'X-Frame-Options' headers to prevent click-jacking, unless
     * specifically disabled. Backend only!
     *
     * @param Request  $request
     * @param Response $response
     */
    protected function setFrameOptions(Request $request, Response $response)
    {
        if (Zone::isBackend($request) && $this->app['config']->get('general/headers/x_frame_options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Frame-Options', 'SAMEORIGIN');
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
            KernelEvents::REQUEST  => ['onKernelRequest', 31], // Right after route is matched
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }
}
