<?php

namespace Bolt\Logger;

use Silex\Application;

/**
 * Handler for deprecated log message.
 *
 * @deprecated since version 2.1
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DeprecatedLog
{
    /**
     * @var \Silex\Application
     */
    private $app;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param string $msg
     */
    public function add($msg)
    {
        $backtrace = debug_backtrace();
        $backtrace = $backtrace[0];
        $file = str_replace($this->app['resources']->getPath('root'), "", $backtrace['file']);

        $this->app['logger.system']->info($msg, array('event' => 'extension'));
        $this->app['logger.system']->warning("[DEPRECATED]: Previous message logged using deprecated log service: {$file}::{$backtrace['line']}", array('event' => 'deprecated'));
    }
}
