<?php

namespace Bolt\Tests\Session\Handler\Factory\Mock;

class MockMemcached extends \Memcached
{
    const HAVE_SASL = 1;

    private $servers = [];
    private $options = [];
    private $username;
    private $password;
    private $persistentId;

    public function __construct($persistentId, $callback)
    {
        $this->persistentId = $persistentId;
        $callback($this, $persistentId);
    }

    public function getPersistentId()
    {
        return $this->persistentId;
    }

    public function addServer($host, $port, $weight = 1)
    {
        $this->servers[] = [$host, $port, $weight];
    }

    public function addServers(array $servers)
    {
        $this->servers = $servers;
    }

    public function getServerList()
    {
        return $this->servers;
    }

    public function getOption($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : false;
    }

    public function setOption($option, $value)
    {
        $this->options[$value];
    }

    public function setOptions(/** @noinspection PhpSignatureMismatchDuringInheritanceInspection */ $options)
    {
        $this->options = $options;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setSaslAuthData($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function getSaslAuthData()
    {
        return [$this->username, $this->password];
    }
}
