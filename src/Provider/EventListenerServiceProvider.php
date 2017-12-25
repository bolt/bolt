<?php

namespace Bolt\Provider;

use Bolt\EventListener as Listener;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventListenerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['listener.access_control'] = $app->share(
            function ($app) {
                return new Listener\AccessControlListener(
                    $app['filesystem'],
                    $app['session.storage'],
                    $app['storage.lazy']
                );
            }
        );

        $app['listener.general'] = $app->share(
            function ($app) {
                return new Listener\GeneralListener($app);
            }
        );

        $app['disable_xss_protection_routes'] = [
            'preview',
            'fileedit',
        ];
        $app['listener.disable_xss_protection'] = $app->share(
            function ($app) {
                return new Listener\DisableXssProtectionListener($app['disable_xss_protection_routes']);
            }
        );

        $app['listener.exception'] = $app->share(
            function ($app) {
                return new Listener\ExceptionListener(
                    $app['twig'],
                    $app['path_resolver']->resolve('root'),
                    $app['filesystem']->getDir('cache://exception/' . $app['environment']),
                    $app['slugify'],
                    $app['debug'],
                    $app['config'],
                    $app['users'],
                    $app['session'],
                    $app['request_stack']
                );
            }
        );

        $app['listener.exception_json'] = $app->share(
            function ($app) {
                return new Listener\ExceptionToJsonListener($app['path_resolver']);
            }
        );

        $app['listener.not_found'] = $app->share(
            function ($app) {
                return new Listener\NotFoundListener(
                    $app['config']->get('theme/notfound') ?: $app['config']->get('general/notfound'),
                    $app['storage.legacy'],
                    $app['templatechooser'],
                    $app['twig']
                );
            }
        );

        $app['listener.system_logger'] = $app->share(
            function ($app) {
                return new Listener\SystemLoggerListener($app['logger.system']);
            }
        );

        /*
         * Creating the actual url generator flushes all controllers.
         * We aren't ready for this since controllers.mount event hasn't fired yet.
         * RedirectListener doesn't use the url generator until kernel.response
         * (way after controllers have been added).
         */
        $app['listener.redirect'] = $app->share(
            function ($app) {
                return new Listener\RedirectListener(
                    $app['session'],
                    $app['url_generator.lazy'],
                    $app['users'],
                    $app['access_control']
                );
            }
        );

        $app['listener.flash_logger'] = $app->share(
            function ($app) {
                $debug = $app['debug'] && $app['config']->get('general/debug_show_loggedoff', false);
                $profilerPrefix = isset($app['profiler.mount_prefix']) ? $app['profiler.mount_prefix'] : null;

                return new Listener\FlashLoggerListener($app['logger.flash'], $debug, $profilerPrefix);
            }
        );

        $app['listener.pager'] = $app->share(
            function ($app) {
                return new Listener\PagerListener($app['pager']);
            }
        );

        $app['listener.snippet'] = $app->share(
            function ($app) {
                return new Listener\SnippetListener(
                    $app['asset.queues'],
                    $app['canonical'],
                    $app['asset.packages'],
                    $app['config']
                );
            }
        );

        $app['listener.template_view'] = $app->share(
            function ($app) {
                return new Listener\TemplateViewListener($app['twig']);
            }
        );

        $app['listener.zone_guesser'] = $app->share(
            function ($app) {
                return new Listener\ZoneGuesser($app);
            }
        );

        $app['listener.profile'] = $app->share(
            function ($app) {
                return new Listener\ProfilerListener(
                    $app['session'],
                    $app['debug'],
                    $app['config']->get('general/debug_show_loggedoff')
                );
            }
        );
    }

    public function boot(Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];

        $listeners = [
            'profile',
            'general',
            'disable_xss_protection',
            'exception_json',
            'not_found',
            'system_logger',
            'snippet',
            'redirect',
            'flash_logger',
            'template_view',
            'zone_guesser',
            'pager',
        ];

        foreach ($listeners as $name) {
            if (isset($app['listener.' . $name])) {
                $dispatcher->addSubscriber($app['listener.' . $name]);
            }
        }

        if (isset($app['listener.exception']) && !$app['config']->get('general/debug_error_use_symfony')) {
            $dispatcher->addSubscriber($app['listener.exception']);
        }
    }
}
