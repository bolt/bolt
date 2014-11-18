<?php

namespace Bolt\Translation;

use Bolt\Application;
use Bolt\Configuration\ResourceManager;

/**
 * Handles translation
 */
class Translator
{
   /**
    * Encode array values as html special chars
    *
    * @param array $params Parameter to encode
    * @return array
    */
    private static function htmlencodeParams($params)
    {
        return array_map(
            function ($val) {
                return htmlspecialchars($val, ENT_QUOTES);
            },
            $params
        );
    }

    /**
     * Low level translation
     *
     * @param string $key
     * @param array $parameters
     * @param string $domain
     * @param string $locale
     * @return string
     */
    public static function trans($key, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        $app = ResourceManager::getApp();

        // Handle default parameter
        if (isset($parameters['DEFAULT'])) {
            $default = $parameters['DEFAULT'];
            unset($parameters['DEFAULT']);
        } else {
            $default = null;
        }

        // Handle number parameter
        if (isset($parameters['NUMBER'])) {
            $number = $parameters['NUMBER'];
            unset($parameters['NUMBER']);
        } else {
            $number = null;
        }

        // Translate
        try {
            if ($number === null) {
                $trans = $app['translator']->trans($key, $parameters, $domain, $locale);
            } else {
                $trans = $app['translator']->transChoice($key, $number, $parameters, $domain, $locale);
            }

            return ($trans === $key && $default !== null) ? $default : $trans;
        } catch (\Symfony\Component\Translation\Exception\InvalidResourceException $e) {
            if (!isset($app['translationyamlerror']) && $app['request']->isXmlHttpRequest() == false) {
                $app['session']->getFlashBag()->add(
                    'warning',
                    '<strong>Error: You should fix this now, before continuing!</strong><br>' . $e->getMessage()
                );
                $app['translationyamlerror'] = true;
            }

            return strtr($key, $parameters);
        }
    }

    /**
     * Low level translation (perhaps unused)
     *
     * @param string $key
     * @param integer $number
     * @param array $parameters
     * @param string $domain
     * @param string $locale
     * @return string
     */
    public static function transChoice($key, $number, array $parameters = array(), $domain = 'messages')
    {
        $app = ResourceManager::getApp();

        try {
            return $app['translator']->transChoice($key, $number, $parameters, $domain, $locale);
        } catch (\Symfony\Component\Translation\Exception\InvalidResourceException $e) {
            if (!isset($app['translationyamlerror']) && $app['request']->isXmlHttpRequest() == false) {
                $app['session']->getFlashBag()->add(
                    'warning',
                    '<strong>Error: You should fix this now, before continuing!</strong><br>' . $e->getMessage()
                );
                $app['translationyamlerror'] = true;
            }

            return strtr($key, $parameters);
        }
    }

    /**
     * i18n made right, second attempt...
     *
     * Instead of calling directly $app['translator']->trans(), we check
     * for the presence of a placeholder named '%contenttype%'.
     *
     * If one is found, we replace it with the contenttype.name parameter,
     * and try to get a translated string. If there is not, we revert to
     * the generic (%contenttype%) string, which must have a translation.
     */
    public static function /*@codingStandardsIgnoreStart*/__/*@codingStandardsIgnoreEnd*/($key, $parameters = array(), $domain = 'messages', $locale = null)
    {
        $app = ResourceManager::getApp();

        // Set locale
        if ($locale === null) {
            $locale = $app['request']->getLocale();
        }

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

        // Check for contenttype(s) placeholder
        if (count($parameters) > 0) {
            if (isset($parameters['%contenttype%'])) {
                $key_arg = '%contenttype%';
            } elseif (isset($parameters['%contenttypes%'])) {
                $key_arg = '%contenttypes%';
            } else {
                $key_arg = false;
            }
            $key_generic = $key;
            if ($key_arg && substr($key_generic, 0, 21) == 'contenttypes.generic.') {

                $ctype = $parameters[$key_arg];
                unset($parameters[$key_arg]);
                $key_ctype = 'contenttypes.' . $ctype . '.text.' . substr($key_generic, 21);

                // Try to get a direct translation, fallback to en
                $trans = static::trans($key_ctype, static::htmlencodeParams($parameters), 'contenttypes', $locale);

                // No translation found, use generic translation
                if ($trans === $key_ctype) {
                    // Get contenttype name
                    $key_name = 'contenttypes.' . $ctype . '.name.' . (($key_arg == '%contenttype%') ? 'singular' : 'plural');
                    $key_ctname = ($key_arg == '%contenttype%') ? 'singular_name' : 'name';

                    $ctname = static::trans($key_name, array(), 'contenttypes', $app['request']->getLocale());
                    if ($ctname === $key_name) {
                        $ctypes = $app['config']->get('contenttypes');
                        $ctname = empty($ctypes[$ctype][$key_ctname]) ? ucfirst($ctype) : $ctypes[$ctype][$key_ctname];
                    }
                    // Get generic translation with name replaced
                    $parameters[$key_arg] = $ctname;
                    $trans = static::trans($key_generic, static::htmlencodeParams($parameters), 'messages', $locale);
                }

                return $trans;
            }
        }

        return static::trans($key, static::htmlencodeParams($parameters), $domain, $locale);
    }
}
