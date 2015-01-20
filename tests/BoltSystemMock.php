<?php
namespace Bolt\Tests;

/**
* 
*/
class BoltSystemMock
{
    
    static $values = array();
    
    
    static public function set($function, $values) {
        self::$values[$function] = $values;
    }
    
    static public function get($function) {
        if (isset(self::$values[$function])) {
            return self::$values[$function];
        }
    }
    
}