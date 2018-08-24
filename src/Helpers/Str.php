<?php

namespace Bolt\Helpers;

use Cocur\Slugify\Slugify;

class Str extends \Bolt\Common\Str
{
    /**
     * Returns a "safe" version of the given string - basically only US-ASCII and
     * numbers. Needed because filenames and titles and such, can't use all characters.
     *
     * @param string $str
     * @param bool   $strict
     * @param string $extrachars
     *
     * @return string
     */
    public static function makeSafe($str, $strict = false, $extrachars = '')
    {
        $str = str_replace('&amp;', '', $str);

        $delim = '/';
        if ($extrachars != '') {
            $extrachars = preg_quote($extrachars, $delim);
        }
        if ($strict) {
            $slugify = Slugify::create([
                'regexp' => '/[^a-z0-9_' . $extrachars . ' -]+/'
            ]);
            $str = $slugify->slugify($str, '');
            $str = str_replace(' ', '-', $str);
        } else {
            // Allow Uppercase and don't convert spaces to dashes
            $slugify = Slugify::create([
                'regexp' => '/[^a-zA-Z0-9_.,' . $extrachars . ' -]+/',
                'lowercase' => false
            ]);
            $str = $slugify->slugify($str, '');
        }

        return $str;
    }

    /**
     * {@inheritdoc}
     */
    public static function replaceFirst($search, $replace, $subject, $caseSensitive = true)
    {
        Deprecated::method(3.4, parent::class);

        return parent::replaceFirst($subject, $search, $replace);
    }

    /**
     * {@inheritdoc}
     */
    public static function replaceLast($search, $replace, $subject, $caseSensitive = true)
    {
        Deprecated::method(3.4, parent::class);

        return parent::replaceLast($subject, $search, $replace, $caseSensitive);
    }

    /**
     * Add 'soft hyphens' &shy; to a string, so that it won't break layout in HTML when
     * using strings without spaces or dashes. Only breaks in long (> 19 chars) words.
     *
     * @param string $str
     *
     * @return string
     */
    public static function shyphenate($str)
    {
        $res = preg_match_all('/([a-z0-9]{19,})/i', $str, $matches);

        if ($res) {
            foreach ($matches[1] as $key => $match) {
                $str = str_replace($match, wordwrap($match, 10, '&shy;', true), $str);
            }
        }

        return $str;
    }

    /**
     * {@inheritdoc}
     */
    public static function endsWith($subject, $search, $caseSensitive = true)
    {
        Deprecated::method(3.4, parent::class);

        return parent::endsWith($subject, $search, $caseSensitive);
    }
}
