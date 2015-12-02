<?php

namespace Bolt\Helpers;

/**
 * Wrapper class for Bolt\Helpers\Str as String is a reserved class name in PHP 7
 *
 * @deprecated Deprecated since 3.0, to be removed in 4.0.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class String
{
    const NOTICE = 'Bolt\Helpers\String is deprecated. Use Bolt\Helpers\Str instead.';

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public static function makeSafe($str, $strict = false, $extrachars = '')
    {
        trigger_error(self::NOTICE, E_USER_DEPRECATED);

        return Str::makeSafe($str, $strict, $extrachars);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        trigger_error(self::NOTICE, E_USER_DEPRECATED);

        return Str::replaceFirst($search, $replace, $subject);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public static function shyphenate($str)
    {
        trigger_error(self::NOTICE, E_USER_DEPRECATED);

        return Str::shyphenate($str);
    }
}
