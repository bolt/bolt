<?php

namespace Bolt\Provider;

use Bolt\TemplateChooser;
use Silex\Application;
use Silex\ServiceProviderInterface;

class TemplateChooserServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['templatechooser'] = $app->share(
            function ($app) {
                $omnisearch = new TemplateChooser($app);

                return $omnisearch;
            }
        );

    }

    public function boot(Application $app)
    {
    }
}
