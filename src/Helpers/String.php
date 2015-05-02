<?php

namespace Bolt\Helpers;

/**
 * Wrapper class for Bolt\Helpers\Str as String is a reserved class name in PHP 7
 *
 * @deprecated Will be removed in Bolt 3
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class String
{
    const NOTICE = 'Bolt\Helpers\String is deprecated. Use Bolt\Helpers\Str instead.';

    /**
     * @deprecated
     */
    public static function makeSafe($str, $strict = false, $extrachars = "")
    {
        trigger_error(self::NOTICE, E_USER_DEPRECATED);

        return Str::makeSafe($str, $strict, $extrachars);
    }

    /**
     * @deprecated
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        trigger_error(self::NOTICE, E_USER_DEPRECATED);

        return Str::replaceFirst($search, $replace, $subject);
    }

    /**
     * @deprecated
     */
    public static function shyphenate($str)
    {
        trigger_error(self::NOTICE, E_USER_DEPRECATED);

        return Str::shyphenate($str);
    }
}
