<?php

namespace Bolt\EventListener;

use Bolt;
use Bolt\Controller\Zone;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Translation\Translator as Trans;
use Bolt\Version;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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
class BootInitListener implements EventSubscriberInterface
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
     * Early boot functions.
     *
     * @param GetResponseEvent $event
     *
     * @return Response|null
     */
    public function onBootEarly(GetResponseEvent $event)
    {
        $verifier = $this->app['boot.validator'];
        $response = $verifier->checks();
        if (!$response instanceof Response) {
            return null;
        }

        $event->setResponse($response);
    }

    /**
     * Normal boot functions.
     *
     * @param GetResponseEvent $event
     *
     * @return Response|null
     */
    public function onBoot(GetResponseEvent $event)
    {
        if (Zone::isBackend($event->getRequest())) {
            $this->setVersionChangeNotice();
        }

        // Twig globals
        $this->setGlobals(false);
        $this->setGlobals(true);

        // Only cache if the config passes checks
        if ($this->app['config']->checkConfig() === false) {
            return null;
        }

        // Final thing we do, if we're still standing, is to save our
        // configuration to cache
        if (!$this->app['config']->get('general/caching/config')) {
            return null;
        }

        $cacheFs = $this->app['filesystem']->getFilesystem('cache');
        try {
            $this->app['config']->cacheConfig($cacheFs, '', false);
        } catch (IOException $e) {
            $response = $this->app['controller.exception']->genericException($e);
            $event->setResponse($response);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onBootEarly', Application::EARLY_EVENT],
                ['onBoot', 33], // After routes determined. @see RouterListener::getSubscribedEvents
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
     *
     * @param bool $safe
     *
     * @return array
     */
    private function setGlobals($safe)
    {
        /** @var \Twig_Environment $twig */
        $twig = $safe ? $this->app['safe_twig'] : $this->app['twig'];
        /** @var \Bolt\Config $config */
        $config = $this->app['config'];
        $configVal = $safe ? null : $config;
        /** @var \Bolt\Users $users */
        $users = $this->app['users'];
        /** @var \Bolt\Configuration\ResourceManager $resources */
        $resources = $this->app['resources'];
        $zone = null;
        /** @var RequestStack $requestStack */
        $requestStack = $this->app['request_stack'];
        if ($request = $requestStack->getCurrentRequest()) {
            $zone = Zone::get($request);
        }

        // User calls can cause exceptions that block the exception handler
        try {
            /** @deprecated Deprecated since 3.0, to be removed in 4.0. */
            $usersVal = $safe ? null : $users->getUsers();
            $usersCur = $users->getCurrentUser();
        } catch (\Exception $e) {
            $usersVal = null;
            $usersCur = null;
        }

        $twig->addGlobal('bolt_name', Bolt\Version::name());
        $twig->addGlobal('bolt_version', Bolt\Version::VERSION);
        $twig->addGlobal('bolt_stable', Bolt\Version::isStable());
        $twig->addGlobal('frontend', $zone === Zone::FRONTEND);
        $twig->addGlobal('backend', $zone === Zone::BACKEND);
        $twig->addGlobal('async', $zone === Zone::ASYNC);
        $twig->addGlobal('paths', $resources->getPaths());
        $twig->addGlobal('theme', $config->get('theme'));
        $twig->addGlobal('user', $usersCur);
        $twig->addGlobal('users', $usersVal);
        $twig->addGlobal('config', $configVal);
    }
}
