<?php

namespace Bolt\Controller\Async;

use Bolt\Filesystem\Exception\ExceptionInterface;
use Bolt\Filesystem\Exception\FileExistsException;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Listing;
use Bolt\Helpers\Str;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
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
     * @return \Bolt\Response\TemplateResponse
     */
    public function browse(Request $request, $namespace, $path)
    {
        $directory = $this->filesystem()->getDir("$namespace://$path");
        $listing = new Listing($directory);
        $showHidden = $this->isAllowed('files:hidden');

        try {
            $directories = $listing->getDirectories($showHidden);
            $files = $listing->getFiles($showHidden);
        } catch (IOException $e) {
            $this->logException(Trans::__('page.file-management.message.folder-not-found', ['%s' => $path]), $e);
            $directories = [];
            $files = [];
        }

        $context = [
            'directory'   => $directory,
            'directories' => $directories,
            'files'       => $files,
            'multiselect' => $request->query->getBoolean('multiselect'),
        ];

        return $this->render('@bolt/async/browse.twig', ['context' => $context]);
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
        // Verify CSRF token
        $this->checkToken($request);

        $namespace = $request->request->get('namespace');
        $parentPath = Str::makeSafe($request->request->get('parent'), false, '()[]!@$^-_=+{},.~');
        $folderName = Str::makeSafe($request->request->get('foldername'), false, '()[]!@$^-_=+{},.~');

        try {
            $dir = $this->filesystem()->getDir("$namespace://$parentPath/$folderName");
            $dir->create();

            return $this->json($dir->getPath(), Response::HTTP_OK);
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
        // Verify CSRF token
        $this->checkToken($request);

        $namespace = $request->request->get('namespace');
        $parentPath = Str::makeSafe($request->request->get('parentPath'), false, '()[]!@$^-_=+{},.~');
        $filename = Str::makeSafe($request->request->get('filename'), false, '()[]!@$^-_=+{},.~');

        if ($this->validateFileExtension($filename) === false) {
            return $this->json(
                sprintf("File extension not allowed: %s", $filename),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $file = $this->filesystem()->getFile("$namespace://$parentPath/$filename");
            $file->put('');

            return $this->json($file->getPath(), Response::HTTP_OK);
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
        // Verify CSRF token
        $this->checkToken($request);

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
        // Verify CSRF token
        $this->checkToken($request);

        $namespace = $request->request->get('namespace');
        $filename = $request->request->get('filename');

        $filesystem = $this->filesystem()->getFilesystem($namespace);

        // If the filename doesn't have an extension $extensionPos will be equal to its length, so that $fileBase will
        // contain the entire filename. This also accounts for dotfiles.
        $extensionPos = strrpos($filename, '.') ?: strlen($filename);

        $fileBase = substr($filename, 0, $extensionPos) . '_copy';
        $fileExtension = substr($filename, $extensionPos);

        $n = 0;

        // Increase $n until filename_copy$n.ext doesn't exist
        do {
            ++$n;
            $destination = $fileBase . $n . $fileExtension;
        } while ($filesystem->has($destination));

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
            $result[] = $file->toJs();
        }

        return $this->json($result);
    }

    /**
     * List records to easily insert links through the WYSIWYG editor.
     *
     * @return \Bolt\Response\TemplateResponse
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
        // Verify CSRF token
        $this->checkToken($request);

        $namespace = $request->request->get('namespace');
        $parent = $request->request->get('parent');
        $folderName = $request->request->get('foldername');

        try {
            $this->filesystem()->deleteDir("$namespace://$parent/$folderName");

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
        // Verify CSRF token
        $this->checkToken($request);

        $namespace = $request->request->get('namespace');
        $parent = $request->request->get('parent');
        $oldName = $request->request->get('oldname');
        $newName = $request->request->get('newname');

        if (!$this->isExtensionChangedAndIsChangeAllowed($oldName, $newName)) {
            return $this->json(Trans::__('general.phrase.only-root-change-file-extensions'), Response::HTTP_FORBIDDEN);
        }

        if ($this->validateFileExtension($newName) === false) {
            return $this->json(
                sprintf("File extension not allowed: %s", $newName),
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->filesystem()->rename("$namespace://$parent/$oldName", "$parent/$newName");

            return $this->json($newName, Response::HTTP_OK);
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
        // Verify CSRF token
        $this->checkToken($request);

        $namespace = $request->request->get('namespace');
        $parent = $request->request->get('parent');
        $oldName = $request->request->get('oldname');
        $newName = $request->request->get('newname');

        try {
            $dir = $this->filesystem()->getDir("$namespace://$parent/$oldName");
            if (!$dir) {
                return $this->json(
                    sprintf("Only directories are allowed to be renamed with this method"),
                    Response::HTTP_BAD_REQUEST
                );
            }
            $this->filesystem()->rename("$namespace://$parent/$oldName", "$parent/$newName");

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
     * @return bool
     */
    private function isExtensionChangedAndIsChangeAllowed($oldName, $newName)
    {
        $user = $this->getUser();
        if ($this->users()->hasRole($user['id'], 'root') || $this->users()->hasRole($user['id'], 'admin')) {
            return true;
        }

        $oldFile = new \SplFileInfo($oldName);
        $newFile = new \SplFileInfo($newName);

        return $oldFile->getExtension() === $newFile->getExtension();
    }

    /**
     * Log an exception to the system log.
     *
     * @param string     $message   A formatted error message
     * @param \Exception $exception The exception that has been thrown
     *
     * @return bool Whether the record has been processed
     */
    private function logException($message, $exception)
    {
        return $this->app['logger.system']->error(
            $message . ': ' . $exception->getMessage(),
            ['event' => 'exception', 'exception' => $exception]
        );
    }

    /**
     * @param string $filename
     *
     * @return bool
     */
    private function validateFileExtension($filename)
    {
        // no UNIX-hidden files
        if ($filename[0] === '.') {
            return false;
        }
        // only whitelisted extensions
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $allowedExtensions = $this->getAllowedUploadExtensions();

        return $extension === '' || in_array(mb_strtolower($extension), $allowedExtensions);
    }

    /**
     * Get the array of configured acceptable file extensions.
     *
     * @return array
     */
    private function getAllowedUploadExtensions()
    {
        return $this->app['config']->get('general/accept_file_types');
    }

    /**
     * Check if the passed in token was valid
     *
     * @param Request $request
     */
    private function checkToken(Request $request)
    {
        $token = new CsrfToken('bolt', $request->request->get('token'));

        if (! $this->app['csrf']->isTokenValid($token)) {
            $msg = 'Token not valid';
            $this->abort(Response::HTTP_UNAUTHORIZED, $msg);
        }
    }
}
