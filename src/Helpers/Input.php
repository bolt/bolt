<?php

namespace Bolt\Helpers;

class Input
{
    /**
     * Clean posted data. Convert tabs to spaces (primarily for yaml) and
     * stripslashes when magic quotes are turned on.
     *
     * @param  mixed  $var
     * @param  bool   $stripslashes
     * @param  bool   $strip_control_chars
     * @return string
     */
    public static function cleanPostedData($var, $stripslashes = true, $strip_control_chars = false)
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $var[$key] = static::cleanPostedData($value, $stripslashes, $strip_control_chars);
            }
        } elseif (is_string($var)) {
            // expand tabs
            $var = str_replace("\t", "    ", $var);

            // prune control characters
            if ($strip_control_chars) {
                $var = preg_replace('/[[:cntrl:][:space:]]/', ' ', $var);
            }

            // Ah, the joys of \"magic quotes\"!
            if ($stripslashes && get_magic_quotes_gpc()) {
                $var = stripslashes($var);
            }
        }

        return $var;
    }
}
