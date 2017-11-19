<?php

namespace Bolt\Helpers;

use Bolt\Collection;

/**
 * @deprecated since 3.3, to be removed in 4.0. Use {@see Bolt\Collection\Arr} instead.
 */
class Arr extends Collection\Arr
{
    /**
     * Make a simple array consisting of key=>value pairs, that can be used
     * in select-boxes in forms.
     *
     * @param array  $array
     * @param string $key
     * @param string $value
     *
     * @return array
     *
     * @deprecated since 3.3, to be removed in 4.0. Use {@see array_column} or {@see Arr::column} instead.
     */
    public static function makeValuePairs($array, $key, $value)
    {
        Deprecated::method(3.3, 'Use array_column() or Arr::column() instead.');

        if (!is_array($array)) {
            return [];
        }

        return self::column($array, $value, $key);
    }

    /**
     * This is the same as {@see array_replace_recursive}.
     * Use that instead or the smarter {@see replaceRecursive}.
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     *
     * @deprecated since 3.3, to be removed in 4.0.
     */
    public static function mergeRecursiveDistinct(array &$array1, array &$array2)
    {
        Deprecated::method(3.3, 'Use array_replace_recursive() or Arr::replaceRecursive() instead.');

        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            // if $key = 'accept_file_types, don't merge.
            if ($key == 'accept_file_types') {
                $merged[$key] = $array2[$key];
                continue;
            }

            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = static::mergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Use {@see isIndexed} or {@see isAssociative} instead.
     *
     * This is the same as isIndexed but it does not check if
     * array is zero-indexed and has sequential keys.
     *
     * @param array $arr
     *
     * @return bool True if indexed, false if associative
     *
     * @deprecated since 3.3, to be removed in 4.0.
     */
    public static function isIndexedArray(array $arr)
    {
        Deprecated::method(3.3, 'Use Arr::isIndexed() or Arr::isAssociative() instead.');

        foreach ($arr as $key => $val) {
            if ($key !== (int) $key) {
                return false;
            }
        }

        return true;
    }
}
