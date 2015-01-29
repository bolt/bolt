<?php
namespace Bolt\Tests\Extensions\Mock;

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

    public function addCritical($log)
    {
        $this->logs[] = $log;
    }

    public function addError($log)
    {
        $this->logs[] = $log;
    }

    public function lastLog()
    {
        return array_pop($this->logs);
    }

}
