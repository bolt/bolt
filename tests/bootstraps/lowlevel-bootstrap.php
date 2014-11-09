<?php

namespace Bolt\Configuration;

/**
 * This is a hack to overload the native functions for error testing
 *
 **/

class ErrorSimulator {
    static $errorType;
    
    public static function simulateError($app, $type)
    {
        if($type == 'core') {
            self::$errorType = array(
                "type"=> E_ERROR,
                "file"=> $app['resources']->getPath('app'),
                "line"=> 16
            );
        }
        if($type == 'extensions') {
            self::$errorType = array(
                "type"=> E_ERROR,
                "file"=> $app['resources']->getPath('rootpath').'/vendor',
                "line"=> 1
            );
        }
        if($type == 'vendor') {
            self::$errorType = array(
                "type"=> E_ERROR,
                "file"=> $app['resources']->getPath('extensions'),
                "line"=> 1
            );
        }
        if($type == 'unknown') {
            self::$errorType = array(
                "type"=> E_ERROR,
                "file"=> __FILE__,
                "line"=> 1
            );
        }
    }
}


function error_get_last()
{
    return ErrorSimulator::$errorType;
}

