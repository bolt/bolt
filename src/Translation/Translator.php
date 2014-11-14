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
     * Translates a key to text, returns false when not found
     *
     * @param Application $app
     * @param string $fn
     * @param array $args
     * @param string $key
     * @param array $replace
     * @param string $domain
     * @return mixed
     */
    private static function translate(Application $app, $fn, $args, $key, $replace, $domain = 'contenttypes')
    {
        if ($fn == 'transChoice') {
            $trans = $app['translator']->transChoice(
                $key,
                $args[1],
                self::htmlencodeParams($replace),
                isset($args[3]) ? $args[3] : $domain,
                isset($args[4]) ? $args[4] : $app['request']->getLocale()
            );
        } else {
            $trans = $app['translator']->trans(
                $key,
                self::htmlencodeParams($replace),
                isset($args[2]) ? $args[2] : $domain,
                isset($args[3]) ? $args[3] : $app['request']->getLocale()
            );
        }

        return ($trans == $key) ? false : $trans;
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
    public static function /*@codingStandardsIgnoreStart*/__/*@codingStandardsIgnoreEnd*/()
    {
        $app = ResourceManager::getApp();

        $num_args = func_num_args();
        if (0 == $num_args) {
            return null;
        }
        $args = func_get_args();
        if ($num_args > 4) {
            $fn = 'transChoice';
        } elseif ($num_args == 1 || is_array($args[1])) {
            // If only 1 arg or 2nd arg is an array call trans
            $fn = 'trans';
        } else {
            $fn = 'transChoice';
        }
        $tr_args = null;
        if ($fn == 'trans' && $num_args > 1) {
            $tr_args = $args[1];
        } elseif ($fn == 'transChoice' && $num_args > 2) {
            $tr_args = $args[2];
        }

        try {

            // Check for contenttype(s) placeholder
            if ($tr_args) {
                if (isset($tr_args['%contenttype%'])) {
                    $key_arg = '%contenttype%';
                } elseif (isset($tr_args['%contenttypes%'])) {
                    $key_arg = '%contenttypes%';
                } else {
                    $key_arg = false;
                }
                $key_generic = $args[0];
                if ($key_arg && substr($key_generic, 0, 21) == 'contenttypes.generic.') {

                    $ctype = $tr_args[$key_arg];
                    unset($tr_args[$key_arg]);
                    $key_ctype = 'contenttypes.' . $ctype . '.text.' . substr($key_generic, 21);

                    // Try to get a direct translation, fallback to en
                    $trans = static::translate($app, $fn, $args, $key_ctype, $tr_args);

                    // No translation found, use generic translation
                    if ($trans === false) {
                        // Get contenttype name
                        $key_name = 'contenttypes.' . $ctype . '.name.' . (($key_arg == '%contenttype%') ? 'singular' : 'plural');
                        $key_ctname = ($key_arg == '%contenttype%') ? 'singular_name' : 'name';

                        $ctname = $app['translator']->trans($key_name, array(), 'contenttypes', $app['request']->getLocale());
                        if ($ctname === $key_name) {
                            $ctypes = $app['config']->get('contenttypes');
                            $ctname = empty($ctypes[$ctype][$key_ctname]) ? ucfirst($ctype) : $ctypes[$ctype][$key_ctname];
                        }
                        // Get generic translation with name replaced
                        $tr_args[$key_arg] = $ctname;
                        $trans = self::translate($app, $fn, $args, $key_generic, $tr_args, 'messages');
                    }

                    return $trans;
                }
            }

            if (isset($args[1])) {
                $args[1] = self::htmlencodeParams($args[1]);
            }

            return call_user_func_array(array($app['translator'], $fn), $args);

        } catch (\Symfony\Component\Translation\Exception\InvalidResourceException $e) {
            $app['session']->getFlashBag()->set(
                'warning',
                sprintf('<strong>Error: You should fix this now, before continuing!</strong><br> %s', $e->getMessage())
            );

            //$app->abort(500, 'Error reading locale files, Translation files misformed');

            // fallback, just return the key, so the user can continue and fix from backend
            return $args[0];
        }

    }
}
