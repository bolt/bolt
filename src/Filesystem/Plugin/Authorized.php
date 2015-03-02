<?php

namespace Bolt\Filesystem\Plugin;

class Authorized extends AdapterPlugin
{
    public function getMethod()
    {
        return 'authorized';
    }

    public function handle()
    {
        $args = func_get_args();
        $filepath = reset($args);

        return $this->app['filepermissions']->authorized($this->namespace, $filepath);
    }
}
