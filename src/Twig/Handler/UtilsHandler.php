<?php

namespace Bolt\Twig\Handler;

use Bolt\Application;
use Bolt\Library as Lib;
use Silex;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Bolt specific Twig functions and filters that provide generic utility
 *
 * @internal
 */
class UtilsHandler
{
    /** @var \Silex\Application */
    private $app;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     *  Switch the debugbar 'on' or 'off'. Note: this has no influence on the
     * 'debug' setting itself. When 'debug' is off, setting this to 'on', will
     * _not_ show the debugbar.
     *
     * @param boolean $value
     */
    public function debugBar($value)
    {
        // Make sure it's actually true or false;
        $value = ($value) ? true : false;

        $this->app['debugbar'] = $value;
    }

    /**
     * Check if a file exists.
     *
     * @param string $fn
     *
     * @return bool
     */
    public function fileExists($fn)
    {
        if ($this->safe) {
            return false; // pretend we don't know anything about any files
        } else {
            return file_exists($fn);
        }
    }

    /**
     * Output pretty-printed backtrace.
     *
     * @param integer $depth
     *
     * @return string|null
     */
    public function printBacktrace($depth = 15)
    {
        if ($this->safe || !$this->app['debug']) {
            return null;
        }

        return VarDumper::dump(debug_backtrace());
    }

    /**
     * Output pretty-printed arrays / objects.
     *
     * @param mixed $var
     *
     * @return string
     */
    public function printDump($var)
    {
        if ($this->safe || !$this->app['debug']) {
            return null;
        }

        return VarDumper::dump($var);
    }

    /**
     * Send debug data to the developers FirePHP instance in-browser.
     *
     * @param mixed $var The data to be dumped into FirePHP
     * @param mixed $msg The message to associate with the data
     *
     * @return string FirePHP formatted string
     */
    public function printFirebug($var, $msg = '')
    {
        if ($this->safe) {
            return null;
        }
        if ($this->app['debug']) {
            if (is_array($var)) {
                $this->app['logger.firebug']->info($msg, $var);
            } elseif (is_string($var)) {
                $this->app['logger.firebug']->info($var);
            } else {
                $this->app['logger.firebug']->info($msg, (array) $var);
            }
        } else {
            return null;
        }
    }

    /**
     * Redirect the browser to another page.
     */
    public function redirect($path)
    {
        // Nope! We're not allowing user-supplied content to issue redirects.
        if ($this->safe) {
            return null;
        }

        Lib::simpleredirect($path);

        return '';
    }

    /**
     * Return the requested parameter from $_REQUEST, $_GET or $_POST.
     *
     * @param string  $parameter    The parameter to get
     * @param string  $from         'GET', 'POST', all the other falls back to REQUEST.
     * @param boolean $stripslashes Apply stripslashes. Defaults to false.
     *
     * @return mixed
     */
    public function request($parameter, $from = '', $stripslashes = false)
    {
        // Don't expose request in safe context
        if ($this->safe) {
            return null;
        }

        $from = strtoupper($from);

        if ($from === 'GET') {
            $request = $this->app['request']->query->get($parameter, false);
        } elseif ($from === 'POST') {
            $request = $this->app['request']->request->get($parameter, false);
        } else {
            $request = $this->app['request']->get($parameter, false);
        }

        if ($stripslashes) {
            $request = stripslashes($request);
        }

        return $request;
    }
}
