<?php

namespace Bolt\Provider;

use ParsedownExtra as Markdown;
use Silex\Application;
use Silex\ServiceProviderInterface;

class MarkdownServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['markdown'] = $app->share(
            function ($app) {
                $markdown = new Markdown();

                return $markdown;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
