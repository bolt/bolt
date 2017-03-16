<?php

namespace Bolt\Tests\Session\Handler\Factory\Mock;

class MockRedis extends \Redis
{
    public $host;
    public $port;
    public $persistent;
    public $timeout;
    public $retryInterval;
    public $password;
    public $database;
    public $options = [];

    public function connect($host, $port = 6379, $timeout = 0.0, $retryInterval = 0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->retryInterval = $retryInterval;
        $this->persistent = false;
    }

    public function pconnect($host, $port = 6379, $timeout = 0.0)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
        $this->persistent = true;
    }

    public function auth($password)
    {
        $this->password = $password;
    }

    public function select($dbindex)
    {
        $this->database = $dbindex;
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }
}
