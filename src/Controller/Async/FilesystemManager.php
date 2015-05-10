<?php
namespace Bolt\Controller\Async;

use Bolt\Translation\Translator as Trans;
use League\Flysystem\FileNotFoundException;
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
        $ctr->get('/browse/{namespace}/{path}', 'actionBrowse')
            ->assert('path', '.*')
            ->value('namespace', 'files')
            ->value('path', '')
            ->bind('asyncbrowse');

        $ctr->post('/folder/create', 'actionCreateFolder')
            ->bind('createfolder');

        $ctr->get('/filesautocomplete', 'actionFilesAutoComplete')
            ->bind('filesautocomplete');

        $ctr->post('/deletefile', 'actionDeleteFile')
            ->bind('deletefile');

        $ctr->post('/duplicatefile', 'actionDuplicateFile')
            ->bind('duplicatefile');

        $ctr->get('/recordbrowser', 'actionRecordBrowser')
            ->bind('recordbrowser');

        $ctr->post('/renamefile', 'actionRenameFile')
            ->bind('renamefile');

        $ctr->post('/folder/rename', 'actionRenameFolder')
            ->bind('renamefolder');

        $ctr->post('/folder/remove', 'actionRemoveFolder')
            ->bind('removefolder');
    }

    /**
     * List browse on the server, so we can insert them in the file input.
     *
     * @param Request $request
     * @param string  $namespace
     * @param string  $path
     *
     * @return mixed
     */
    public function actionBrowse(Request $request, $namespace, $path)
    {
        // No trailing slashes in the path.
        $path = rtrim($path, '/');

        $filesystem = $this->getFilesystemManager()->getFilesystem($namespace);

        // $key is linked to the fieldname of the original field, so we can
        // Set the selected value in the proper field
        $key = $request->query->get('key');

        // Get the pathsegments, so we can show the path.
        $pathsegments = array();
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
            $msg = Trans::__("Folder '%s' could not be found, or is not readable.", array('%s' => $path));
            $this->addFlash('error', $msg);
        }

        list($files, $folders) = $filesystem->browse($path, $this->app);

        $context = array(
            'namespace'    => $namespace,
            'files'        => $files,
            'folders'      => $folders,
            'pathsegments' => $pathsegments,
            'key'          => $key
        );

        return $this->render('files_async/files_async.twig',
            array('context' => $context),
            array('title', Trans::__('Files in %s', array('%s' => $path)))
        );
    }

    /**
     * Create a new folder.
     *
     * @param Request $request
     *
     * @return Boolean Whether the creation was successful
     */
    public function actionCreateFolder(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $folderName = $request->request->get('foldername');

        try {
            return $this->getFilesystemManager()->createDir("$namespace://$parentPath$folderName");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete a file on the server.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function actionDeleteFile(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $filename = $request->request->get('filename');

        try {
            return $this->getFilesystemManager()->delete("$namespace://$filename");
        } catch (FileNotFoundException $e) {
            return false;
        }
    }

    /**
     * Duplicate a file on the server.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function actionDuplicateFile(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $filename = $request->request->get('filename');

        $filesystem = $this->getFilesystemManager()->getFilesystem($namespace);

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
    public function actionFilesAutoComplete(Request $request)
    {
        $term = $request->get('term');
        $extensions = $request->query->get('ext');

        $files = $this->getFilesystemManager()->search($term, $extensions);

        $this->app['debug'] = false;

        return $this->json($files);
    }

    /**
     * List records to easily insert links through the WYSIWYG editor.
     *
     * @return mixed
     */
    public function actionRecordBrowser()
    {
        $results = array();

        foreach ($this->app['storage']->getContentTypes() as $contenttype) {
            $records = $this->getContent($contenttype, array('published' => true, 'hydrate' => false));

            foreach ($records as $record) {
                $results[$contenttype][] = array(
                    'title' => $record->gettitle(),
                    'id'    => $record->id,
                    'link'  => $record->link()
                );
            }
        }

        $context = array(
            'results' => $results,
        );

        return $this->render('recordbrowser/recordbrowser.twig', array('context' => $context));
    }

    /**
     * Delete a folder recursively if writeable.
     *
     * @param Request $request
     *
     * @return Boolean Whether the renaming action was successful
     */
    public function actionRemoveFolder(Request $request)
    {
        $namespace = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $folderName = $request->request->get('foldername');

        try {
            return $this->getFilesystemManager()->deleteDir("$namespace://$parentPath$folderName");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Rename a file within the files directory tree.
     *
     * @param Request $request
     *
     * @return Boolean Whether the renaming action was successful
     */
    public function actionRenameFile(Request $request)
    {
        $namespace  = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $oldName    = $request->request->get('oldname');
        $newName    = $request->request->get('newname');

        try {
            return $this->getFilesystemManager()->rename("$namespace://$parentPath/$oldName", "$parentPath/$newName");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Rename a folder within the files directory tree.
     *
     * @param Request $request
     *
     * @return Boolean Whether the renaming action was successful
     */
    public function actionRenameFolder(Request $request)
    {
        $namespace  = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $oldName    = $request->request->get('oldname');
        $newName    = $request->request->get('newname');

        try {
            return $this->getFilesystemManager()->rename("$namespace://$parentPath$oldName", "$parentPath$newName");
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Middleware function to do some tasks that should be done for all
     * asynchronous requests.
     */
    public function before(Request $request)
    {
        // Start the 'stopwatch' for the profiler.
        $this->app['stopwatch']->start('bolt.async.before');

        // If there's no active session, don't do anything.
        if (!$this->getAuthentication()->isValidSession()) {
            $this->abort(Response::HTTP_UNAUTHORIZED, 'You must be logged in to use this.');
        }

        // Stop the 'stopwatch' for the profiler.
        $this->app['stopwatch']->stop('bolt.async.before');
    }
}
