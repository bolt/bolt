<?php

namespace Bolt\Filesystem;

use League\Flysystem\PluginInterface;
use League\Flysystem\FilesystemInterface;
use Bolt\Application;

class PublicUrlPlugin extends AdapterPlugin
{


    public function getMethod()
    {
        return 'url';
    }


    public function getLocalUrl($path)
    {
        $prefix = $this->app['resources']->getUrl($this->namespace);

        return $prefix.$path;
    }
}
