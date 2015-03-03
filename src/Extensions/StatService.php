<?php

namespace Bolt\Extensions;

use Bolt\Application;

class StatService
{
    public $app;
    public $urls = array(
        'install' => 'stat/install/%s/%s'
    );

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Record an extension install.
     *
     * @param string $package
     * @param string $version
     */
    public function recordInstall($package, $version)
    {
        $url = sprintf($this->app['extend.site'] . $this->urls['install'], $package, $version);

        try {
            $this->app['guzzle.client']->head($url)->send();
        } catch (\Exception $e) {
            $this->app['logger.system']->critical($e->getMessage(), array('event' => 'exception', 'exception' => $e));
        }
    }
}
