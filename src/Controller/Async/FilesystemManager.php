<?php
namespace Bolt\Controller\Async;

use Bolt\Translation\Translator as Trans;
use Guzzle\Http\Exception\RequestException as V3RequestException;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\FileNotFoundException;
use Silex;
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
    protected function addRoutes(Silex\ControllerCollection $ctr)
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

        $ctr->get('/filebrowser/{contenttype}', 'actionFileBrowser')
            ->assert('contenttype', '.*')
            ->bind('filebrowser');

        $ctr->post('/deletefile', 'actionDeleteFile')
            ->bind('deletefile');

        $ctr->post('/duplicatefile', 'actionDuplicateFile')
            ->bind('duplicatefile');

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
     * List pages in given contenttype, to easily insert links through the
     * WYSIWYG editor.
     *
     * @param string $contenttype
     *
     * @return mixed
     */
    public function actionFileBrowser($contenttype)
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

        return $this->render('filebrowser/filebrowser.twig', array('context' => $context));
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

    /**
     * Get the news from either cache or Bolt HQ.
     *
     * @param string $hostname
     *
     * @return array|string
     */
    private function getNews($hostname)
    {
        // Cached for two hours.
        $news = $this->app['cache']->fetch('dashboardnews');

        // If not cached, get fresh news.
        if ($news !== false) {
            $this->app['logger.system']->info('Using cached data', array('event' => 'news'));

            return $news;
        } else {
            $source = 'http://news.bolt.cm/';
            $curl = $this->getDashboardCurlOptions($hostname, $source);

            $this->app['logger.system']->info('Fetching from remote server: ' . $source, array('event' => 'news'));

            try {
                if ($this->app['deprecated.php']) {
                    $fetchedNewsData = $this->app['guzzle.client']->get($curl['url'], null, $curl['options'])->send()->getBody(true);
                } else {
                    $fetchedNewsData = $this->app['guzzle.client']->get($curl['url'], array(), $curl['options'])->getBody(true);
                }

                $fetchedNewsItems = json_decode($fetchedNewsData);

                if ($fetchedNewsItems) {
                    $news = array();

                    // Iterate over the items, pick the first news-item that
                    // applies and the first alert we need to show
                    $version = $this->app->getVersion();
                    foreach ($fetchedNewsItems as $item) {
                        $type = ($item->type === 'alert') ? 'alert' : 'information';
                        if (!isset($news[$type])
                            && (empty($item->target_version) || version_compare($item->target_version, $version, '>'))
                        ) {
                            $news[$type] = $item;
                        }
                    }

                    $this->app['cache']->save('dashboardnews', $news, 7200);
                } else {
                    $this->app['logger.system']->error('Invalid JSON feed returned', array('event' => 'news'));
                }

                return $news;
            } catch (RequestException $e) {
                $this->app['logger.system']->critical(
                    'Error occurred during newsfeed fetch',
                    array('event' => 'exception', 'exception' => $e)
                );

                return array('error' => array('type' => 'error', 'title' => 'Unable to fetch news!', 'teaser' => "<p>Unable to connect to $source</p>"));
            } catch (V3RequestException $e) {
                /** @deprecated remove with the end of PHP 5.3 support */
                $this->app['logger.system']->critical(
                    'Error occurred during newsfeed fetch',
                    array('event' => 'exception', 'exception' => $e)
                );

                return array('error' => array('type' => 'error', 'title' => 'Unable to fetch news!', 'teaser' => "<p>Unable to connect to $source</p>"));
            }
        }
    }

    /**
     * Get the cURL options.
     *
     * @param string $hostname
     * @param string $source
     *
     * @return array
     */
    private function getDashboardCurlOptions($hostname, $source)
    {
        $driver = $this->app['db']->getDatabasePlatform()->getName();

        $url = sprintf(
            '%s?v=%s&p=%s&db=%s&name=%s',
            $source,
            rawurlencode($this->app->getVersion()),
            phpversion(),
            $driver,
            base64_encode($hostname)
        );

        // Standard option(s)
        $options = array('CURLOPT_CONNECTTIMEOUT' => 5);

        // Options valid if using a proxy
        if ($this->getOption('general/httpProxy')) {
            $proxies = array(
                'CURLOPT_PROXY'        => $this->getOption('general/httpProxy/host'),
                'CURLOPT_PROXYTYPE'    => 'CURLPROXY_HTTP',
                'CURLOPT_PROXYUSERPWD' => $this->getOption('general/httpProxy/user') . ':' .
                $this->getOption('general/httpProxy/password')
            );
        }

        return array(
            'url'     => $url,
            'options' => $proxies ? array_merge($options, $proxies) : $options
        );
    }

    /**
     * Get last modified records from the content log.
     *
     * @param string  $contenttypeslug
     * @param integer $contentid
     *
     * @return BoltResponse
     */
    private function getLastmodifiedByContentLog($contenttypeslug, $contentid)
    {
        // Get the proper contenttype.
        $contenttype = $this->getContentType($contenttypeslug);

        // get the changelog for the requested contenttype.
        $options = array('limit' => 5, 'order' => 'date', 'direction' => 'DESC');

        if (intval($contentid) == 0) {
            $isFiltered = false;
        } else {
            $isFiltered = true;
            $options['contentid'] = intval($contentid);
        }

        $changelog = $this->app['logger.manager.change']->getChangelogByContentType($contenttype['slug'], $options);

        $context = array(
            'changelog'   => $changelog,
            'contenttype' => $contenttype,
            'contentid'   => $contentid,
            'filtered'    => $isFiltered,
        );

        $response = $this->render('components/panel-lastmodified.twig', array('context' => $context));
        $response->setPublic()->setSharedMaxAge(60);

        return $response;
    }

    /**
     * Only get latest {contenttype} record edits based on date changed.
     *
     * @param string $contenttypeslug
     *
     * @return BoltResponse
     */
    private function getLastmodifiedSimple($contenttypeslug)
    {
        // Get the proper contenttype.
        $contenttype = $this->getContentType($contenttypeslug);

        // Get the 'latest' from the requested contenttype.
        $latest = $this->getContent($contenttype['slug'], array('limit' => 5, 'order' => 'datechanged DESC', 'hydrate' => false));

        $context = array(
            'latest'      => $latest,
            'contenttype' => $contenttype
        );

        $response = $this->render('components/panel-lastmodified.twig', array('context' => $context));
        $response->setPublic()->setSharedMaxAge(60);

        return $response;
    }
}
