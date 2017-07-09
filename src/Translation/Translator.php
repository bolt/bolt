<?php

namespace Bolt\Translation;

use Bolt\Legacy\AppSingleton;
use Symfony\Component\Translation\Exception\InvalidResourceException;

/**
 * Handles translation.
 */
class Translator
{
    /**
     * Encode array values as html special chars.
     *
     * @param array  $params    Parameter to encode
     * @param string $removeKey If not empty the key is removed from result
     *
     * @return array
     */
    private static function htmlencodeParams(array $params, $removeKey = '')
    {
        if ($removeKey) {
            unset($params[$removeKey]);
        }

        return array_map(
            function ($val) {
                return htmlspecialchars($val, ENT_QUOTES);
            },
            $params
        );
    }

    /**
     * Low level translation.
     *
     * @param string $key
     * @param array  $params
     * @param string $domain
     * @param mixed  $locale
     * @param mixed  $default
     *
     * @return string
     */
    private static function trans($key, array $params = [], $domain = 'messages', $locale = null, $default = null)
    {
        $app = AppSingleton::get();

        // Handle default parameter
        if (isset($params['DEFAULT'])) {
            if ($default === null) {
                $default = $params['DEFAULT'];
            }
            unset($params['DEFAULT']);
        }

        // Handle number parameter
        if (isset($params['NUMBER'])) {
            $number = $params['NUMBER'];
            unset($params['NUMBER']);
        } else {
            $number = null;
        }

        // Translate
        try {
            if ($number === null) {
                $trans = $app['translator']->trans($key, $params, $domain, $locale);
            } else {
                $trans = $app['translator']->transChoice($key, $number, $params, $domain, $locale);
            }

            return ($trans === $key && $default !== null) ? $default : $trans;
        } catch (InvalidResourceException $e) {
            if (!isset($app['translationyamlerror']) && $app['request']->isXmlHttpRequest() === false) {
                $app['logger.flash']->danger('Error: You should fix this now, before continuing!<br>' . $e->getMessage());
                $app['translationyamlerror'] = true;
            }

            return strtr($key, $params);
        }
    }

    /**
     * Translation shortcut placeholder.
     *
     * Special parameter keys:
     * 'DEFAULT': the value is returns instead of the key of no translation is found
     * 'NUMBER': transChoice is triggered with the value as count value
     *
     * @param mixed  $key    The message ID. If an array is passed, a sanitized key is built
     * @param array  $params Parameter for string replacement and commands ('DEFAULT', 'NUMBER')
     * @param string $domain
     * @param mixed  $locale
     *
     * @return string
     */
    public static function __($key, array $params = [], $domain = 'messages', $locale = null)
    {
        // If $key is an array, convert it to a sanitized string
        if (is_array($key)) {
            array_walk(
                $key,
                function (&$value) {
                    $value = preg_replace('/[^a-z-]/', '', strtolower($value));
                }
            );
            $key = implode('.', $key);
        }

        return self::trans($key, self::htmlencodeParams($params), $domain, $locale);
    }
}
