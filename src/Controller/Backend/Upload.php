<?php

namespace Bolt\Controller\Backend;

use Silex\Application;
use Silex\ControllerCollection;
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
        $c->match('/{namespace}', 'uploadNamespace')
            ->before([$this, 'before'])
            ->value('namespace', 'files')
            ->bind('upload')
        ;

        return $c;
    }

    /**
     * @param Request     $request
     * @param Application $app
     * @param null        $roleRoute
     *
     * @return null|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function before(Request $request, Application $app, $roleRoute = null)
    {
        return parent::before($request, $app, 'files:uploads');
    }

    /**
     * Route to handle file uploads.
     *
     * @param Request $request
     * @param string  $namespace
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function uploadNamespace(Request $request, $namespace)
    {
        if ($handler = $request->get('handler')) {
            // Use custom handlers
            if (is_array($handler)) {
                return $this->processCustomUploadHandler($request, $handler);
            }
            list($namespace, $prefix) = $this->parser($handler);
            $this->app['upload.namespace'] = $namespace;
            $this->app['upload.prefix'] = $prefix;
        } else {
            $namespace = $this->app['upload.namespace'];
        }

        // Perform the file upload actions and collect the results
        $fileUpload = $this->handleUploadFiles($request, $namespace);

        return $this->json($fileUpload, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
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

        return [$namespace, $prefix];
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

        /** @var \Sirius\Upload\Result\Collection $result */
        $result = $this->app['upload']->process($filesToProcess);

        if ($result->isValid()) {
            if (!$this->app['config']->get('general/upload/autoconfirm')) {
                // Remove the .lock file attached to the file
                $result->confirm();
            }

            $successfulFiles = [];
            foreach ($result as $resultFile) {
                $successfulFiles[] = $this->filesystem()->toJs($namespace . '://' . $resultFile->name);
            }

            return $successfulFiles;
        }

        // The file that was saved during process() has a .lock file attached
        // and can now be cleared, in the case where form processing fails
        try {
            $result->clear();
        } catch (\Exception $e) {
            // It's an error state anyway
        }

        $errorFiles = [];
        foreach ($result as $resultFile) {
            $errors = $resultFile->getMessages();
            $errorFiles[] = [
                    'name'  => $resultFile->original_name,
                    'error' => (string) $errors[0],
                ];
        }

        return $errorFiles;
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
            return [];
        }
        $filesToProcess = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $filesToProcess[] = [
                    'name'     => $file->getClientOriginalName(),
                    'tmp_name' => $file->getPathname(),
                ];
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
    private function processCustomUploadHandler(Request $request, array $handler)
    {
        list($namespace, $prefix) = $this->parser($handler[0]);
        $this->app['upload.namespace'] = $namespace;
        $this->app['upload.prefix'] = $prefix;

        // Do the upload
        $fullResult = $this->handleUploadFiles($request, $namespace);

        array_shift($handler);
        $original = $namespace;

        if (count($fullResult)) {
            $result = $fullResult[0];
            foreach ($handler as $copy) {
                list($namespace, $prefix) = $this->parser($copy);

                $this->filesystem()->put(
                    $namespace . '://' . $prefix . basename($result['name']),
                    $this->filesystem()->read($original . '://' . $result['name'])
                );
            }
        }

        return $this->json($fullResult, Response::HTTP_OK, ['Content-Type' => 'text/plain']);
    }
}
