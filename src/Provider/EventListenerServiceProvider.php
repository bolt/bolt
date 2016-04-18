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
                    $app['storage']
                );
            }
        );

        $app['listener.general'] = $app->share(
            function ($app) {
                return new Listener\GeneralListener($app);
            }
        );

        $app['listener.exception'] = $app->share(
            function ($app) {
                $rootPath = $app['resources']->getPath('root');

                return new Listener\ExceptionListener(
                    $rootPath,
                    $app['render'],
                    $app['logger.system'],
                    $app['session'],
                    $app['config']->get('general/debug', false)
                );
            }
        );

        $app['listener.not_found'] = $app->share(
            function ($app) {
                return new Listener\NotFoundListener(
                    $app['config']->get('theme/notfound') ?: $app['config']->get('general/notfound'),
                    $app['storage.legacy'],
                    $app['templatechooser'],
                    $app['render']
                );
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
                    $app['asset.queue.snippet'],
                    $app['config'],
                    $app['resources'],
                    $app['render']
                );
            }
        );

        $app['listener.zone_guesser'] = $app->share(
            function ($app) {
                return new Listener\ZoneGuesser($app);
            }
        );
    }

    public function boot(Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];

        $dispatcher->addSubscriber($app['listener.access_control']);
        $dispatcher->addSubscriber($app['listener.general']);
        $dispatcher->addSubscriber($app['listener.exception']);
        $dispatcher->addSubscriber($app['listener.not_found']);
        $dispatcher->addSubscriber($app['listener.snippet']);
        $dispatcher->addSubscriber($app['listener.redirect']);
        $dispatcher->addSubscriber($app['listener.flash_logger']);
        $dispatcher->addSubscriber($app['listener.zone_guesser']);
        $dispatcher->addSubscriber($app['listener.pager']);
    }
}
