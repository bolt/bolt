<?php

namespace Bolt\Exception;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Boot initialisation exception.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BootException extends RuntimeException
{
    /** @var Response */
    protected $response;

    /**
     * Constructor.
     *
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     * @param Response   $response
     */
    public function __construct($message, $code = 0, \Exception $previous = null, Response $response = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * @return boolean
     */
    public function hasResponse()
    {
        return (boolean) $this->response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Output an exception *very* early in the load-chain.
     *
     * @param string $message
     *
     * @throws BootException
     */
    public static function earlyException($message)
    {
        echo $message;

        throw new static(strip_tags($message));
    }

    /**
     * Exception due to a missing vendor/autoload.php file.
     */
    public static function earlyExceptionComposer()
    {
        $message = <<<EOM
Configuration auto-detection failed because the file <code>vendor/autoload.php</code> doesn't exist.
<br><br>
Make sure you've installed the required components with Composer.
EOM;
        echo sprintf(static::getEarlyExceptionHtml(), 'Bolt - Installation Incomplete', $message, static::getHintsComposer());

        throw new static(strip_tags($message));
    }

    /**
     * Exception due to a missing .bolt.yml or .bolt.php file.
     */
    public static function earlyExceptionMissingLoaderConfig()
    {
        $message = <<<EOM
This installation is missing either a .bolt.yml file (default), or a .bolt.php file.
<br><br>
If you have uploaded this install via a file manager or FTP, please check that 
"show hidden files" is turned on. After doing so, you will be able to see this 
file and you can upload it to the root of your Bolt installation.
EOM;
        echo sprintf(static::getEarlyExceptionHtml(), 'Bolt - Installation Incomplete', $message, static::getHintsComposer());

        throw new static(strip_tags($message));
    }

    /**
     * Exception due to a PHP version being unsupported.
     */
    public static function earlyExceptionVersion()
    {
        $message = <<<EOM
Bolt requires PHP <u>5.5.9</u>, or higher. 
<br><br>
You are running PHP <u>%s</u>, so Bolt will not run on your current setup.
EOM;
        $message = sprintf($message, htmlspecialchars(PHP_VERSION, ENT_QUOTES));

        echo sprintf(static::getEarlyExceptionHtml(), 'Bolt - Fatal error', $message, '');

        throw new static(strip_tags($message));
    }

    /**
     * Template for early exception HTML to be parsed by sprintf() prior to output.
     *
     * @return string
     */
    protected static function getEarlyExceptionHtml()
    {
        return <<<EOM
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Bolt - Error</title>
    <style>
        body { padding: 20px; font-family: "Helvetica Neue",Helvetica,Arial,sans-serif; color:#333; font-size:14px; line-height: 20px; margin: 0px; }
        .exception div { max-width: 530px; margin: auto; }
        .exception h1 { font-size: 38.5px; line-height: 40px;margin: 10px 0px; }
        .exception p { margin: 0px 0px 10px; }
        .exception strong { font-weight: bold; }
        .exception code, pre { padding: 0px 3px 2px; font-family: Monaco,Menlo,Consolas,"Courier New",monospace; font-size: 12px; color: #333; border-radius: 3px; }
        .exception code { padding: 2px 4px; color: #D14; background-color: #F7F7F9; border: 1px solid #E1E1E8; white-space: nowrap; }
        .exception a { color: #08C; text-decoration: none; }
        .exception ul, ol { padding: 0px; margin: 0px 0px 10px 25px; }
        .exception hr { margin:20px 0; border:0; border-top: 1px solid #eeeeee; border-bottom: 1px solid #ffffff; }
    </style>
</head>
<body class="exception">
    <div>
        <h1>%s</h1>
        <p>%s</p>
        <hr>
        <p>%s</p>
    </div>
    <hr>
</body>
</html>
EOM;
    }

    /**
     * Footer hints for missing autoload.php exceptions.
     *
     * @return string
     */
    protected static function getHintsComposer()
    {
        return <<<EOM
For more details: 
<ul>
    <li>
        <a href="https://getcomposer.org/doc/00-intro.md">Getting Composer</a>
    </li>
    <li>
        <a href="https://getcomposer.org/doc/01-basic-usage.md#installing-dependencies">Installing dependencies with Composer</a>
    </li>
</ul>
EOM;
    }
}
