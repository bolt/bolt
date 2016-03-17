<?php

namespace Bolt\Provider;

use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Bolt\Filesystem\Handler\Image;
use Bolt\Thumbs;
use Bolt\Thumbs\ImageResource;
use Silex\Application;
use Silex\ServiceProviderInterface;

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
    public function register(Application $app)
    {
        if (!isset($app['thumbnails'])) {
            $app->register(new Thumbs\ServiceProvider());
        }

        $app['thumbnails.filesystems'] = [
            'files',
            'themes',
        ];

        $app['thumbnails.save_files'] = $app['config']->get('general/thumbnails/save_files');

        $app['thumbnails.filesystem_cache'] = $app->share(function ($app) {
            if ($app['thumbnails.save_files'] === false) {
                return null;
            }
            if (!$app['filesystem']->hasFilesystem('web')) {
                return null;
            }

            return $app['filesystem']->getFilesystem('web');
        });

        $app['thumbnails.caching'] = $app['config']->get('general/caching/thumbnails');

        $app['thumbnails.cache'] = $app->share(function ($app) {
            if ($app['thumbnails.caching'] === false) {
                return null;
            }

            return $app['cache'];
        });

        $app['thumbnails.default_image'] = $app->share(function ($app) {
            $finder = new Thumbs\Finder($app['filesystem'], ['app', 'themes', 'files'], new Image());

            return $finder->find($app['config']->get('general/thumbnails/notfound_image'));
        });

        $app['thumbnails.error_image'] = $app->share(function ($app) {
            $finder = new Thumbs\Finder($app['filesystem'], ['app', 'themes', 'files'], new Image());

            return $finder->find($app['config']->get('general/thumbnails/error_image'));
        });

        $app['thumbnails.cache_time'] = $app['config']->get('general/thumbnails/browser_cache_time');

        $app['thumbnails.limit_upscaling'] = !$app['config']->get('general/thumbnails/allow_upscale', false);

        ImageResource::setNormalizeJpegOrientation($app['config']->get('general/thumbnails/exif_orientation', true));
        ImageResource::setQuality($app['config']->get('general/thumbnails/quality', 80));
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
