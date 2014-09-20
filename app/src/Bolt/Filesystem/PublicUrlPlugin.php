<?php

namespace Bolt\Filesystem;

class PublicUrlPlugin extends AdapterPlugin
{

    public function getMethod()
    {
        return 'url';
    }

    public function getLocalUrl($path)
    {
        $prefix = $this->app['resources']->getUrl($this->namespace);

        return $prefix . $path;
    }
}
