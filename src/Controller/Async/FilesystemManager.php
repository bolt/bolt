<?php
namespace Bolt\Controller\Async;

use Bolt\Filesystem\Exception\ExceptionInterface;
use Bolt\Filesystem\Exception\FileExistsException;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Async controller for filesystem management async routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class FilesystemManager extends AsyncBase
{
    protected function addRoutes(ControllerCollection $ctr)
    {
        $ctr->get('/browse/{namespace}/{path}', 'browse')
            ->assert('path', '.*')
            ->value('namespace', 'files')
            ->value('path', '')
            ->bind('asyncbrowse');

        $ctr->get('/file/autocomplete', 'filesAutoComplete')
            ->bind('file/autocomplete');

        $ctr->post('/file/create', 'createFile')
            ->bind('file/create');

        $ctr->post('/file/delete', 'deleteFile')
            ->bind('file/delete');

        $ctr->post('/file/duplicate', 'duplicateFile')
            ->bind('file/duplicate');

        $ctr->post('/file/rename', 'renameFile')
            ->bind('file/rename');

        $ctr->post('/folder/create', 'createFolder')
            ->bind('createfolder');

        $ctr->post('/folder/rename', 'renameFolder')
            ->bind('renamefolder');

        $ctr->post('/folder/remove', 'removeFolder')
            ->bind('removefolder');

        $ctr->get('/recordbrowser', 'recordBrowser')
            ->bind('recordbrowser');
    }

    /**
     * List browse on the server, so we can insert them in the file input.
     *
     * @param Request $request
     * @param string  $namespace
     * @param string  $path
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function browse(Request $request, $namespace, $path)
    {
        // No trailing slashes in the path.
        $path = rtrim($path, '/');

        $filesystem = $this->filesystem()->getFilesystem($namespace);

        // Get the pathsegments, so we can show the path.
        $pathsegments = [];
        $cumulative = '';
        if (!empty($path)) {
            foreach (explode('/', $path) as $segment) {
                $cumulative .= $segment . '/';
                $pathsegments[$cumulative] = $segment;
            }
        }

        try {
            $filesystem->listContents($path);
        } catch (\Exception $e) {
            $msg = Trans::__("Folder '%s' could not be found, or is not readable.", ['%s' => $path]);
            $this->flashes()->error($msg);
        }

        $files = $filesystem->find()->in($path)->files()->depth(0)->toArray();
        $directories = $filesystem->find()->in($path)->directories()->depth(0)->toArray();

        $context = [
            'namespace'    => $namespace,
            'files'        => $files,
            'directories'  => $directories,
            'pathsegments' => $pathsegments,
        ];

        return $this->render(
            '@bolt/async/browse.twig',
            ['context' => $context],
            ['title', Trans::__('Files in %s', ['%s' => $path])]
        );
    }

    /**
     * Create a new folder.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createFolder(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $folderName = $request->request->get('foldername');

        try {
            $this->filesystem()->createDir("$namespace://$parentPath$folderName");

            return $this->json(null, Response::HTTP_OK);
        } catch (IOException $e) {
            return $this->json(Trans::__('Unable to create directory: %DIR%', ['%DIR%' => $folderName]), Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create an empty file.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function createFile(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $parentPath = $request->request->get('parentPath');
        $filename = $request->request->get('filename');

        try {
            $this->filesystem()->put("$namespace://$parentPath/$filename", ' ');

            return $this->json(null, Response::HTTP_OK);
        } catch (IOException $e) {
            $msg = Trans::__('Unable to create file: %FILE%', ['%FILE%' => $filename]);

            $this->app['logger.system']->critical(
                $msg . ': ' . $e->getMessage(),
                ['event' => 'exception', 'exception' => $e]
            );

            return $this->json($msg, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a file on the server.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function deleteFile(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $filename = $request->request->get('filename');

        try {
            $this->filesystem()->delete("$namespace://$filename");

            return $this->json(null, Response::HTTP_OK);
        } catch (ExceptionInterface $e) {
            $msg = Trans::__('Unable to delete file: %FILE%', ['%FILE%' => $filename]);

            $this->app['logger.system']->critical(
                $msg . ': ' . $e->getMessage(),
                ['event' => 'exception', 'exception' => $e]
            );

            return $this->json(
                $msg,
                $e instanceof FileNotFoundException ? Response::HTTP_NOT_FOUND : Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Duplicate a file on the server.
     *
     * @param Request $request
     *
     * @return boolean
     */
    public function duplicateFile(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $filename = $request->request->get('filename');

        $filesystem = $this->filesystem()->getFilesystem($namespace);

        $extensionPos = strrpos($filename, '.');
        $destination = substr($filename, 0, $extensionPos) . '_copy' . substr($filename, $extensionPos);
        $n = 1;

        while ($filesystem->has($destination)) {
            $extensionPos = strrpos($destination, '.');
            $destination = substr($destination, 0, $extensionPos) . "$n" . substr($destination, $extensionPos);
            $n = rand(0, 1000);
        }
        if ($filesystem->copy($filename, $destination)) {
            return true;
        }

        return false;
    }

    /**
     * Return autocomplete data for a file name.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function filesAutoComplete(Request $request)
    {
        $term = $request->get('term', '.*');
        $extensions = implode('|', explode(',', $request->query->get('ext', '.*')));
        $regex = sprintf('/.*(%s).*\.(%s)$/', $term, $extensions);

        $files = $this->filesystem()
            ->find()
            ->in('files://')
            ->name($regex)
        ;

        /** @var \Bolt\Filesystem\Handler\File $file */
        foreach ($files as $file) {
            $result[] = $file->getPath();
        }

        return $this->json($result);
    }

    /**
     * List records to easily insert links through the WYSIWYG editor.
     *
     * @return \Bolt\Response\BoltResponse
     */
    public function recordBrowser()
    {
        $results = [];

        foreach ($this->storage()->getContentTypes() as $contenttype) {
            if ($this->app['config']->get("contenttypes/{$contenttype}/viewless")) {
                // Skip viewless ContentTypes
                continue;
            }
            $records = $this->getContent($contenttype, ['published' => true, 'hydrate' => false]);

            foreach ($records as $record) {
                $results[$contenttype][] = [
                    'title' => $record->getTitle(),
                    'id'    => $record->id,
                    'link'  => $record->link(),
                ];
            }
        }

        $context = [
            'results' => $results,
        ];

        return $this->render('@bolt/recordbrowser/recordbrowser.twig', ['context' => $context]);
    }

    /**
     * Delete a folder recursively if writeable.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function removeFolder(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $folderName = $request->request->get('foldername');

        try {
            $this->filesystem()->deleteDir("$namespace://$parentPath$folderName");

            return $this->json(null, Response::HTTP_OK);
        } catch (IOException $e) {
            return $this->json(Trans::__('Unable to delete directory: %DIR%', ['%DIR%' => $folderName]), Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Rename a file within the files directory tree.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function renameFile(Request $request)
    {
        $namespace  = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $oldName    = $request->request->get('oldname');
        $newName    = $request->request->get('newname');

        if (!$this->isMatchingExtension($oldName, $newName)) {
            return $this->json(Trans::__('Only root can change file extensions.'), Response::HTTP_FORBIDDEN);
        }

        try {
            if ($this->filesystem()->rename("$namespace://$parentPath/$oldName", "$parentPath/$newName")) {
                return $this->json(null, Response::HTTP_OK);
            }

            return $this->json(Trans::__('Unable to rename file: %FILE%', ['%FILE%' => $oldName]), Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return $this->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Rename a folder within the files directory tree.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function renameFolder(Request $request)
    {
        $namespace  = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $oldName    = $request->request->get('oldname');
        $newName    = $request->request->get('newname');

        try {
            $this->filesystem()->rename("$namespace://$parentPath$oldName", "$parentPath$newName");

            return $this->json(null, Response::HTTP_OK);
        } catch (ExceptionInterface $e) {
            $msg = Trans::__('Unable to rename directory: %DIR%', ['%DIR%' => $oldName]);

            $this->app['logger.system']->critical(
                $msg . ': ' . $e->getMessage(),
                ['event' => 'status', 'status' => $e]
            );

            if ($e instanceof FileExistsException) {
                $status = Response::HTTP_CONFLICT;
            } elseif ($e instanceof FileNotFoundException) {
                $status = Response::HTTP_NOT_FOUND;
            } else {
                $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            }

            return $this->json($msg, $status);
        }
    }

    /**
     * Check that file extensions are not being changed.
     *
     * @param string $oldName
     * @param string $newName
     *
     * @return boolean
     */
    private function isMatchingExtension($oldName, $newName)
    {
        $user = $this->getUser();
        if ($this->users()->hasRole($user['id'], 'root')) {
            return true;
        }

        $oldFile = new \SplFileInfo($oldName);
        $newFile = new \SplFileInfo($newName);
        if ($oldFile->getExtension() === $newFile->getExtension()) {
            return true;
        }

        return false;
    }
}
