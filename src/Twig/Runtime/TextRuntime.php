<?php

namespace Bolt\Twig\Runtime;

use Bolt\Common\Exception\ParseException;
use Bolt\Common\Json;
use Bolt\Helpers\Str;
use Cocur\Slugify\SlugifyInterface;
use Psr\Log\LoggerInterface;

/**
 * Bolt specific Twig functions and filters that provide text manipulation.
 *
 * @internal
 */
class TextRuntime
{
    /** @var LoggerInterface */
    private $systemLogger;
    /** @var SlugifyInterface */
    private $slugify;

    /**
     * Constructor.
     *
     * @param LoggerInterface  $systemLogger
     * @param SlugifyInterface $slugify
     */
    public function __construct(LoggerInterface $systemLogger, SlugifyInterface $slugify)
    {
        $this->systemLogger = $systemLogger;
        $this->slugify = $slugify;
    }

    /**
     * JSON decodes a variable. Twig has a built-in json_encode filter, but no built-in
     * function to JSON decode a string. This functionality remedies that.
     *
     * @param string $string the string to decode
     *
     * @return array|null The JSON decoded array
     */
    public function jsonDecode($string)
    {
        try {
            return Json::parse($string);
        } catch (ParseException $e) {
            return null;
        }
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
    public function localeDateTime($dateTime, $format = '%B %e, %Y %H:%M', $tempLocale = null)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }

        // Check for Windows to find and replace the %e modifier correctly
        // @see: http://php.net/strftime
        $os = strtoupper(substr(PHP_OS, 0, 3));
        $format = $os !== 'WIN' ? $format : preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);

        // According to http://php.net/manual/en/function.setlocale.php manual
        // if the second parameter is "0", the locale setting is not affected,
        // only the current setting is returned.
        $currentLocale = setlocale(LC_ALL, 0);
        if ($currentLocale === false) {
            // This shouldn't occur, but.. Dude!
            // You ain't even got locale or English on your platform??
            // Various things we could do. We could fail miserably, but a more
            // graceful approach is to use the datetime to display a default
            // format
            $this->systemLogger->error('No valid locale detected. Fallback on DateTime active.', ['event' => 'system']);

            return $dateTime->format('Y-m-d H:i:s');
        }

        // Temporarily set the locale, if needed
        if ($tempLocale) {
            setlocale(LC_ALL, array_merge((array) $tempLocale, (array) $currentLocale));
        }

        $timestamp = $dateTime->getTimestamp();
        $result = strftime($format, $timestamp);

        // And reset the locale, if needed
        if ($tempLocale) {
            setlocale(LC_ALL, $currentLocale);
        }

        return $result;
    }

    /**
     * Perform a regular expression search and replace on the given string.
     *
     * @param string $str
     * @param string $pattern
     * @param string $replacement
     * @param int    $limit
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
     * @param string $str
     * @param bool   $strict
     * @param string $extrachars
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

        return $this->slugify->slugify($str);
    }

    /**
     * Test whether a passed string contains valid JSON.
     *
     * @param string $string the string to test
     *
     * @return bool
     */
    public function testJson($string)
    {
        return Json::test($string);
    }
}
