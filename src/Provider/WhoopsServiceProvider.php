<?php

namespace Bolt\Provider;

use Bolt\EventListener\WhoopsListener;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class WhoopsServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['whoops'] = $app->share(function () use ($app) {
            $run = new Run();
            $run->allowQuit(false);
            $run->pushHandler($app['whoops.handler']);

            return $run;
        });

        $app['whoops.handler'] = $app->share(function () use ($app) {
            if (PHP_SAPI === 'cli') {
                return $app['whoops.handler.cli'];
            } else {
                return $app['whoops.handler.page'];
            }
        });

        $app['whoops.handler.cli'] = $app->share(function () {
            return new PlainTextHandler();
        });

        $app['whoops.handler.page'] = $app->share(function () use ($app) {
            $handler = new PrettyPageHandler();
            $handler->addDataTableCallback('Bolt Application', $app['whoops.handler.page.app_info']);
            $handler->addDataTableCallback('Request', $app['whoops.handler.page.request_info']);

            return $handler;
        });

        $app['whoops.handler.page.app_info'] = $app->protect(function () use ($app) {
            return [
                'Charset'           => $app['charset'],
                'Locale'            => $app['locale'],
                'Route Class'       => $app['route_class'],
                'Dispatcher Class'  => $app['dispatcher_class'],
                'Application Class' => get_class($app),
            ];
        });

        $app['whoops.handler.page.request_info'] = $app->protect(function () use ($app) {
            /** @var RequestStack $requestStack */
            $requestStack = $app['request_stack'];
            if (!$request = $requestStack->getCurrentRequest()) {
                return [];
            }

            return [
                'URI'          => $request->getUri(),
                'Request URI'  => $request->getRequestUri(),
                'Path Info'    => $request->getPathInfo(),
                'Query String' => $request->getQueryString() ?: '<none>',
                'HTTP Method'  => $request->getMethod(),
                'Script Name'  => $request->getScriptName(),
                'Base Path'    => $request->getBasePath(),
                'Base URL'     => $request->getBaseUrl(),
                'Scheme'       => $request->getScheme(),
                'Port'         => $request->getPort(),
                'Host'         => $request->getHost(),
            ];
        });

        $app['whoops.listener'] = $app->share(function () use ($app) {
            $showWhileLoggedOff = $app['config']->get('general/debug_show_loggedoff', false);

            return new WhoopsListener(
                $app['whoops'],
                $app['session'],
                $showWhileLoggedOff
            );
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['whoops.listener']);
    }
}
