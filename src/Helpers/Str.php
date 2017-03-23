<?php

namespace Bolt\Helpers;

use Cocur\Slugify\Slugify;

class Str
{
    /**
     * Returns a "safe" version of the given string - basically only US-ASCII and
     * numbers. Needed because filenames and titles and such, can't use all characters.
     *
     * @param string  $str
     * @param boolean $strict
     * @param string  $extrachars
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
            $slugify = Slugify::create('/[^a-z0-9_' . $extrachars . ' -]+/');
            $str = $slugify->slugify($str, '');
            $str = str_replace(' ', '-', $str);
        } else {
            // Allow Uppercase and don't convert spaces to dashes
            $slugify = Slugify::create('/[^a-zA-Z0-9_.,' . $extrachars . ' -]+/', ['lowercase' => false]);
            $str = $slugify->slugify($str, '');
        }

        return $str;
    }

    /**
     * Replace the first occurrence of a string only. Behaves like str_replace, but
     * replaces _only_ the _first_ occurrence.
     *
     * @see http://stackoverflow.com/a/2606638
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     *
     * @return string
     */
    public static function replaceFirst($search, $replace, $subject)
    {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    /**
     * Replace only the last occurrence of the $search text on the $subject.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @param bool   $caseSensitive
     *
     * @return string
     */
    public static function replaceLast($search, $replace, $subject, $caseSensitive = true)
    {
        $pos = $caseSensitive ? strrpos($subject, $search) : strripos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
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
     * Returns whether the subjects ends with the search string.
     *
     * @param string $subject
     * @param string $search
     * @param bool   $caseSensitive
     *
     * @return bool
     */
    public static function endsWith($subject, $search, $caseSensitive = true)
    {
        if ($caseSensitive) {
            $subject = strtolower($subject);
            $search = strtolower($search);
        }

        return $search === '' || substr($subject, -strlen($search)) === $search;
    }
}
