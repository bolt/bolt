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
        Debug\ErrorHandler::register();

        if ($debug) {
            Debug\DebugClassLoader::enable();
        }

        if (PHP_SAPI !== 'cli') {
            Debug\ExceptionHandler::register($debug);
        } else {
            $exceptionHandler = Debug\ExceptionHandler::register($debug);
            $exceptionHandler->setHandler(
                function(\Exception $e) {
                    $app = new Application('Bolt CLI', Version::VERSION);
                    $output = new ConsoleOutput(OutputInterface::VERBOSITY_DEBUG);
                    $app->renderException($e, $output);
                    ob_clean();
            });
        }
    }
}
