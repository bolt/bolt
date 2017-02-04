<?php

namespace Bolt\Provider;

use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\Image;
use Bolt\Filesystem\Matcher;
use Bolt\Thumbs;
use Bolt\Thumbs\ImageResource;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Pimple\Container;

/**
 * Register thumbnails service.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ThumbnailsServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        if (!isset($app['thumbnails'])) {
            $app->register(new Thumbs\ServiceProvider());
        }

        $app['thumbnails.creator'] = $app->extend('thumbnails.creator', function ($creator, $app) {
            ImageResource::setNormalizeJpegOrientation($app['config']->get('general/thumbnails/exif_orientation', true));
            ImageResource::setQuality($app['config']->get('general/thumbnails/quality', 80));

            return $creator;
        });

        $app['thumbnails.filesystems'] = [
            'files',
            'themes',
        ];

        $app['thumbnails.save_files'] = function ($app) {
            return $app['config']->get('general/thumbnails/save_files');
        };

        $app['thumbnails.filesystem_cache'] = function ($app) {
            if ($app['thumbnails.save_files'] === false) {
                return null;
            }
            if (!$app['filesystem']->hasFilesystem('web')) {
                return null;
            }

            return $app['filesystem']->getFilesystem('web');
        };

        $app['thumbnails.caching'] = function ($app) {
            return $app['config']->get('general/caching/thumbnails');
        };

        $app['thumbnails.cache'] = function ($app) {
            if ($app['thumbnails.caching'] === false) {
                return null;
            }

            return $app['cache'];
        };

        $app['thumbnails.default_image'] = function ($app) {
            $matcher = new Matcher($app['filesystem'], ['web', 'bolt_assets', 'themes', 'files']);
            try {
                return $matcher->getImage($app['config']->get('general/thumbnails/notfound_image'));
            } catch (FileNotFoundException $e) {
                return new Image();
            }
        };

        $app['thumbnails.error_image'] = function ($app) {
            $matcher = new Matcher($app['filesystem'], ['web', 'bolt_assets', 'themes', 'files']);
            try {
                return $matcher->getImage($app['config']->get('general/thumbnails/error_image'));
            } catch (FileNotFoundException $e) {
                return new Image();
            }
        };

        $app['thumbnails.default_imagesize'] = function ($app) {
            return $app['config']->get('general/thumbnails/default_image');
        };

        $app['thumbnails.cache_time'] = function ($app) {
            return $app['config']->get('general/thumbnails/browser_cache_time');
        };

        $app['thumbnails.limit_upscaling'] = function ($app) {
            return !$app['config']->get('general/thumbnails/allow_upscale', false);
        };

        $app['thumbnails.only_aliases'] = function ($app) {
            return $app['config']->get('general/thumbnails/only_aliases', false);
        };
        $app['thumbnails.aliases'] = function ($app) {
            return $app['config']->get('theme/thumbnails/aliases', []);
        };
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
        $app['dispatcher']->addListener(ControllerEvents::MOUNT, function (MountEvent $event) {
            $app = $event->getApp();
            $event->mount($app['controller.thumbnails.mount_prefix'], $app['controller.thumbnails']);
        });
    }
}
