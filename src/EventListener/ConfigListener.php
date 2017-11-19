<?php

namespace Bolt\EventListener;

use Bolt;
use Bolt\Controller\Zone;
use Bolt\Translation\Translator as Trans;
use Bolt\Twig\ArrayAccessSecurityProxy;
use Bolt\Version;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Configuration checks at the start of the request cycle.
 *
 * This is a temporary solution to remove the configuration checks from
 * pre-request, until such time as configuration can be refactored.
 *
 * @internal do not extend/call
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ConfigListener implements EventSubscriberInterface
{
    /** @var Application */
    private $app;

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Early functions.
     *
     * @param GetResponseEvent $event
     */
    public function onRequestEarly(GetResponseEvent $event)
    {
        $validator = $this->app['config.validator'];
        $response = $validator->checks();
        if ($response instanceof Response) {
            $event->setResponse($response);
        }
    }

    /**
     * Normal functions.
     *
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        if (Zone::isBackend($event->getRequest())) {
            $this->setVersionChangeNotice();
        }

        // Twig globals
        $this->setGlobals();

        // Only cache if the config passes checks
        if ($this->app['config']->checkConfig() === false) {
            return;
        }

        // Final thing we do, if we're still standing, is to save our
        // configuration to cache
        if (!$this->app['config']->get('general/caching/config')) {
            return;
        }

        $this->app['config']->cacheConfig();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequestEarly', Application::EARLY_EVENT],
                ['onRequest', 33], // Before routes determined. @see RouterListener::getSubscribedEvents
            ],
        ];
    }

    /**
     * If required set a notification of version change, for admin users.
     */
    private function setVersionChangeNotice()
    {
        if (!$this->app['config.environment']->hasVersionChange()) {
            return;
        }

        $notice = Trans::__(
            "Detected Bolt version change to <b>%VERSION%</b>, and the cache has been cleared. Please <a href=\"%URI%\">check the database</a>, if you haven't done so already.",
            [
                '%VERSION%' => Version::VERSION,
                '%URI%'     => $this->app['url_generator']->generate('dbcheck'),
            ]
        );
        $this->app['logger.system']->notice(strip_tags($notice), ['event' => 'config']);
        $this->app['logger.flash']->warning($notice);
    }

    /**
     * Get the parameters that will be used to update Bolt's registered Twig
     * globals.
     *
     * This is here as a transitory measure.
     */
    private function setGlobals()
    {
        /** @var Environment $twig */
        $twig = $this->app['twig'];
        /** @var \Bolt\Config $config */
        $config = $this->app['config'];
        /** @var \Bolt\Users $users */
        $users = $this->app['users'];
        $zone = null;
        /** @var RequestStack $requestStack */
        $requestStack = $this->app['request_stack'];
        if ($request = $requestStack->getCurrentRequest()) {
            $zone = Zone::get($request);
        }

        // User calls can cause exceptions that block the exception handler
        try {
            /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
            $usersVal = $users->getUsers();
            $usersCur = $users->getCurrentUser();

            $sandbox = $this->app['twig.extension.sandbox'];
            $usersVal = array_map(function ($user) use ($sandbox) {
                return new ArrayAccessSecurityProxy($user, $sandbox, 'User');
            }, $usersVal);
            $usersCur = new ArrayAccessSecurityProxy($usersCur, $sandbox, 'User');
        } catch (\Exception $e) {
            $usersVal = null;
            $usersCur = null;
        }

        $twig->addGlobal('frontend', $zone === Zone::FRONTEND);
        $twig->addGlobal('backend', $zone === Zone::BACKEND);
        $twig->addGlobal('async', $zone === Zone::ASYNC);
        $twig->addGlobal('theme', $config->get('theme'));
        $twig->addGlobal('user', $usersCur);
        $twig->addGlobal('users', $usersVal);
    }
}
