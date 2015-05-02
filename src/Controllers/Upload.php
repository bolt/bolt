<?php

namespace Bolt\Controllers;

use Bolt\Application;
use Bolt\Filesystem\FlysystemContainer;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Silex;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Sirius\Upload\Handler as UploadHandler;
use Sirius\Upload\Result\Collection;
use Sirius\Upload\Result\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Upload implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(Silex\Application $app)
    {
        // This exposes the main upload object as a service
        $app['upload'] = function () use ($app) {
            $allowedExensions = $app['config']->get('general/accept_file_types');
            $uploadHandler = new UploadHandler($app['upload.container']);
            $uploadHandler->setPrefix($app['upload.prefix']);
            $uploadHandler->setOverwrite($app['upload.overwrite']);
            $uploadHandler->addRule('extension', array('allowed' => $allowedExensions));

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
        };

        // This exposes the file container as a configurabole object please refer to:
        // Sirius\Upload\Container\ContainerInterface
        // Any compatible file handler can be used.
        $app['upload.container'] = function () use ($app) {
            $base = $app['resources']->getPath($app['upload.namespace']);
            if (!is_writable($base)) {
                throw new \RuntimeException("Unable to write to upload destination. Check permissions on $base", 1);
            }
            $container = new FlysystemContainer($app['filesystem']->getFilesystem($app['upload.namespace']));

            return $container;
        };

        // This allows multiple upload locations, all prefixed with a namespace. The default is /files
        // Note, if you want to provide an alternative namespace, you must set a path on the $app['resources']
        // service
        $app['upload.namespace'] = 'files';

        // This gets prepended to all file saves, can be reset to "" or add your own closure for more complex ones.
        $app['upload.prefix'] = date('Y-m') . '/';

        $app['upload.overwrite'] = false;
    }

    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];
        $controller = $this;
        $func = function (Silex\Application $app, Request $request) use ($controller) {
            if ($handler = $request->get('handler')) {
                $parser = function ($setting) use ($app) {
                    $parts = explode('://', $setting);
                    if (count($parts) == 2) {
                        $namespace = $parts[0];
                        array_shift($parts);
                    } else {
                        $namespace = $app['upload.namespace'];
                    }
                    $prefix = rtrim($parts[0], '/') . '/';

                    return array($namespace, $prefix);
                };

                // This block handles the more advanced functionality where multiple upload
                // handlers are provided. Only the first one is returned as a result, the result
                // of this first upload is then attempted to copy to the remaining handlers.
                if (is_array($handler)) {
                    list($namespace, $prefix) = $parser($handler[0]);
                    $app['upload.namespace'] = $namespace;
                    $app['upload.prefix'] = $prefix;
                    $result = $controller->uploadFile($app, $request, $namespace);

                    array_shift($handler);
                    $original = $namespace;

                    if (count($result)) {
                        $result = $result[0];
                        foreach ($handler as $copy) {
                            list($namespace, $prefix) = $parser($copy);
                            $manager = $app['filesystem'];
                            $manager->put(
                                $namespace . '://' . $prefix . basename($result['name']),
                                $manager->read($original . '://' . $result['name'])
                            );
                        }
                    }

                    return new JsonResponse($result, Response::HTTP_OK, array('Content-Type' => 'text/plain'));
                } else {
                    list($namespace, $prefix) = $parser($handler);
                }
                $app['upload.namespace'] = $namespace;
                $app['upload.prefix'] = $prefix;
            } else {
                $namespace = $app['upload.namespace'];
            }

            return new JsonResponse(
                $controller->uploadFile($app, $request, $namespace),
                Response::HTTP_OK,
                array('Content-Type' => 'text/plain')
            );
        };
        $ctr->match('/{namespace}', $func)
            ->before(array($this, 'before'))
            ->value('namespace', 'files')
            ->bind('upload');

        return $ctr;
    }

    public function uploadFile(Silex\Application $app, Request $request, $namespace, $files = null)
    {
        $app['upload.namespace'] = $namespace;

        if ($files === null) {
            $files = $request->files->get($namespace);
        }

        if (!$files) {
            return array();
        }
        $filesToProcess = array();
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $filesToProcess[] = array(
                    'name'     => $file->getClientOriginalName(),
                    'tmp_name' => $file->getPathName()
                );
            } else {
                $filesToProcess[] = $file;
            }
        }

        /** @var Collection|File $result */
        $result = $app['upload']->process($filesToProcess);

        if ($result->isValid()) {
            $result->confirm();
            if ($result instanceof File) {
                $successfulFiles = array($result->name);
            } elseif ($result instanceof Collection) {
                $successfulFiles = array();
                foreach ($result as $resultFile) {
                    $successfulFiles[] = array(
                        'url'  => $namespace . '/' . $resultFile->name,
                        'name' => $resultFile->name
                    );
                }
            }

            return $successfulFiles;
        } else {
            try {
                $result->clear();
            } catch (\Exception $e) {
            }
            $errorFiles = array();
            foreach ($result as $resultFile) {
                $errors = $resultFile->getMessages();
                $errorFiles[] = array(
                    'url'   => $namespace . '/' . $resultFile->original_name,
                    'name'  => $resultFile->original_name,
                    'error' => $errors[0]->__toString()
                );
            }

            return $errorFiles;
        }
    }

    /**
     * Middleware function to check whether a user is logged on.
     *
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function before(Request $request, Application $app)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.backend.before');

        // If there's no active session, don't do anything.
        if (!$app['users']->isValidSession()) {
            $app->abort(Response::HTTP_NOT_FOUND, 'You must be logged in to use this.');
        }

        if (!$app['users']->isAllowed("files:uploads")) {
            $app['session']->getFlashBag()->add('error', Trans::__('You do not have the right privileges to upload.'));

            return Lib::redirect('dashboard');
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');

        return null;
    }

    public function boot(Silex\Application $app)
    {
    }
}
