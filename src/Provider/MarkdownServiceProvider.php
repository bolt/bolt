<?php

namespace Bolt\Provider;

use ParsedownExtra as Markdown;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MarkdownServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['markdown'] = function () {
            $markdown = new Markdown();

            return $markdown;
        };
    }
}
