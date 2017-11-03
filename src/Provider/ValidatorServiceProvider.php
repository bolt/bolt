<?php

namespace Bolt\Provider;

use Silex\Application;
use Silex\Provider;
use Symfony\Component\Validator\Mapping\Cache\DoctrineCache;
use Symfony\Component\Validator\Mapping\Factory\LazyLoadingMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;

/**
 * Validator service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ValidatorServiceProvider extends Provider\ValidatorServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        parent::register($app);

        $app['validator.mapping.class_metadata_factory'] = $app->share(function ($app) {
            return new LazyLoadingMetadataFactory(new StaticMethodLoader(), new DoctrineCache($app['cache']));
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}
