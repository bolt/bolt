<?php

namespace Bolt\Provider;

use ParsedownExtra as Markdown;
use Silex\Application;
use Pimple\ServiceProviderInterface;

class MarkdownServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['markdown'] = 
            function () {
                $markdown = new Markdown();

                return $markdown;
            }
        ;
    }

    public function boot(Application $app)
    {
    }
}
