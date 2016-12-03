<?php

namespace Bolt\Twig\Runtime;

use Bolt\Library as Lib;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Bolt specific Twig functions and filters that provide generic utility
 *
 * @internal
 */
class UtilsRuntime
{
    /** @var LoggerInterface */
    private $firebugLogger;
    /** @var RequestStack */
    private $requestsStack;
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
     * @param RequestStack    $requestsStack
     * @param bool            $debug
     * @param bool            $isUser
     * @param bool            $showAlways
     */
    public function __construct(LoggerInterface $firebugLogger, RequestStack $requestsStack, $debug, $isUser, $showAlways)
    {
        $this->firebugLogger = $firebugLogger;
        $this->requestsStack = $requestsStack;
        $this->debug = $debug;
        $this->isUser = $isUser;
        $this->showAlways = $showAlways;
    }

    /**
     * Check if a file exists.
     *
     * @param string $filename
     *
     * @return boolean
     */
    public function fileExists($filename)
    {
        return file_exists($filename);
    }

    /**
     * Just for safe_twig. Main twig overrides this function.
     *
     * @see \Bolt\Provider\TwigServiceProvider
     */
    public function printDump()
    {
        return null;
    }

    /**
     * Output pretty-printed backtrace.
     *
     * @param integer $depth
     *
     * @return string|null
     */
    public function printBacktrace($depth)
    {
        if (!$this->allowDebug()) {
            return null;
        }

        return VarDumper::dump(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, $depth));
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
     * Redirect the browser to another page.
     *
     * @param string $path
     *
     * @return string
     */
    public function redirect($path)
    {
        Lib::simpleredirect($path);

        return '';
    }

    /**
     * Return the requested parameter from $_REQUEST, $_GET or $_POST.
     *
     * @param string  $parameter    The parameter to get
     * @param string  $from         'GET' or 'POST', all the others falls back to REQUEST.
     * @param boolean $stripSlashes Apply stripslashes. Defaults to false.
     *
     * @return mixed
     */
    public function request($parameter, $from = '', $stripSlashes = false)
    {
        $request = $this->requestsStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        $from = strtoupper($from);

        if ($from === 'GET') {
            $value = $request->query->get($parameter, false);
        } elseif ($from === 'POST') {
            $value = $request->request->get($parameter, false);
        } else {
            $value = $request->get($parameter, false);
        }

        if ($stripSlashes) {
            $value = stripslashes($value);
        }

        return $value;
    }

    /**
     * Helper function to determine if we're supposed to allow `backtrace`
     * and `firebug`. If `$this->app['debug']` is false, we don't allow it.
     * Otherwise we show only to _logged on_ users, _or_ non-authenticated
     * users, but then `debug_show_loggedoff` needs to be set.
     *
     * @return boolean
     */
    private function allowDebug()
    {
        return $this->debug && ($this->isUser || $this->showAlways);
    }
}
