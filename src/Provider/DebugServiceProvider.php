<?php

namespace Bolt\Provider;

use Bolt\Version;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\BufferingLogger;
use Symfony\Component\Debug\DebugClassLoader;
use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpKernel\EventListener\DebugHandlersListener;

/**
 * Configure Error & Exception Handlers, DebugClassLoader, and debug value.
 *
 * This should be registered first, so that the handlers can be registered
 * before other boot logic happens and services are invoked.
 *
 * 1. There is no error / exception handlers during app creation and registration stage. This is a very small window,
 *    since closures are just being registered (no logic). Extensions are not loaded either.
 * 2. App Boot
 *   2.a. Debug 1st phase: Error & exception handlers are registered (if enabled) based on `debug.early`'s value.
 *        There should be no logic required to get this value.
 *   2.b. Extensions 1st phase: Extensions are registered.
 *   2.c. Debug 2nd phase: The "real" `debug` value is retrieved from config, which means all the logic to setup config
 *        is ran. This is where everything starts happening. Then the handlers are re-registered if their configuration
 *        has changed.
 *   2.d. Extensions 2nd phase: Extensions are booted.
 *   2.e. App continues to boot everything else.
 * 3. Kernel Request
 *   3.a. Our early exception handler is replaced with either the HttpKernel or Console App exception handling.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class DebugServiceProvider implements ServiceProviderInterface
{
    /** @var bool */
    private $firstPhase;

    /**
     * Constructor.
     *
     * @param bool $firstPhase
     */
    public function __construct($firstPhase = true)
    {
        $this->firstPhase = $firstPhase;
    }

    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        if (!$this->firstPhase) {
            return;
        }

        // Debug value that's not based on config service, so exception handler can be registered with minimal logic.
        $app['debug.early'] = true;
        // Whether $this->boot() has happened
        $app['debug.initialized'] = false;

        // Use `debug.early` if booting, then use `debug` from config if given.
        // Feel free to override this value to skip this logic.
        // For example, you can set this based on an environment variable.
        $previousDebug = $app->raw('debug');
        $app['debug'] = function ($app) use ($previousDebug) {
            if (!$app['debug.initialized']) {
                return $app['debug.early'];
            }

            if (($debugOverride = $app['debug.from_config']) !== null) {
                return $debugOverride;
            }

            return $previousDebug;
        };

        // Separate so it's only called once.
        $app['debug.from_config'] = $app->share(function ($app) {
            return $app['config']->get('general/debug');
        });

        if (!isset($app['environment'])) {
            $app['environment'] = $app->share(function ($app) {
                return $app['debug'] ? 'development' : 'production';
            });
        }

        $app['debug.handlers_listener'] = $app->share(
            function ($app) {
                $errorLevels = $app['config']->get(
                    $app['debug'] ?
                    'general/debug_error_level' :
                    'general/production_error_level'
                );

                return new DebugHandlersListener(
                    null, // HttpKernel or Console App exception handler
                    $app['logger'],
                    $errorLevels, // Levels
                    $errorLevels, // Throw at
                    true, // Scream
                    $app['code.file_link_format']
                );
            }
        );

        // Enable handlers for web and cli, but not test runners since they have their own.
        $app['debug.error_handler.enabled'] =
        $app['debug.exception_handler.enabled'] =
            PHP_SAPI !== 'cli' || (
                !defined('PHPUNIT_COMPOSER_INSTALL')
                && !function_exists('codecept_debug')
            );

        // Error Handler is registered when this service is invoked if enabled is true.
        $app['debug.error_handler'] = function ($app) {
            // memoize handler, meaning the same handler is used for every call,
            // unless arguments change then a new one is created.
            /** @var ErrorHandler|null $handler */
            static $handler;
            /** @var bool $displayErrors */
            static $displayErrors;

            $newDisplayErrors = $app['debug.error_handler.display_errors'];
            if ($newDisplayErrors !== $displayErrors) {
                $displayErrors = $newDisplayErrors;

                // If the handler has already been created, but the display_errors value has changed:
                // revert throwAt call (below) and return the same handler.
                if ($handler !== null) {
                    $handler->throwAt(-1 & 0x1FFF, true);

                    return $handler;
                }
            } elseif ($handler !== null) { // return same handler if created
                return $handler;
            }

            $handler = new ErrorHandler(new BufferingLogger());

            if ($app['debug.error_handler.enabled']) {
                ErrorHandler::register($handler);

                if (!$displayErrors) {
                    // Don't convert any errors to exceptions.
                    // has to be after register() as it resets the level.
                    $handler->throwAt(0, true);
                }
            }

            return $handler;
        };

        // If debug, throw errors, else just log the error.
        $app['debug.error_handler.display_errors'] = function ($app) {
            return $app['debug'];
        };

        // If debug, report all errors, else leave it unchanged.
        $app['debug.error_handler.reporting_level'] = function ($app) {
            return $app['debug'] ? -1 : null;
        };

        // Exception Handler is registered when this service is invoked if enabled.
        // This is only for bootstrapping. The real one gets set on kernel request / console command event.
        $app['debug.exception_handler'] = function ($app) {
            // memoize handler, meaning the same handler is used for every call,
            // unless arguments change then a new one is created.
            static $handler;
            static $args;

            $newArgs = [
                $debug = $app['debug'],
                $charset = $app['charset'],
                $fileLinkFormat = $app['code.file_link_format'],
            ];

            if ($newArgs !== $args) {
                $args = $newArgs;
                $handler = null;
            }

            if ($handler !== null) {
                return $handler;
            }

            if ($app['debug.error_handler.enabled']) {
                // Register the ExceptionHandler on the ErrorHandler as well.
                $handler = ExceptionHandler::register($debug, $charset, $fileLinkFormat);
            } else {
                $handler = new ExceptionHandler($debug, $charset, $fileLinkFormat);
            }

            // The ExceptionHandler by default renders HTML. If we are on CLI, change it to render for CLI.
            // Remember this is only in effect until the the console command event is dispatched,
            // where that console app is used instead.
            if (PHP_SAPI === 'cli') {
                $handler->setHandler(function ($e) {
                    $app = new ConsoleApplication('Bolt CLI', Version::VERSION);
                    $output = new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG);
                    $app->renderException($e, $output);
                    ob_clean();
                });
            }

            return $handler;
        };

        $app['debug.class_loader.enabled'] = function ($app) {
            return $app['debug'];
        };

        // Added by WebProviderServiceProvider
        if (!isset($app['code.file_link_format'])) {
            $app['code.file_link_format'] = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        if ($this->firstPhase) {
            $level = $app['debug.error_handler.reporting_level'];
            if ($level !== null) {
                error_reporting($level);
            }

            // Register handlers with `debug.early` value
            $this->registerHandlers($app);
        } else {
            $app['debug.initialized'] = true;

            // Can be subscribed regardless of enabled, because it won't do anything
            // if there is no error handler or exception handler registered.
            $app['dispatcher']->addSubscriber($app['debug.handlers_listener']);

            // Register again which will make changes to handlers if parameters have changed.
            $this->registerHandlers($app);
        }
    }

    private function registerHandlers(Application $app)
    {
        if ($app['debug.class_loader.enabled']) {
            DebugClassLoader::enable();
        }

        if ($app['debug.error_handler.enabled']) {
            $app['debug.error_handler']; // Invoke to register
        }

        if ($app['debug.exception_handler.enabled']) {
            $app['debug.exception_handler']; // Invoke to register
        }
    }
}
