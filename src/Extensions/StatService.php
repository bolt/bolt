<?php

namespace Bolt\Extensions;

use Bolt\Application;

class StatService
{
    public $app;
    public $urls = array(
        'install' => 'stat/install/%s/%s'
    );

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function recordInstall($package, $version)
    {
        $url = sprintf($this->app['extend.site'].$this->urls['install'], $package, $version);
        return @file_get_contents($url);
    }
}
