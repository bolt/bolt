<?php

namespace Bolt\Provider;

use Bolt\Common\Ini;
use Bolt\Version;
use Psr\Log\LoggerInterface;
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
 *   2.a. Debug 1st phase: Error & exception handlers are registered based on `debug.early`'s value.
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

        // Thrown and logged errors in an integer bit field of E_* constants
        $app['error_handler.throw_at'] =
        $app['error_handler.log_at'] = function ($app) {
            if ($app['debug']) {
                return $app['config']->get('general/debug_error_level', E_ALL);
            }

            return $app['config']->get('general/production_error_level', 0);
        };

        $app['error_handler.logger'] = function ($app) {
            return $app['logger'];
        };

        // Enable handlers for web and cli, but not test runners since they have their own.
        $app['error_handler.enabled'] =
        $app['exception_handler.enabled'] =
            PHP_SAPI !== 'cli' || (
                !defined('PHPUNIT_COMPOSER_INSTALL')
                && !function_exists('codecept_debug')
            );

        $app['error_handler'] = $app->share(function () {
            return new ErrorHandler(new BufferingLogger());
        });

        // Exception Handler is registered when this service is invoked if enabled.
        // This is only for bootstrapping. The real one gets set on kernel request / console command event.
        $app['exception_handler.early'] = function ($app) {
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

            if ($app['error_handler.enabled']) {
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

        // Listener to set the exception handler from HttpKernel or Console App.
        $app['debug.handlers_listener'] = $app->share(
            function () {
                return new DebugHandlersListener(null);
            }
        );

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
            if ($app['error_handler.enabled']) {
                // Report all errors since it has its own logging / throwing errors logic.
                error_reporting(E_ALL);

                // Disable built-in PHP error displaying logic. Errors are:
                // 1. Logged to the logger.
                // 2. Converted to an exception and thrown to the user that way.
                // Both of these are regardless of HTML or CLI output.
                // This makes the built-in display_errors redundant.
                Ini::set('display_errors', false);
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
        } else {
            DebugClassLoader::disable();
        }

        if ($app['error_handler.enabled']) {
            $handler = $app['error_handler'];
            if ($this->firstPhase) {
                ErrorHandler::register($handler);
                // Set throw at value based on `debug.early` value during 1st phase.
                // (Has to be after register() as that resets the level.)
                $handler->throwAt($app['debug'] ? E_ALL : 0, true);
            } else {
                // Set throw at value based on config value during 2nd phase.
                $handler->throwAt($app['error_handler.throw_at'], true);

                $this->configureLogger($handler, $app['error_handler.logger'], $app['error_handler.log_at']);
            }
        }

        if ($app['exception_handler.enabled']) {
            $app['exception_handler.early']; // Invoke to register
        }
    }

    /**
     * Configure the error handler to log types given to the logger given and to ignore all types not specified.
     *
     * It's important that the BufferingLogger is completely replaced for all error types with either a real logger
     * or null, otherwise a memory leak could occur.
     *
     * @param ErrorHandler    $handler
     * @param LoggerInterface $logger
     * @param array|int       $loggedAt An array map of E_* to LogLevel::* or an integer bit field of E_* constants
     */
    private function configureLogger(ErrorHandler $handler, LoggerInterface $logger, $loggedAt)
    {
        // Set real logger for the levels specified.
        $handler->setDefaultLogger($logger, $loggedAt);

        // For all the levels not logged, tell the handler not to log them.
        $notLoggedLevels = [];
        $defaults = [
            E_DEPRECATED        => null,
            E_USER_DEPRECATED   => null,
            E_NOTICE            => null,
            E_USER_NOTICE       => null,
            E_STRICT            => null,
            E_WARNING           => null,
            E_USER_WARNING      => null,
            E_COMPILE_WARNING   => null,
            E_CORE_WARNING      => null,
            E_USER_ERROR        => null,
            E_RECOVERABLE_ERROR => null,
            E_COMPILE_ERROR     => null,
            E_PARSE             => null,
            E_ERROR             => null,
            E_CORE_ERROR        => null,
        ];
        if (is_array($loggedAt)) {
            $notLoggedLevels = array_diff_key($defaults, $loggedAt);
        } else {
            if ($loggedAt === 0) { // shortcut for no logging.
                $notLoggedLevels = $defaults;
            } elseif ($loggedAt === E_ALL) { // shortcut for all logging.
                // Do nothing. Leave notLoggedLevels empty.
            } else {
                foreach ($defaults as $type => $logger) {
                    if (!($loggedAt & $type)) {
                        $notLoggedLevels[$type] = null;
                    }
                }
            }
        }
        if ($notLoggedLevels) {
            $handler->setLoggers($notLoggedLevels);
        }
    }
}
