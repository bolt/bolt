<?php

namespace Bolt\Provider;

use Bolt\TemplateChooser;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class TemplateChooserServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['templatechooser'] = 
            function ($app) {
                $chooser = new TemplateChooser($app['config']);

                return $chooser;
            }
        ;
    }
}
