<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Library as Lib;

class PublicUrl extends AdapterPlugin
{
    public function getMethod()
    {
        return 'url';
    }

    public function getLocalUrl($path)
    {
        $filesystem = $this->app['filesystem']->getFilesystem($this->namespace);
        $prefix = '' // <- I guess something like $filesystem->getPath();

        return $prefix . Lib::safeFilename($path);
    }
}
