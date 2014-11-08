<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\Extensions\ExtensionInterface;
use Bolt\Application;

/**
 * Class to mock functionality of cache.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class Cache
{
    
    public $lastkey;
    public $lastvalue;

    
    public function add($log, $level) 
    {
        $this->logs[] =  $log;   
    }
    
    public function save($key, $value)
    {
        $this->lastkey = $key;
        $this->lastvalue = $value;
    }

    
    public function fetch($key)
    {
        if ($this->lastkey == $key) {
            return $this->lastvalue;
        }
        return false;
    }
    
    public function contains($key)
    {
        if ($this->lastkey == $key) {
            return true;
        }
        return false;
    }

   
}
