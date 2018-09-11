<?php

namespace Bolt\Provider;

use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\UploadContainer;
use Cocur\Slugify\Slugify;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Sirius\Upload\Handler as UploadHandler;

/**
 * Class to handle uploads.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UploadServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['upload.sanitizer'] = $app->share(function ($app) {
            $pattern = $app['config']->get('general/upload/pattern', '[^A-Za-z0-9\.]+');
            $lowercase = $app['config']->get('general/upload/lowercase', true);

            return new Slugify([
                'regexp' => "/$pattern/",
                'lowercase' => $lowercase
            ]);
        });

        // This exposes the main upload object as a service
        $app['upload'] = $app->share(
            function () use ($app) {
                $allowedExtensions = $app['config']->get('general/accept_file_types');
                $uploadHandler = new UploadHandler($app['upload.container']);
                $uploadHandler->setPrefix($app['upload.prefix']);
                $uploadHandler->setOverwrite($app['upload.overwrite']);
                $uploadHandler->setAutoconfirm($app['config']->get('general/upload/autoconfirm'));
                $uploadHandler->addRule('extension', ['allowed' => $allowedExtensions]);

                $uploadHandler->setSanitizerCallback(function ($filename) use ($app) {
                    return $app['upload.sanitizer']->slugify($filename, $app['config']->get('general/upload/replacement', '-'));
                });

                return $uploadHandler;
            }
        );

        // This exposes the file container as a configurable object please refer to:
        // Sirius\Upload\Container\ContainerInterface
        // Any compatible file handler can be used.
        $app['upload.container'] = $app->share(
            function () use ($app) {
                /** @var Filesystem $filesystem */
                $filesystem = $app['filesystem']->getFilesystem($app['upload.namespace']);
                $container = new UploadContainer($filesystem);

                return $container;
            }
        );

        // This allows multiple upload locations, all prefixed with a namespace. The default is /files
        // Note, this must be a name of a mounted filesystem (see FilesystemServiceProvider)
        $app['upload.namespace'] = 'files';

        // This gets prepended to all file saves, can be reset to "" or add your own closure for more complex ones.
        $app['upload.prefix'] = date('Y-m') . '/';

        $app['upload.overwrite'] = false;
    }

    public function boot(Application $app)
    {
    }
}
