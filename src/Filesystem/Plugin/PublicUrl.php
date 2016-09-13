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
        $prefix = $this->app['resources']->getUrl($this->namespace);

        return $prefix . Lib::safeFilename($path);
    }
}
