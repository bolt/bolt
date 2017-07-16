<?php

namespace Bolt\Provider;

use Pimple\Container;
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
    public function register(Container $app)
    {
        parent::register($app);

        $app['validator.mapping.class_metadata_factory'] = function ($app) {
            return new LazyLoadingMetadataFactory(new StaticMethodLoader(), new DoctrineCache($app['cache']));
        };
    }
}
