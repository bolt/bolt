<?php
/**
 * Customised Whoops service provider with the error event handler moved out to
 * a subscriber so that it can be conditionally removed.
 *
 * @author Filipe Dobreira <http://github.com/filp>
 */

namespace Bolt\Provider;

use Bolt\EventListener\WhoopsListener;
use RuntimeException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Whoops\Handler\Handler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class WhoopsServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        // There's only ever going to be one error page...right?
        $app['whoops.error_page_handler'] = $app->share(function () {
            if (PHP_SAPI === 'cli') {
                return new PlainTextHandler();
            } else {
                return new PrettyPageHandler();
            }
        });

        // Retrieves info on the Silex environment and ships it off
        // to the PrettyPageHandler's data tables:
        // This works by adding a new handler to the stack that runs
        // before the error page, retrieving the shared page handler
        // instance, and working with it to add new data tables
        $app['whoops.silex_info_handler'] = $app->protect(function () use ($app) {
            try {
                /** @var Request $request */
                $request = $app['request'];
            } catch (RuntimeException $e) {
                // This error occurred too early in the application's life
                // and the request instance is not yet available.
                return;
            }

            /** @var Handler $errorPageHandler */
            $errorPageHandler = $app["whoops.error_page_handler"];

            if ($errorPageHandler instanceof PrettyPageHandler) {
                /** @var PrettyPageHandler $errorPageHandler */

                // General application info:
                $errorPageHandler->addDataTable('Silex Application', array(
                    'Charset'           => $app['charset'],
                    'Locale'            => $app['locale'],
                    'Route Class'       => $app['route_class'],
                    'Dispatcher Class'  => $app['dispatcher_class'],
                    'Application Class' => get_class($app),
                ));

                // Request info:
                $errorPageHandler->addDataTable('Silex Application (Request)', array(
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
                ));
            }
        });

        $app['whoops'] = $app->share(function () use ($app) {
            $run = new Run();
            $run->allowQuit(false);
            $run->pushHandler($app['whoops.error_page_handler']);
            $run->pushHandler($app['whoops.silex_info_handler']);
            return $run;
        });

        $app['whoops.listener'] = $app->share(function ($app) {
            $showWhileLoggedOff = $app['config']->get('general/debug_show_loggedoff', false);
            return new WhoopsListener($app['whoops'], $app['session'], $showWhileLoggedOff);
        });

        $app['whoops']->register();
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($app['whoops.listener']);
    }
}
