<?php

namespace Bolt\Helpers;

class Arr
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
     */
    public static function makeValuePairs($array, $key, $value)
    {
        $tempArray = array();

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
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param array $array2
     *
     * @return array
     *
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @author Bob den Otter for Bolt-specific excludes
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
     * Check if an array is indexed or associative.
     *
     * @param array $arr
     *
     * @return boolean True if indexed, false if associative
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
