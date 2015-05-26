<?php
namespace Bolt\Tests;

/**
 * System mock
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BoltSystemMock
{
    public static $values = [];

    public static function set($function, $values)
    {
        self::$values[$function] = $values;
    }

    public static function get($function)
    {
        if (isset(self::$values[$function])) {
            return self::$values[$function];
        }
    }
}
