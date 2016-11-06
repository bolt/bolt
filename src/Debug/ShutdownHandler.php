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
    /**
     * Set the handlers.
     *
     * @param bool $debug
     */
    public static function register($debug = true)
    {
        $errorLevels = error_reporting();
        if ($debug) {
            $errorLevels |= E_RECOVERABLE_ERROR | E_USER_ERROR | E_DEPRECATED | E_USER_DEPRECATED;
            Debug\DebugClassLoader::enable();
        }
        Debug\ErrorHandler::register()->throwAt($errorLevels, true);

        if (PHP_SAPI !== 'cli') {
            Debug\ExceptionHandler::register($debug);
        } else {
            $consoleHandler = function (\Exception $e) {
                $app = new Application('Bolt CLI', Version::VERSION);
                $output = new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG);
                $app->renderException($e, $output);
                ob_clean();
            };
            Debug\ExceptionHandler::register($debug)->setHandler($consoleHandler);
        }
    }
}
