<?php

namespace Bolt\Provider;

use Bolt\Filesystem\UploadContainer;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Sirius\Upload\Handler as UploadHandler;

/**
 * Class to handle uploads
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UploadServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // This exposes the main upload object as a service
        $app['upload'] = $app->share(
            function () use ($app) {
                $allowedExensions = $app['config']->get('general/accept_file_types');
                $uploadHandler = new UploadHandler($app['upload.container']);
                $uploadHandler->setPrefix($app['upload.prefix']);
                $uploadHandler->setOverwrite($app['upload.overwrite']);
                $uploadHandler->addRule('extension', ['allowed' => $allowedExensions]);

                $pattern = $app['config']->get('general/upload/pattern', '[^A-Za-z0-9\.]+');
                $replacement = $app['config']->get('general/upload/replacement', '-');
                $lowercase = $app['config']->get('general/upload/lowercase', true);

                $uploadHandler->setSanitizerCallback(
                    function ($filename) use ($pattern, $replacement, $lowercase) {
                        if ($lowercase) {
                            return preg_replace("/$pattern/", $replacement, strtolower($filename));
                        }

                        return preg_replace("/$pattern/", $replacement, $filename);
                    }
                );

                return $uploadHandler;
            }
        );

        // This exposes the file container as a configurable object please refer to:
        // Sirius\Upload\Container\ContainerInterface
        // Any compatible file handler can be used.
        $app['upload.container'] = $app->share(
            function () use ($app) {
                $base = $app['resources']->getPath($app['upload.namespace']);
                if (!is_writable($base)) {
                    throw new \RuntimeException("Unable to write to upload destination. Check permissions on $base", 1);
                }
                $container = new UploadContainer($app['filesystem']->getFilesystem($app['upload.namespace']));

                return $container;
            }
        );

        // This allows multiple upload locations, all prefixed with a namespace. The default is /files
        // Note, if you want to provide an alternative namespace, you must set a path on the $app['resources']
        // service
        $app['upload.namespace'] = 'files';

        // This gets prepended to all file saves, can be reset to "" or add your own closure for more complex ones.
        $app['upload.prefix'] = date('Y-m') . '/';

        $app['upload.overwrite'] = false;
    }

    public function boot(Application $app)
    {
    }
}
