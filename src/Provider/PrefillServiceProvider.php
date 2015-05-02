<?php

namespace Bolt\Provider;

use Bolt\Storage\Prefill;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PrefillServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['prefill'] = $app->share(
            function ($app) {
                /** @deprecated remove $app['deprecated.php'] for PHP 5.3 derp */
                $prefill = new Prefill($app['guzzle.client'], $app['deprecated.php']);

                return $prefill;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
