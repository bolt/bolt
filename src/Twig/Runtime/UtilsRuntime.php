<?php

namespace Bolt\Twig\Runtime;

use Psr\Log\LoggerInterface;

/**
 * Bolt specific Twig functions and filters that provide generic utility.
 *
 * @internal
 */
class UtilsRuntime
{
    /** @var LoggerInterface */
    private $firebugLogger;
    /** @var bool */
    private $debug;
    /** @var bool */
    private $isUser;
    /** @var bool */
    private $showAlways;

    /**
     * Constructor.
     *
     * @param LoggerInterface $firebugLogger
     * @param bool            $debug
     * @param bool            $isUser
     * @param bool            $showAlways
     */
    public function __construct(LoggerInterface $firebugLogger, $debug, $isUser, $showAlways)
    {
        $this->firebugLogger = $firebugLogger;
        $this->debug = $debug;
        $this->isUser = $isUser;
        $this->showAlways = $showAlways;
    }

    /**
     * Check if a file exists.
     *
     * @param string $filename
     *
     * @return bool
     */
    public function fileExists($filename)
    {
        return file_exists($filename);
    }

    /**
     * Send debug data to the developers FirePHP instance in-browser.
     *
     * @param mixed $var The data to be dumped into FirePHP
     * @param mixed $msg The message to associate with the data
     */
    public function printFirebug($var, $msg)
    {
        if (!$this->allowDebug()) {
            return;
        }

        if (is_string($msg)) {
            $this->firebugLogger->info($msg, (array) $var);
        } elseif (is_string($var)) {
            $this->firebugLogger->info($var, (array) $msg);
        }
    }

    /**
     * Helper function to determine if we're supposed to allow `backtrace`
     * and `firebug`. If `$this->app['debug']` is false, we don't allow it.
     * Otherwise we show only to _logged on_ users, _or_ non-authenticated
     * users, but then `debug_show_loggedoff` needs to be set.
     *
     * @return bool
     */
    private function allowDebug()
    {
        return $this->debug && ($this->isUser || $this->showAlways);
    }
}
