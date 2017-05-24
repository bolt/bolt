<?php

namespace Bolt\Tests\Session\Handler\Factory\Mock;

class MockMemcache extends \Memcache
{
    private $servers = [];

    public function addServer(
        $host,
        $port = 11211,
        $persistent = true,
        $weight = null,
        $timeout = 1,
        $retry_interval = 15,
        $status = true,
        callable $failure_callback = null
    ) {
        $this->servers[] = [
            'host'           => $host,
            'port'           => $port,
            'persistent'     => $persistent,
            'weight'         => $weight,
            'timeout'        => $timeout,
            'retry_interval' => $retry_interval,
        ];
    }

    public function getServers()
    {
        return $this->servers;
    }
}
