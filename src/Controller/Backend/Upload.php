<?php

namespace Bolt\Controller\Backend;

use Bolt\Translation\Translator as Trans;
use Silex;
use Silex\Application;
use Silex\ControllerCollection;
use Sirius\Upload\Result\Collection;
use Sirius\Upload\Result\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to handle file uploads.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/
class Upload extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->match('/{namespace}', 'controller.backend.users:actionUploadNamespace')
            ->before(array($this, 'before'))
            ->value('namespace', 'files')
            ->bind('upload');

        return $c;
    }

    /**
     * Middleware function to check whether a user is logged on.
     *
     * @param Request     $request The Symfony Request
     * @param Application $app     The application/container
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

        if (!$app['users']->isAllowed('files:uploads')) {
            $this->addFlash('error', Trans::__('You do not have the right privileges to upload.'));

            return $this->redirectToRoute('dashboard');
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');

        return null;
    }

    /**
     * Route to handle file uploads.
     *
     * @param Request $request
     * @param string  $namespace
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function actionUploadNamspace(Request $request, $namespace)
    {
        if ($handler = $request->get('handler')) {
            // Use custom handlers
            if (is_array($handler)) {
                return $this->processCutomUploadHandler($request, $handler);
            } else {
                list($namespace, $prefix) = $this->parser($handler);
                $this->app['upload.namespace'] = $namespace;
                $this->app['upload.prefix'] = $prefix;
            }
        } else {
            $namespace = $this->app['upload.namespace'];
        }

        // Perform the file upload actions and collect the results
        $fileUpload = $this->handleUploadFiles($request, $namespace);

        return $this->json($fileUpload, Response::HTTP_OK, array('Content-Type' => 'text/plain'));
    }

    /**
     * Parse a string and determine the upload prefix and namespace.
     *
     * @param string $handler
     *
     * @return array
     */
    private function parser($handler)
    {
        $parts = explode('://', $handler);
        if (count($parts) === 2) {
            $namespace = $parts[0];
            array_shift($parts);
        } else {
            $namespace = $this->app['upload.namespace'];
        }
        $prefix = rtrim($parts[0], '/') . '/';

        return array($namespace, $prefix);
    }

    /**
     * Perform a file upload.
     *
     * @param Request $request
     * @param string  $namespace
     * @param string  $files
     *
     * @return array
     */
    private function handleUploadFiles(Request $request, $namespace, $files = null)
    {
        $filesToProcess = $this->getFilesToProcess($request, $namespace, $files);

        /** @var \Sirius\Upload\Result\Collection|\Sirius\Upload\Result\File $result */
        $result = $this->app['upload']->process($filesToProcess);

        if ($result->isValid()) {
            // Remove the .lock file attached to the file
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
            // The file that was saved during process() has a .lock file attached
            // and can now be cleared, in the case where form processing fails
            try {
                $result->clear();
            } catch (\Exception $e) {
                // It's an error state anyway
            }

            $errorFiles = array();
            foreach ($result as $resultFile) {
                $errors = $resultFile->getMessages();
                $errorFiles[] = array(
                    'url'   => $namespace . '/' . $resultFile->original_name,
                    'name'  => $resultFile->original_name,
                    'error' => (string) $errors[0]
                );
            }

            return $errorFiles;
        }
    }

    /**
     * Determine the list of files to upload.
     *
     * @param Request $request
     * @param string  $namespace
     * @param string  $files
     *
     * @return array
     */
    private function getFilesToProcess(Request $request, $namespace, $files = null)
    {
        $this->app['upload.namespace'] = $namespace;

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

        return $filesToProcess;
    }

    /**
     * This handles the more advanced functionality where multiple upload handlers
     * are provided. Only the first one is returned as a result, the result
     * of this first upload is then attempted to copy to the remaining handlers.
     *
     * @param Request $request
     * @param array   $handler
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function processCutomUploadHandler(Request $request, array $handler)
    {
        list($namespace, $prefix) = $this->parser($handler[0]);
        $this->app['upload.namespace'] = $namespace;
        $this->app['upload.prefix'] = $prefix;

        // Do the upload
        $result = $this->handleUploadFiles($request, $namespace);

        array_shift($handler);
        $original = $namespace;

        if (count($result)) {
            $result = $result[0];
            foreach ($handler as $copy) {
                list($namespace, $prefix) = $this->parser($copy);

                $manager = $this->app['filesystem'];
                $manager->put(
                    $namespace . '://' . $prefix . basename($result['name']),
                    $manager->read($original . '://' . $result['name'])
                );
            }
        }

        return $this->json($result, Response::HTTP_OK, array('Content-Type' => 'text/plain'));
    }
}
