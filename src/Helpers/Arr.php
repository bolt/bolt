<?php

namespace Bolt\Helpers;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use Traversable;
use Webmozart\Assert\Assert;

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
     * @param Traversable|array $array
     *
     * @return bool
     */
    public static function isIndexed($array)
    {
        if (!is_iterable($array)) {
            return false;
        }

        return !static::isAssociative($array);
    }

    /**
     * Returns whether the given item is an associative array.
     *
     * Note: Empty arrays are not.
     *
     * @param Traversable|array  $array
     *
     * @return bool
     */
    public static function isAssociative($array)
    {
        if ($array instanceof Traversable) {
            $array = iterator_to_array($array);
        }
        if (!is_array($array) || $array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Returns whether a key exists from an array (or ArrayAccess object) using a path syntax to retrieve nested data.
     *
     * This method does not allow for keys that contain "/". You must traverse
     * the array manually or using something more advanced like JMESPath to
     * work with keys that contain "/".
     *
     *     // Check if the the bar key of a set of nested arrays exists.
     *     // This is equivalent to isset($data['foo']['baz']['bar']) but won't
     *     // throw warnings for missing keys.
     *     has($data, 'foo/baz/bar');
     *
     * Note: isset() with nested data, `isset($data['a']['b'])`, won't call offsetExists for 'a'.
     * It calls offsetGet('a') and if 'a' doesn't exist and an isset isn't done in offsetGet, a warning is thrown.
     * It could be argued that that ArrayAccess object should fix its implementation of offsetGet, and I would agree.
     * But I think this can have nicer syntax.
     *
     * @param array|ArrayAccess $data Data to check values from
     * @param string            $path Path to traverse and check keys from
     *
     * @return bool
     */
    public static function has($data, $path)
    {
        static::assertArrayAccessible($data);
        Assert::stringNotEmpty($path);

        $path = explode('/', $path);

        while (null !== ($part = array_shift($path))) {
            if (!($data instanceof ArrayAccess) && !is_array($data)) {
                return false;
            }
            if (!(isset($data[$part]) || array_key_exists($part, $data))) {
                return false;
            }
            $data = $data[$part];
        }

        return true;
    }

    /**
     * Gets a value from an array (or ArrayAccess object) using a path syntax to retrieve nested data.
     *
     * This method does not allow for keys that contain "/". You must traverse
     * the array manually or using something more advanced like JMESPath to
     * work with keys that contain "/".
     *
     *     // Get the bar key of a set of nested arrays.
     *     // This is equivalent to $data['foo']['baz']['bar'] but won't
     *     // throw warnings for missing keys.
     *     get($data, 'foo/baz/bar');
     *
     * This code is adapted from Michael Dowling in his Guzzle library.
     *
     * @param array|ArrayAccess $data    Data to retrieve values from
     * @param string            $path    Path to traverse and retrieve a value from
     * @param mixed|null        $default Default value to return if key does not exist
     *
     * @return mixed|null
     */
    public static function get($data, $path, $default = null)
    {
        static::assertArrayAccessible($data);
        Assert::stringNotEmpty($path);

        $path = explode('/', $path);

        while (null !== ($part = array_shift($path))) {
            if ((!is_array($data) && !($data instanceof ArrayAccess)) || !isset($data[$part])) {
                return $default;
            }
            $data = $data[$part];
        }

        return $data;
    }

    /**
     * Set a value in a nested array (or ArrayAccess object) key.
     * Keys will be created as needed to set the value.
     *
     * This function does not support keys that contain "/" or "[]" characters
     * because these are special tokens used when traversing the data structure.
     * A value may be appended to an existing array by using "[]" as the final
     * key of a path.
     *
     *     get($data, 'foo/baz'); // null
     *     set($data, 'foo/baz/[]', 'a');
     *     set($data, 'foo/baz/[]', 'b');
     *     get($data, 'foo/baz');
     *     // Returns ['a', 'b']
     *
     * Note: To set values not directly under ArrayAccess objects their
     * offsetGet() method needs to be defined as return by reference.
     *
     *     public function &offsetGet($offset) {}
     *
     * This code is adapted from Michael Dowling in his Guzzle library.
     *
     * @param array|ArrayAccess $data  Data to modify by reference
     * @param string            $path  Path to set
     * @param mixed             $value Value to set at the key
     *
     * @throws \RuntimeException when trying to set using a nested path
     *     that travels through a scalar value or an object whose
     *     offsetGet method isn't marked as return by reference.
     */
    public static function set(&$data, $path, $value)
    {
        static::assertArrayAccessible($data);
        Assert::stringNotEmpty($path);

        $queue = explode('/', $path);
        // Optimization for simple sets.
        if (count($queue) === 1) {
            if ($path === '[]') {
                $data[] = $value;
            } else {
                $data[$path] = $value;
            }
            return;
        }

        $invalidKey = []; // array so it can be modified after being passed by reference to error handler closure.
        $prev = set_error_handler('var_dump');
        restore_error_handler();
        set_error_handler(function ($type, $message, $file, $line) use ($prev, $path, &$invalidKey) {
            $regex = '/Indirect modification of overloaded element of ([\w\\\\]+) has no effect/';
            if (preg_match($regex, $message, $matches)) {
                throw new RuntimeException(
                    "Cannot to set \"$path\", because \"{$invalidKey['key']}\" is an {$matches[1]} which has not " .
                    "defined its offsetGet() method as return by reference."
                );
            } elseif ($prev) {
                return call_user_func($prev, $type, $message, $file, $line);
            } else {
                // return false to let PHP handle error
                return false;
            }
        });
        try {
            $current =& $data;
            while (null !== ($key = array_shift($queue))) {
                if (!is_array($current) && !($current instanceof ArrayAccess)) {
                    throw new RuntimeException(
                        "Cannot set \"$path\", because \"{$invalidKey['key']}\" is already set and not an array " .
                        "or an object implementing ArrayAccess."
                    );
                } elseif (!$queue) {
                    if ($key === '[]') {
                        $current[] = $value;
                    } else {
                        $current[$key] = $value;
                    }
                } elseif (isset($current[$key])) {
                    $current =& $current[$key];
                } else {
                    $current[$key] = [];
                    $current =& $current[$key];
                }
                $invalidKey['key'] = $key;
            }
        } finally {
            restore_error_handler();
        }
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

    protected static function assertArrayAccessible($value)
    {
        if (!is_array($value) && !($value instanceof ArrayAccess)) {
            throw new InvalidArgumentException(
                'Expected an array or an object implementing ArrayAccess. Got: ' .
                (is_object($value) ? get_class($value) : gettype($value))
            );
        }
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
     * @return boolean True if indexed, false if associative
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
