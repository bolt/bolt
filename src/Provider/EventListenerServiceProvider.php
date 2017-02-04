<?php

namespace Bolt\Provider;

use Bolt\EventListener as Listener;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventListenerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['listener.access_control'] = 
            function ($app) {
                return new Listener\AccessControlListener(
                    $app['filesystem'],
                    $app['session.storage'],
                    $app['storage.lazy']
                );
            }
        ;

        $app['listener.general'] = 
            function ($app) {
                return new Listener\GeneralListener($app);
            }
        ;

        $app['listener.exception'] = 
            function ($app) {
                return new Listener\ExceptionListener(
                    $app['config'],
                    $app['controller.exception'],
                    $app['logger.system']
                );
            }
        ;

        $app['listener.not_found'] = 
            function ($app) {
                return new Listener\NotFoundListener(
                    $app['config']->get('theme/notfound') ?: $app['config']->get('general/notfound'),
                    $app['storage.legacy'],
                    $app['templatechooser'],
                    $app['twig'],
                    $app['render']
                );
            }
        ;

        /*
         * Creating the actual url generator flushes all controllers.
         * We aren't ready for this since controllers.mount event hasn't fired yet.
         * RedirectListener doesn't use the url generator until kernel.response
         * (way after controllers have been added).
         */
        $app['listener.redirect'] = 
            function ($app) {
                return new Listener\RedirectListener(
                    $app['session'],
                    $app['url_generator.lazy'],
                    $app['users'],
                    $app['access_control']
                );
            }
        ;

        $app['listener.flash_logger'] = 
            function ($app) {
                $debug = $app['debug'] && $app['config']->get('general/debug_show_loggedoff', false);
                $profilerPrefix = isset($app['profiler.mount_prefix']) ? $app['profiler.mount_prefix'] : null;

                return new Listener\FlashLoggerListener($app['logger.flash'], $debug, $profilerPrefix);
            }
        ;

        $app['listener.pager'] = 
            function ($app) {
                return new Listener\PagerListener($app['pager']);
            }
        ;

        $app['listener.snippet'] = 
            function ($app) {
                return new Listener\SnippetListener(
                    $app['asset.queues'],
                    $app['canonical'],
                    $app['asset.packages'],
                    $app['config']
                );
            }
        ;

        $app['listener.zone_guesser'] = 
            function ($app) {
                return new Listener\ZoneGuesser($app);
            }
        ;
    }

    public function boot(Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];

        $listeners = [
            'general',
            'exception',
            'not_found',
            'snippet',
            'redirect',
            'flash_logger',
            'zone_guesser',
            'pager',
        ];

        foreach ($listeners as $name) {
            if (isset($app['listener.' . $name])) {
                $dispatcher->addSubscriber($app['listener.' . $name]);
            }
        }
    }
}
