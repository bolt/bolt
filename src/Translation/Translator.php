<?php

namespace Bolt\Translation;

use Bolt\Configuration\ResourceManager;
use Silex\Application;
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
        $app = ResourceManager::getApp();

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
                $app['logger.flash']->warning('<strong>Error: You should fix this now, before continuing!</strong><br>' . $e->getMessage());
                $app['translationyamlerror'] = true;
            }

            return strtr($key, $params);
        }
    }

    /**
     * Returns translated contenttype name with fallback to name/slug from 'contenttypes.yml'.
     *
     * @param string $contenttype The contentype
     * @param bool   $singular    Singular or plural requested?
     * @param string $locale      Translate to this locale
     *
     * @return string
     */
    private static function transContenttypeName($contenttype, $singular, $locale)
    {
        $key = 'contenttypes.' . $contenttype . '.name.' . ($singular ? 'singular' : 'plural');

        $name = self::trans($key, [], 'contenttypes', $locale);
        if ($name === $key) {
            $app = ResourceManager::getApp();

            $name = $app['config']->get('contenttypes/' . $contenttype . ($singular ? '/singular_name' : '/name'));
            if (empty($name)) {
                $name = ucfirst(
                    $app['config']->get(
                        'contenttypes/' . $contenttype . ($singular ? '/singular_slug' : '/slug'),
                        $contenttype
                    )
                );
            }
            // Escape names coming from 'contenttypes.yml'
            $name = htmlspecialchars($name, ENT_QUOTES);
        }

        return $name;
    }

    /**
     * Translates contentype specific messages and falls back to building generic message or fallback locale.
     *
     * @param string  $genericKey
     * @param array   $params
     * @param string  $id
     * @param boolean $singular
     * @param mixed   $locale
     *
     * @return boolean
     */
    private static function transContenttype($genericKey, array $params, $id, $singular, $locale)
    {
        $contenttype = $params[$id];
        $encParams = self::htmlencodeParams($params, $id);
        $key = 'contenttypes.' . $contenttype . '.text.' . substr($genericKey, 21);

        // Try to get a real translation from contenttypes.xx_XX.yml
        $trans = self::trans($key, $encParams, 'contenttypes', $locale, false);
        $app = ResourceManager::getApp();
        $localeFallbacks = $app['locale_fallbacks'];
        $transFallback = self::trans($key, $encParams, 'contenttypes', reset($localeFallbacks), false);

        // We don't want fallback translation here
        if ($trans === $transFallback) {
            $trans = false;
        }

        // No translation found, build string from generic translation pattern
        if ($trans === false) {
            // Get generic translation with name replaced
            $encParams[$id] = self::transContenttypeName($contenttype, $singular, $locale);
            $transGeneric = self::trans($genericKey, $encParams, 'messages', $locale, false);
        } else {
            $transGeneric = false;
        }

        // Return: translation => generic translation => fallback translation => key
        if ($trans !== false) {
            return $trans;
        } elseif ($transGeneric !== false) {
            return $transGeneric;
        } elseif ($transFallback !== false) {
            return $transFallback;
        } else {
            return $genericKey;
        }
    }

    /**
     * i18n made right, third attempt….
     *
     * Instead of calling directly $app['translator']->trans(), we check for the presence of a placeholder named
     * '%contenttype%'.
     *
     * If one is found, we replace it with the contenttype.name parameter, and try to get a translated string. If
     * there is not, we revert to the generic (%contenttype%) string, which must have a translation.
     *
     * Special parameter keys:
     * 'DEFAULT': the value is returns instead of the key of no translation is found
     * 'NUMBER': transCjoice is triggered with the value as countvalue
     *
     * @param mixed  $key    The messsage id. If an array is passed, an sanitized key is build
     * @param array  $params Parameter for string replacement and commands ('DEFAULT', 'NUMBER')
     * @param string $domain
     * @param mixed  $locale
     *
     * @return string
     */
    public static function /*@codingStandardsIgnoreStart*/__/*@codingStandardsIgnoreEnd*/($key, array $params = [], $domain = 'messages', $locale = null)
    {
        // If $key is an array, convert it to a sanizized string
        if (is_array($key)) {
            array_walk(
                $key,
                function (&$value) {
                    $value = preg_replace('/[^a-z-]/', '', strtolower($value));
                }
            );
            $key = join('.', $key);
        }

        // Handle contenttypes
        if (substr($key, 0, 13) == 'contenttypes.') {
            // Generic contenttypes
            if (substr($key, 13, 8) == 'generic.') {
                if (isset($params['%contenttype%'])) {
                    return self::transContenttype($key, $params, '%contenttype%', true, $locale);
                } elseif (isset($params['%contenttypes%'])) {
                    return self::transContenttype($key, $params, '%contenttypes%', false, $locale);
                }
            // Switch domain
            } elseif ($domain === 'messages') {
                $domain = 'contenttypes';
            }
        }

        return self::trans($key, self::htmlencodeParams($params), $domain, $locale);
    }
}
