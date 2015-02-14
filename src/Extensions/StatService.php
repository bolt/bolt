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
        $url = sprintf($this->app['extend.site'] . $this->urls['install'], $package, $version);

        try {
            $this->app['logger.system']->addInfo("Installed $package $version", array('event' => 'extensions'));
            $this->app['guzzle.client']->head($url)->send();
        } catch (\Exception $e) {
            $this->app['logger.system']->addCritical($e->getMessage(), array('event' => 'exception', 'exception' => $e));
        }

    }
}
