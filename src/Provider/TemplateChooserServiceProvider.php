<?php

namespace Bolt\Provider;

use Bolt\TemplateChooser;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class TemplateChooserServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['templatechooser'] = function ($app) {
            $chooser = new TemplateChooser($app['config']);

            return $chooser;
        };
    }
}
