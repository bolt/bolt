<?php

namespace Bolt\Twig\Handler;

use Bolt\Helpers\Str;
use Silex;

/**
 * Bolt specific Twig functions and filters that provide text manipulation
 *
 * @internal
 */
class TextHandler
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
     * JSON decodes a variable. Twig has a built-in json_encode filter, but no built-in
     * function to JSON decode a string. This functionality remedies that.
     *
     * @param string $string The string to decode.
     *
     * @return array The JSON decoded array
     */
    public function jsonDecode($string)
    {
        return json_decode($string, true);
    }

    /**
     * Returns the date time in a particular format. Takes the locale into
     * account.
     *
     * @param string|\DateTime $dateTime
     * @param string           $format
     *
     * @return string Formatted date and time
     */
    public function localeDateTime($dateTime, $format = '%B %e, %Y %H:%M')
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }

        // Check for Windows to find and replace the %e modifier correctly
        // @see: http://php.net/strftime
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
        }

        // According to http://php.net/manual/en/function.setlocale.php manual
        // if the second parameter is "0", the locale setting is not affected,
        // only the current setting is returned.
        $result = setlocale(LC_ALL, 0);
        if ($result === false) {
            // This shouldn't occur, but.. Dude!
            // You ain't even got locale or English on your platform??
            // Various things we could do. We could fail miserably, but a more
            // graceful approach is to use the datetime to display a default
            // format
            $this->app['logger.system']->error('No valid locale detected. Fallback on DateTime active.', ['event' => 'system']);

            return $dateTime->format('Y-m-d H:i:s');
        } else {
            $timestamp = $dateTime->getTimestamp();

            return strftime($format, $timestamp);
        }
    }

    /**
     * Perform a regular expression search and replace on the given string.
     *
     * @param string  $str
     * @param string  $pattern
     * @param string  $replacement
     * @param integer $limit
     *
     * @return string Same string where first character is in upper case
     */
    public function pregReplace($str, $pattern, $replacement = '', $limit = -1)
    {
        return preg_replace($pattern, $replacement, $str, $limit);
    }

    /**
     * Return a 'safe string' version of a given string.
     *
     * @see function Bolt\Library::safeString()
     *
     * @param string  $str
     * @param boolean $strict
     * @param string  $extrachars
     *
     * @return string
     */
    public function safeString($str, $strict = false, $extrachars = '')
    {
        return Str::makeSafe($str, $strict, $extrachars);
    }

    /**
     * Return the 'sluggified' version of a string.
     *
     * @param string $str input value
     *
     * @return string Slug safe version of the string
     */
    public function slug($str)
    {
        if (is_array($str)) {
            $str = implode(' ', $str);
        }

        return $this->app['slugify']->slugify($str);
    }

    /**
     * Test whether a passed string contains valid JSON.
     *
     * @param string $string The string to test.
     *
     * @return boolean
     */
    public function testJson($string)
    {
        json_decode($string, true);

        return (json_last_error() === JSON_ERROR_NONE);
    }
}
