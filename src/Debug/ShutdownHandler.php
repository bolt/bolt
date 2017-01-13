<?php

namespace Bolt\Debug;

use Bolt\Version;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug;

/**
 * Shutdown handler set up.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ShutdownHandler
{
    public static $errorLevels;

    /**
     * Set the handlers.
     *
     * NOTE:
     * CLI uses a custom output handler & must NOT call Debug::enable() as this
     * breaks Codeception redirect handling under some circumstances.
     *
     * @param bool $debug
     */
    public static function register($debug = true)
    {
        self::$errorLevels = error_reporting();

        if (PHP_SAPI === 'cli') {
            $consoleHandler = function (\Exception $e) {
                $app = new Application('Bolt CLI', Version::VERSION);
                $output = new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG);
                $app->renderException($e, $output);
                ob_clean();
            };
            Debug\ExceptionHandler::register($debug)->setHandler($consoleHandler);

            return;
        }

        if ($debug) {
            self::$errorLevels |= E_RECOVERABLE_ERROR | E_USER_ERROR | E_DEPRECATED | E_USER_DEPRECATED;
        }
        Debug\Debug::enable(self::$errorLevels, true);
        Debug\ExceptionHandler::register($debug);
    }
}
