<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Extensions\ExtensionInterface;
use Bolt\Application;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class Logger
{
    
    public $logs = array();

    
    public function add($log, $level) 
    {
        $this->logs[] =  $log;   
    }

    
    public function lastLog()
    {
        return array_pop($this->logs);
    }

   
}
