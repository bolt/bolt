<?php

namespace Bolt\Helpers;

class Input
{
    /**
     * Clean posted data. Convert tabs to spaces (primarily for yaml) and
     * stripslashes when magic quotes are turned on.
     *
     * @param mixed $var
     * @param bool  $stripslashes
     * @param bool  $stripControlChars
     *
     * @return string
     */
    public static function cleanPostedData($var, $stripslashes = true, $stripControlChars = false)
    {
        if (is_array($var)) {
            foreach ($var as $key => $value) {
                $var[$key] = static::cleanPostedData($value, $stripslashes, $stripControlChars);
            }
        } elseif (is_string($var)) {
            // expand tabs
            $var = str_replace("\t", "    ", $var);

            // prune control characters
            if ($stripControlChars) {
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
