<?php

namespace Bolt\Helpers;

class Arr
{
    /**
     * Replaces values from second array into first array recursively.
     *
     * This differs from {@see array_replace_recursive} in a couple ways:
     *  - Lists (indexed arrays) from second array completely replace list in first array.
     *  - Null values from second array do not replace lists or associative arrays in first
     *    (they do still replace scalar values).
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array The combined array
     */
    public static function replaceRecursive(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && static::isAssociative($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = static::replaceRecursive($merged[$key], $value);
            } elseif ($value === null && isset($merged[$key]) && is_array($merged[$key])) {
                continue;
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Returns whether the given item is an indexed array - zero indexed and sequential.
     *
     * Note: Empty arrays are.
     *
     * @param array $array
     *
     * @return bool
     */
    public static function isIndexed($array)
    {
        if (!is_array($array)) {
            return false;
        }

        return !static::isAssociative($array);
    }

    /**
     * Returns whether the given item is an associative array.
     *
     * Note: Empty arrays are not.
     *
     * @param array $array
     *
     * @return bool
     */
    public static function isAssociative($array)
    {
        if (!is_array($array) || $array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Make a simple array consisting of key=>value pairs, that can be used
     * in select-boxes in forms.
     *
     * @param array  $array
     * @param string $key
     * @param string $value
     *
     * @return array
     */
    public static function makeValuePairs($array, $key, $value)
    {
        $tempArray = [];

        if (is_array($array)) {
            foreach ($array as $item) {
                if (empty($key)) {
                    $tempArray[] = $item[$value];
                } else {
                    $tempArray[$item[$key]] = $item[$value];
                }
            }
        }

        return $tempArray;
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
     * @return boolean True if indexed, false if associative
     *
     * @deprecated since 3.3, to be removed in 4.0.
     */
    public static function isIndexedArray(array $arr)
    {
        foreach ($arr as $key => $val) {
            if ($key !== (int) $key) {
                return false;
            }
        }

        return true;
    }
}
