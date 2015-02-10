<?php

namespace Bolt\Filesystem\Plugin;

class Authorized extends AdapterPlugin {

    public function getMethod()
    {
        return 'authorized';
    }

    public function handle()
    {
        $filepath = reset(func_get_args());
        return $this->app['filepermissions']->authorized($this->namespace, $filepath);
    }
}
