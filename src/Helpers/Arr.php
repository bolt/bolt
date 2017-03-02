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
     * Return the values from a single column in the input array, identified by the $columnKey.
     *
     * Optionally, an $indexKey may be provided to index the values in the returned array by the
     * values from the $indexKey column in the input array.
     *
     * This supports objects which was added in PHP 7.0. This method can be dropped when support for PHP 5.x is dropped.
     *
     * @param array  $input     A list of arrays or objects from which to pull a column of values.
     * @param string $columnKey Column of values to return.
     * @param string $indexKey  Column to use as the index/keys for the returned array.
     *
     * @return array
     */
    public static function column(array $input, $columnKey, $indexKey = null)
    {
        if (PHP_MAJOR_VERSION > 5) {
            return array_column($input, $columnKey, $indexKey);
        }

        $output = [];

        foreach ($input as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if ($indexKey !== null) {
                if (is_array($row) && array_key_exists($indexKey, $row)) {
                    $keySet = true;
                    $key = (string) $row[$indexKey];
                } elseif (is_object($row) && isset($row->{$indexKey})) {
                    $keySet = true;
                    $key = (string) $row->{$indexKey};
                }
            }

            if ($columnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($columnKey, $row)) {
                $valueSet = true;
                $value = $row[$columnKey];
            } elseif (is_object($row) && isset($row->{$columnKey})) {
                $valueSet = true;
                $value = $row->{$columnKey};
            }

            if ($valueSet) {
                if ($keySet) {
                    $output[$key] = $value;
                } else {
                    $output[] = $value;
                }
            }
        }

        return $output;
    }

    /**
     * @internal This may be removed at any point.
     *
     * @param array $a
     * @param array $b
     *
     * @return array [key, left, right][]
     */
    public static function deepDiff(array $a, array $b)
    {
        if (empty($a)) {
            $a = [];
        }
        if (empty($b)) {
            $b = [];
        }
        $keys = array_keys($a + $b);
        $result = [];

        foreach ($keys as $k) {
            if (empty($a[$k])) {
                $l = null;
            } else {
                $l = $a[$k];
            }
            if (empty($b[$k])) {
                $r = null;
            } else {
                $r = $b[$k];
            }
            if ($l != $r) {
                $result[] = [$k, $l, $r];
            }
        }

        return $result;
    }
}
