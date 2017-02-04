<?php

namespace Bolt\Provider;

use ParsedownExtra as Markdown;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

class MarkdownServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['markdown'] = 
            function () {
                $markdown = new Markdown();

                return $markdown;
            }
        ;
    }
}
