<?php
namespace Bolt\Tests\Extensions\Mock;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Logger
{
    public $logs = [];

    public function add($message, $level)
    {
        $this->logs[] =  $message;
    }

    public function addRecord($level, $message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function addDebug($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function addInfo($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function addNotice($message, array $context = [])
    {
        $this->logs[] = $message;
    }
    public function addWarning($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function addError($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function addCritical($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function addAlert($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function addEmergency($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function debug($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function info($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function notice($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function warn($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function warning($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function err($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function error($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function crit($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function critical($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function alert($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function emerg($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function emergency($message, array $context = [])
    {
        $this->logs[] = $message;
    }

    public function lastLog()
    {
        return array_pop($this->logs);
    }
}
