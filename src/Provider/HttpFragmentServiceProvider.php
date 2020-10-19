<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;
use Symfony\Component\HttpKernel\Fragment\EsiFragmentRenderer;
use Symfony\Component\HttpKernel\Fragment\HIncludeFragmentRenderer;
use Symfony\Component\HttpKernel\EventListener\FragmentListener;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\UriSigner;

/**
 * {@inheritdoc}
 */
class HttpFragmentServiceProvider extends \Silex\Provider\HttpFragmentServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        parent::register($app);

        $app['uri_signer.secret'] = $app->share(function ($app) {

            $path = dirname(dirname($app['cache']->getDirectory())) . '/.secret';

            if (file_exists($path)) {
                $secret = file_get_contents($path);
                return $secret;
            }

            $secret = bin2hex(random_bytes(64));
            file_put_contents($path, $secret);
            return $secret;
        });
    }
}
