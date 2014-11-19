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
    * @param string $removeKey If not empty the key is removed from result
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
     * Low level translation
     *
     * @param string $key
     * @param array $parameters
     * @param string $domain
     * @param mixed $locale
     * @param mixed $default
     * @return string
     */
    public static function trans($key, array $parameters = array(), $domain = 'messages', $locale = null, $default = null)
    {
        $app = ResourceManager::getApp();

        // Handle default parameter
        if (isset($parameters['DEFAULT'])) {
            if ($default === null) {
                $default = $parameters['DEFAULT'];
            }
            unset($parameters['DEFAULT']);
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
     * Returns translated contenttype name with fallback to name/slug from 'contenttypes.yml'
     *
     * @param string $contenttype The contentype
     * @param bool $singular Singular or plural requested?
     * @param string $locale Translate to this locale
     * @return string
     */
    private static function transContenttypeName($contenttype, $singular, $locale)
    {
        $key = 'contenttypes.' . $contenttype . '.name.' . ($singular ? 'singular' : 'plural');

        $name = static::trans($key, array(), 'contenttypes', $locale);
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
     * Translates contentype specific messages and falls back to building generic message or fallback locale
     *
     * @param string $genericKey
     * @param array $params
     * @param string $id
     * @param boolean $singular
     * @param mixed $locale
     * @return boolean
     */
    private static function transContenttype($genericKey, array $params, $id, $singular, $locale)
    {
        $contenttype = $params[$id];
        $encParams = static::htmlencodeParams($params, $id);
        $key = 'contenttypes.' . $contenttype . '.text.' . substr($genericKey, 21);

        // Try to get a real translation from contenttypes.xx_XX.yml
        $trans = static::trans($key, $encParams, 'contenttypes', $locale, false);
        $transFallback = static::trans($key, $encParams, 'contenttypes', \Bolt\Application::DEFAULT_LOCALE, false);

        // We don't want fallback translation here
        if ($trans === $transFallback) {
            $trans = false;
        }

        // No translation found, build string from generic translation pattern
        if ($trans === false) {
            // Get generic translation with name replaced
            $encParams[$id] = static::transContenttypeName($contenttype, $singular, $locale);
            $transGeneric = static::trans($genericKey, $encParams, 'messages', $locale, false);
        } else {
            $transGeneric = false;
        }

        // Return: translation => generic translation => fallback translation => key
        if ($trans) {
            return $trans;
        } elseif ($transGeneric) {
            return $transGeneric;
        } elseif ($transFallback) {
            return $transFallback;
        } else {
            return $genericKey;
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
    public static function /*@codingStandardsIgnoreStart*/__/*@codingStandardsIgnoreEnd*/($key, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        // Set locale
        if ($locale === null) {
            $app = ResourceManager::getApp();
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

        // Handle generic contenttypes
        if (substr($key, 0, 21) == 'contenttypes.generic.') {
            if (isset($parameters['%contenttype%'])) {
                return static::transContenttype($key, $parameters, '%contenttype%', true, $locale);
            } elseif (isset($parameters['%contenttypes%'])) {
                return static::transContenttype($key, $parameters, '%contenttypes%', false, $locale);
            }
        }

        return static::trans($key, static::htmlencodeParams($parameters), $domain, $locale);
    }
}
