<?php

namespace Bolt\Provider;

use Bolt\TemplateChooser;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class TemplateChooserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['templatechooser'] = 
            function ($app) {
                $chooser = new TemplateChooser($app['config']);

                return $chooser;
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
