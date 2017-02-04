<?php

namespace Bolt\Provider;

use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\UploadContainer;
use Silex\Application;
use Pimple\ServiceProviderInterface;
use Sirius\Upload\Handler as UploadHandler;
use Pimple\Container;

/**
 * Class to handle uploads
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UploadServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // This exposes the main upload object as a service
        $app['upload'] = 
            function () use ($app) {
                $allowedExtensions = $app['config']->get('general/accept_file_types');
                $uploadHandler = new UploadHandler($app['upload.container']);
                $uploadHandler->setPrefix($app['upload.prefix']);
                $uploadHandler->setOverwrite($app['upload.overwrite']);
                $uploadHandler->addRule('extension', ['allowed' => $allowedExtensions]);

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
        ;

        // This exposes the file container as a configurable object please refer to:
        // Sirius\Upload\Container\ContainerInterface
        // Any compatible file handler can be used.
        $app['upload.container'] = 
            function () use ($app) {
                /** @var Filesystem $filesystem */
                $filesystem = $app['filesystem']->getFilesystem($app['upload.namespace']);
                $container = new UploadContainer($filesystem);

                return $container;
            }
        ;

        // This allows multiple upload locations, all prefixed with a namespace. The default is /files
        // Note, if you want to provide an alternative namespace, you must set a path on the $app['resources']
        // service
        $app['upload.namespace'] = 'files';

        // This gets prepended to all file saves, can be reset to "" or add your own closure for more complex ones.
        $app['upload.prefix'] = date('Y-m') . '/';

        $app['upload.overwrite'] = false;
    }
}
