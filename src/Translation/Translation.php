<?php

namespace Bolt\Translation;

/**
 * Handles translation
 */
class Translation
{
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
    public static function __()
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

                // Translates a key to text, returns false when not found
                $fnc_trans = function ($key, $tr_args, $domain) use ($fn, $args, $app) {
                    if ($fn == 'transChoice') {
                        $trans = $app['translator']->transChoice(
                            $key,
                            $args[1],
                            self::htmlencode_params($tr_args),
                            isset($args[3]) ? $args[3] : $domain,
                            isset($args[4]) ? $args[4] : $app['request']->getLocale()
                        );
                    } else {
                        $trans = $app['translator']->trans(
                            $key,
                            self::htmlencode_params($tr_args),
                            isset($args[2]) ? $args[2] : $domain,
                            isset($args[3]) ? $args[3] : $app['request']->getLocale()
                        );
                    }

                    return ($trans == $key) ? false : $trans;
                };
                $ctype = $tr_args[$key_arg];
                unset($tr_args[$key_arg]);
                $key_ctype = 'contenttypes.' . $ctype . '.text.' . substr($key_generic, 21);

                // Try to get a direct translation, fallback to en
                $trans = $fnc_trans($key_ctype, $tr_args, 'contenttypes');

                // No translation found, use generic translation
                if ($trans === false) {
                    // Get contenttype name
                    $key_name = 'contenttypes.' . $ctype . '.name.' . (($key_arg == '%contenttype%') ? 'singular' : 'plural');
                    $key_ctname = ($key_arg == '%contenttype%') ? 'singular_name' : 'name';

                    $ctname = $fnc_trans($key_name, $tr_args, 'contenttypes');
                    if ($ctname === false) {
                        $ctypes = $app['config']->get('contenttypes');
                        $ctname = empty($ctypes[$ctype][$key_ctname]) ? ucfirst($ctype) : $ctypes[$ctype][$key_ctname];
                    }
                    // Get generic translation with name replaced
                    $tr_args[$key_arg] = $ctname;
                    $trans = $fnc_trans($key_generic, $tr_args, 'messages');
                }

                return $trans;
            }
        }

        if (isset($args[1])) {
            $args[1] = self::htmlencode_params($args[1]);
        }
        switch ($num_args) {
            case 5:
                return $app['translator']->transChoice($args[0], $args[1], $args[2], $args[3], $args[4]);
            case 4:
                return $app['translator']->$fn($args[0], $args[1], $args[2], $args[3]);
            case 3:
                return $app['translator']->$fn($args[0], $args[1], $args[2]);
            case 2:
                return $app['translator']->$fn($args[0], $args[1]);
            case 1:
                return $app['translator']->$fn($args[0]);
        }
    }
}
