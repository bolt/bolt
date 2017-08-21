<?php

namespace Bolt\Provider;

use Bolt\Common\Deprecated;
use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Bolt\Filesystem\Exception\DefaultImageNotFoundException;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\ImageInterface;
use Bolt\Filesystem\Matcher;
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

        $app['thumbnails.creator'] = $app->share($app->extend('thumbnails.creator', function ($creator, $app) {
            ImageResource::setNormalizeJpegOrientation($app['config']->get('general/thumbnails/exif_orientation', true));
            ImageResource::setQuality($app['config']->get('general/thumbnails/quality', 80));

            return $creator;
        }));

        $app['thumbnails.filesystems'] = [
            'files',
            'themes',
        ];

        $app['thumbnails.save_files'] = function ($app) {
            return $app['config']->get('general/thumbnails/save_files');
        };

        $app['thumbnails.filesystem_cache'] = $app->share(function ($app) {
            if ($app['thumbnails.save_files'] === false) {
                return null;
            }
            if (!$app['filesystem']->hasFilesystem('web')) {
                return null;
            }

            return $app['filesystem']->getFilesystem('web');
        });

        $app['thumbnails.caching'] = function ($app) {
            return $app['config']->get('general/caching/thumbnails');
        };

        $app['thumbnails.cache'] = $app->share(function ($app) {
            if ($app['thumbnails.caching'] === false) {
                return null;
            }

            return $app['cache'];
        });

        $app['thumbnails.default_image'] = $app->share(function ($app) {
            return $this->findDefaultImage($app, 'notfound');
        });

        $app['thumbnails.error_image'] = $app->share(function ($app) {
            return $this->findDefaultImage($app, 'error');
        });

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

    /**
     * @param Application $app
     * @param string      $name
     *
     * @return ImageInterface
     */
    private function findDefaultImage(Application $app, $name)
    {
        $matcher = new Matcher($app['filesystem'], ['web', 'bolt_assets', 'themes', 'files']);

        $configKey = "thumbnails/{$name}_image";
        $path = $app['config']->get("general/$configKey");

        // Trim "view/" from front of path for BC.
        if (strpos($path, 'view/') === 0) {
            Deprecated::warn(
                'Not specifying a mount point for thumbnail paths',
                3.3,
                'Take a look at the config.yml.dist for how to update them.'
            );
            $path = substr($path, 5);
        }

        try {
            $image = $matcher->getImage($path);
        } catch (FileNotFoundException $e) {
            throw new DefaultImageNotFoundException(
                sprintf(
                    'Unable to locate %s image for thumbnails. Looked for: "%s". Please update "%s" in config.yml.',
                    $name,
                    $path,
                    $configKey
                ),
                $path,
                $e
            );
        }

        return $image;
    }
}
