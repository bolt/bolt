<?php

namespace Bolt\Provider;

use Bolt\EventListener as Listener;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventListenerServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['listener.access_control'] = function ($app) {
            return new Listener\AccessControlListener(
                $app['filesystem'],
                $app['session.storage'],
                $app['storage.lazy']
            );
        };

        $app['listener.general'] = function ($app) {
            return new Listener\GeneralListener($app);
        };

        $app['disable_xss_protection_routes'] = [
            'preview',
            'fileedit',
        ];
        $app['listener.disable_xss_protection'] = function ($app) {
            return new Listener\DisableXssProtectionListener($app['disable_xss_protection_routes']);
        };

        $app['listener.exception'] = function ($app) {
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
        };

        $app['listener.exception_json'] = function () {
            return new Listener\ExceptionToJsonListener();
        };

        $app['listener.not_found'] = function ($app) {
            return new Listener\NotFoundListener(
                $app['config']->get('theme/notfound') ?: $app['config']->get('general/notfound'),
                $app['query'],
                $app['templatechooser'],
                $app['twig']
            );
        };

        $app['listener.system_logger'] = function ($app) {
            return new Listener\SystemLoggerListener($app['logger.system']);
        };

        /*
         * Creating the actual url generator flushes all controllers.
         * We aren't ready for this since controllers.mount event hasn't fired yet.
         * RedirectListener doesn't use the url generator until kernel.response
         * (way after controllers have been added).
         */
        $app['listener.redirect'] = function ($app) {
            return new Listener\RedirectListener(
                $app['session'],
                $app['url_generator.lazy'],
                $app['users'],
                $app['access_control']
            );
        };

        $app['listener.flash_logger'] = function ($app) {
            $debug = $app['debug'] && $app['config']->get('general/debug_show_loggedoff', false);

            return new Listener\FlashLoggerListener($app['logger.flash'], $debug);
        };

        $app['listener.pager'] = function ($app) {
            return new Listener\PagerListener($app['pager']);
        };

        $app['listener.snippet'] = function ($app) {
            return new Listener\SnippetListener(
                $app['asset.queues'],
                $app['canonical'],
                $app['asset.packages'],
                $app['config']
            );
        };

        $app['listener.template_view'] = function ($app) {
            return new Listener\TemplateViewListener($app['twig']);
        };

        $app['listener.zone_guesser'] = function ($app) {
            return new Listener\ZoneGuesser($app);
        };

        $app['listener.profile'] = function ($app) {
            return new Listener\ProfilerListener(
                $app['session'],
                $app['debug'],
                $app['config']->get('general/debug_show_loggedoff')
            );
        };
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
