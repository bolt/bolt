<?php

namespace Bolt\Twig\Handler;

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
     * @param string  $fn
     * @param boolean $safe
     *
     * @return boolean
     */
    public function fileExists($fn, $safe)
    {
        if ($safe) {
            // pretend we don't know anything about any files
            return false;
        } else {
            return file_exists($fn);
        }
    }

    /**
     * Output pretty-printed backtrace.
     *
     * @param integer $depth
     * @param boolean $safe
     *
     * @return string|null
     */
    public function printBacktrace($depth, $safe)
    {
        if ($safe || !$this->app['debug']) {
            return null;
        }

        return VarDumper::dump(debug_backtrace());
    }

    /**
     * Output pretty-printed arrays / objects.
     *
     * @param mixed   $var
     * @param boolean $safe
     *
     * @return string
     */
    public function printDump($var, $safe)
    {
        if ($safe || !$this->app['debug']) {
            return null;
        }

        return VarDumper::dump($var);
    }

    /**
     * Send debug data to the developers FirePHP instance in-browser.
     *
     * @param mixed   $var  The data to be dumped into FirePHP
     * @param mixed   $msg  The message to associate with the data
     * @param boolean $safe
     *
     * @return string FirePHP formatted string
     */
    public function printFirebug($var, $msg, $safe)
    {
        if ($safe) {
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
     *
     * @param boolean $safe
     *
     * @return string
     */
    public function redirect($path, $safe)
    {
        // Nope! We're not allowing user-supplied content to issue redirects.
        if ($safe) {
            return null;
        }

        Lib::simpleredirect($path);

        return '';
    }

    /**
     * Return the requested parameter from $_REQUEST, $_GET or $_POST.
     *
     * @param string  $parameter    The parameter to get
     * @param string  $from         'GET' or 'POST', all the others falls back to REQUEST.
     * @param boolean $stripslashes Apply stripslashes. Defaults to false.
     * @param boolean $safe
     *
     * @return mixed
     */
    public function request($parameter, $from, $stripslashes, $safe)
    {
        // Don't expose request in safe context
        if ($safe) {
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
