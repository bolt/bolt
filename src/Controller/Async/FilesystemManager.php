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
use Webmozart\PathUtil\Path;

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
        } catch (IOException $e) {
            $msg = Trans::__('page.file-management.message.folder-not-found', ['%s' => $path]);
            $this->logException($msg, $e);
            $this->flashes()->error($msg);
        }

        $files = $filesystem->find()->in($path)->files()->depth(0)->toArray();
        $directories = $filesystem->find()->in($path)->directories()->depth(0)->toArray();

        $context = [
            'namespace'    => $namespace,
            'files'        => $files,
            'directories'  => $directories,
            'pathsegments' => $pathsegments,
            'multiselect'  => $request->query->get('multiselect') === 'true',
        ];

        return $this->render(
            '@bolt/async/browse.twig',
            ['context' => $context],
            ['title', Trans::__('page.file-management.message.files-in', ['%s' => $path])]
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

            return $this->json("$parentPath$folderName", Response::HTTP_OK);
        } catch (IOException $e) {
            $msg = Trans::__('Unable to create directory: %DIR%', ['%DIR%' => $folderName]);
            $this->logException($msg, $e);

            return $this->json($msg, Response::HTTP_INTERNAL_SERVER_ERROR);
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

            return $this->json("$parentPath/$filename", Response::HTTP_OK);
        } catch (IOException $e) {
            $msg = Trans::__('Unable to create file: %FILE%', ['%FILE%' => $filename]);
            $this->logException($msg, $e);

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

            return $this->json($filename, Response::HTTP_OK);
        } catch (ExceptionInterface $e) {
            $msg = Trans::__('Unable to delete file: %FILE%', ['%FILE%' => $filename]);
            $this->logException($msg, $e);

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
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function duplicateFile(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $filename = $request->request->get('filename');

        $filesystem = $this->filesystem()->getFilesystem($namespace);

        // If the filename doesn't have an extension $extensionPos will be equal to its length, so that$fileBase will
        // contain the entire filename. This also accounts for dotfiles.
        $extensionPos = strrpos($filename, '.') ?: strlen($filename);

        $fileBase = substr($filename, 0, $extensionPos) . '_copy';
        $fileExtension = substr($filename, $extensionPos);

        $n = 1;

        // Increase $n until filename_copy$n.ext doesn't exist
        while ($filesystem->has($fileBase . $n . $fileExtension)) {
            $n++;
        }

        $destination = $fileBase . $n . $fileExtension;

        try {
            $filesystem->copy($filename, $destination);

            return $this->json($destination, Response::HTTP_OK);
        } catch (IOException $e) {
            $msg = Trans::__('Unable to duplicate file: %FILE%', ['%FILE%' => $filename]);

            $this->logException($msg, $e);

            return $this->json($msg, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
        $term = $request->query->get('term', '.*');
        $dir = Path::getDirectory($term);
        $term = Path::getFilename($term);
        $term = preg_quote($term);

        $extensions = implode('|', explode(',', $request->query->get('ext', '.*')));
        $regex = sprintf('/.*(%s).*\.(%s)$/', $term, $extensions);

        $files = $this->filesystem()
            ->find()
            ->in('files://' . $dir)
            ->name($regex)
        ;

        $result = [];
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

        foreach ($this->app['config']->get('contenttypes') as $contenttype) {
            if ($contenttype['viewless']) {
                // Skip viewless ContentTypes
                continue;
            }

            $slug = $contenttype['slug'];
            $records = $this->getContent($slug, ['published' => true, 'hydrate' => false]);
            foreach ($records as $record) {
                $results[$slug][] = [
                    'title' => $record->getTitle(),
                    'id'    => $record->id,
                    'link'  => $record->link(),
                ];
            }
        }

        $context = ['results' => $results];

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
        $folderName = $request->request->get('foldername');

        try {
            $this->filesystem()->deleteDir(sprintf('%s://%s', $namespace, $folderName));

            return $this->json($folderName, Response::HTTP_OK);
        } catch (ExceptionInterface $e) {
            $msg = Trans::__('Unable to delete directory: %DIR%', ['%DIR%' => $folderName]);

            $this->logException($msg, $e);

            return $this->json($msg, Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $namespace = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $oldName = $request->request->get('oldname');
        $newName = $request->request->get('newname');

        if (!$this->isMatchingExtension($oldName, $newName)) {
            return $this->json(Trans::__('general.phrase.only-root-change-file-extensions'), Response::HTTP_FORBIDDEN);
        }

        try {
            $this->filesystem()->rename(sprintf('%s://%s/%s', $namespace, $parentPath, $oldName), sprintf('%s/%s', $parentPath, $newName));

            return $this->json(sprintf('%s/%s', $parentPath, $newName), Response::HTTP_OK);
        } catch (ExceptionInterface $e) {
            $msg = Trans::__('Unable to rename file: %FILE%', ['%FILE%' => $oldName]);
            $this->logException($msg, $e);

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
     * Rename a folder within the files directory tree.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function renameFolder(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $oldName = $request->request->get('oldname');
        $newName = $request->request->get('newname');

        try {
            $this->filesystem()->rename(sprintf('%s://%s', $namespace, $oldName), $newName);

            return $this->json($newName, Response::HTTP_OK);
        } catch (ExceptionInterface $e) {
            $msg = Trans::__('Unable to rename directory: %DIR%', ['%DIR%' => $oldName]);
            $this->logException($msg, $e);

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

        return $oldFile->getExtension() === $newFile->getExtension();
    }

    /**
     * Log an exception to the system log
     *
     * @param string     $message   A formatted error message
     * @param \Exception $exception The exception that has been thrown
     *
     * @return Boolean Whether the record has been processed
     */
    private function logException($message, $exception)
    {
        return $this->app['logger.system']->error(
            $message . ': ' . $exception->getMessage(),
            ['event' => 'exception', 'exception' => $exception]
        );
    }
}
