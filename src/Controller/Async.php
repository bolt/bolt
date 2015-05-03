<?php
namespace Bolt\Controller;

use Bolt\Translation\Translator as Trans;
use Guzzle\Http\Exception\RequestException as V3RequestException;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\FileNotFoundException;
use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Async extends Base
{
    protected function addRoutes(Silex\ControllerCollection $ctr)
    {
        $ctr->before(array($this, 'before'));

        $ctr->get('/dashboardnews', 'actionDashboardNews')
            ->bind('dashboardnews');

        $ctr->get('/latestactivity', 'actionLatestActivity')
            ->bind('latestactivity');

        $ctr->get('/filesautocomplete', 'actionFilesAutoComplete')
            ->bind('filesautocomplete');

        $ctr->get('/readme/{filename}', 'actionReadme')
            ->assert('filename', '.+')
            ->bind('readme');

        $ctr->get('/widget/{key}', 'actionWidget')
            ->bind('widget');

        $ctr->get('/makeuri', 'actionMakeUri')
            ->bind('makeuri');

        $ctr->get('/lastmodified/{contenttypeslug}/{contentid}', 'actionLastModified')
            ->value('contentid', '')
            ->bind('lastmodified');

        $ctr->get('/filebrowser/{contenttype}', 'actionFileBrowser')
            ->assert('contenttype', '.*')
            ->bind('filebrowser');

        $ctr->get('/browse/{namespace}/{path}', 'actionBrowse')
            ->assert('path', '.*')
            ->value('namespace', 'files')
            ->value('path', '')
            ->bind('asyncbrowse');

        $ctr->post('/renamefile', 'actionRenameFile')
            ->bind('renamefile');

        $ctr->post('/deletefile', 'actionDeleteFile')
            ->bind('deletefile');

        $ctr->post('/duplicatefile', 'actionDuplicateFile')
            ->bind('duplicatefile');

        $ctr->get('/addstack/{filename}', 'actionAddStack')
            ->assert('filename', '.*')
            ->bind('addstack');

        $ctr->get('/tags/{taxonomytype}', 'actionTags')
            ->bind('tags');

        $ctr->get('/populartags/{taxonomytype}', 'actionPopularTags')
            ->bind('populartags');

        $ctr->get('/showstack', 'actionShowStack')
            ->bind('showstack');

        $ctr->get('/omnisearch', 'actionOmnisearch');

        $ctr->post('/folder/rename', 'actionRenameFolder')
            ->bind('renamefolder');

        $ctr->post('/folder/remove', 'actionRemoveFolder')
            ->bind('removefolder');

        $ctr->post('/folder/create', 'actionCreateFolder')
            ->bind('createfolder');

        $ctr->get('/changelog/{contenttype}/{contentid}', 'actionChangeLogRecord')
            ->value('contenttype', '')
            ->value('contentid', '0')
            ->bind('changelogrecord');

        $ctr->get('/email/{type}/{recipient}', 'actionEmailNotification')
            ->assert('type', '.*')
            ->bind('emailNotification');
    }

    /**
     * News. Film at 11.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function actionDashboardNews(Request $request)
    {
        $news = $this->getNews($request->getHost());

        // One 'alert' and one 'info' max. Regular info-items can be disabled,
        // but Alerts can't.
        $context = array(
            'alert'       => empty($news['alert']) ? null : $news['alert'],
            'information' => empty($news['information']) ? null : $news['information'],
            'error'       => empty($news['error']) ? null : $news['error'],
            'disable'     => $this->getOption('general/backend/news/disable')
        );

        $response = $this->render('components/panel-news.twig', array('context' => $context));
        $response->setCache(array('s_maxage' => '3600', 'public' => true));

        return $response;
    }

    /**
     * Get the 'latest activity' for the dashboard.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function actionLatestActivity()
    {
        $change = $this->app['logger.manager']->getActivity('change', 8);
        $system = $this->app['logger.manager']->getActivity('system', 8, null, 'authentication');

        $response = $this->render('components/panel-activity.twig', array('context' => array(
            'change' => $change,
            'system' => $system,
        )));
        $response->setPublic()->setSharedMaxAge(3600);

        return $response;
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
     * Render a widget, and return the HTML, so it can be inserted in the page.
     *
     * @param string $key
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function actionWidget($key)
    {
        $html = $this->app['extensions']->renderWidget($key);

        return new Response($html, Response::HTTP_OK, array('Cache-Control' => 's-maxage=180, public'));
    }

    /**
     * Render an extension's README.md file.
     *
     * @param string $filename
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function actionReadme($filename)
    {
        $paths = $this->app['resources']->getPaths();

        $filename = $paths['extensionspath'] . '/vendor/' . $filename;

        // don't allow viewing of anything but "readme.md" files.
        if (strtolower(basename($filename)) != 'readme.md') {
            $this->abort(Response::HTTP_UNAUTHORIZED, 'Not allowed');
        }
        if (!is_readable($filename)) {
            $this->abort(Response::HTTP_UNAUTHORIZED, 'Not readable');
        }

        $readme = file_get_contents($filename);

        // Parse the field as Markdown, return HTML
        $html = $this->app['markdown']->text($readme);

        return new Response($html, Response::HTTP_OK, array('Cache-Control' => 's-maxage=180, public'));
    }

    /**
     * Generate a URI based on request parmaeters
     *
     * @param Request $request
     *
     * @return string
     */
    public function actionMakeUri(Request $request)
    {
        $uri = $this->app['storage']->getUri(
            $request->query->get('title'),
            $request->query->get('id'),
            $request->query->get('contenttypeslug'),
            $request->query->getBoolean('fulluri'),
            true,
            $request->query->get('slugfield') //for multipleslug support
        );

        return $uri;
    }

    /**
     * Fetch a JSON encoded set of taxonomy specific tags.
     *
     * @param string $taxonomytype
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function actionTags($taxonomytype)
    {
        $table = $this->getOption('general/database/prefix', 'bolt_');
        $table .= 'taxonomy';

        $query = $this->app['db']->createQueryBuilder()
            ->select("DISTINCT $table.slug")
            ->from($table)
            ->where('taxonomytype = :taxonomytype')
            ->orderBy('slug', 'ASC')
            ->setParameters(array(
                ':taxonomytype' => $taxonomytype
            ));

        $results = $query->execute()->fetchAll();

        return $this->json($results);
    }

    /**
     * Fetch a JSON encoded set of the most popular taxonomy specific tags.
     *
     * @param Request $request
     * @param string  $taxonomytype
     *
     * @return integer|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function actionPopularTags(Request $request, $taxonomytype)
    {
        $table = $this->getOption('general/database/prefix', 'bolt_');
        $table .= 'taxonomy';

        $query = $this->app['db']->createQueryBuilder()
            ->select('slug, COUNT(slug) AS count')
            ->from($table)
            ->where('taxonomytype = :taxonomytype')
            ->groupBy('slug')
            ->orderBy('count', 'DESC')
            ->setMaxResults($request->query->getInt('limit', 20))
            ->setParameters(array(
                ':taxonomytype' => $taxonomytype
            ));

        $results = $query->execute()->fetchAll();

        usort(
            $results,
            function ($a, $b) {
                if ($a['slug'] == $b['slug']) {
                    return 0;
                }

                return ($a['slug'] < $b['slug']) ? -1 : 1;
            }
        );

        return $this->json($results);
    }

    /**
     * Perform an OmniSearch search and return the results.
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function actionOmnisearch(Request $request)
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 3) {
            return $this->json(array());
        }

        $options = $this->app['omnisearch']->query($query);

        return $this->json($options);
    }

    /**
     * Latest {contenttype} to show a small listing in the sidebars.
     *
     * @param string       $contenttypeslug
     * @param integer|null $contentid
     *
     * @return BoltResponse
     */
    public function actionLastModified($contenttypeslug, $contentid = null)
    {
        // Let's find out how we should determine what the latest changes were:
        $contentLogEnabled = (bool) $this->getOption('general/changelog/enabled');

        if ($contentLogEnabled) {
            return $this->getLastmodifiedByContentLog($this->app, $contenttypeslug, $contentid);
        } else {
            return $this->getLastmodifiedSimple($this->app, $contenttypeslug);
        }
    }

    /**
     * List pages in given contenttype, to easily insert links through the Wysywig editor.
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
     * List browse on the server, so we can insert them in the file input.
     *
     * @param string  $namespace
     * @param string  $path
     * @param Request $request
     *
     * @return mixed
     */
    public function actionBrowse($namespace, $path, Request $request)
    {
        // No trailing slashes in the path.
        $path = rtrim($path, '/');

        $filesystem = $this->getFilesystemManager()->getFilesystem($namespace);

        // $key is linked to the fieldname of the original field, so we can
        // Set the selected value in the proper field
        $key = $request->query->get('key');

        // Get the pathsegments, so we can show the path.
        $pathsegments = array();
        $cumulative = "";
        if (!empty($path)) {
            foreach (explode("/", $path) as $segment) {
                $cumulative .= $segment . "/";
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
     * Add a file to the user's stack.
     *
     * @param string $filename
     *
     * @return true
     */
    public function actionAddStack($filename)
    {
        $this->app['stack']->add($filename);

        return true;
    }

    /**
     * Render a user's current stack.
     *
     * @param Request $request
     *
     * @return \Twig_Markup
     */
    public function actionShowStack(Request $request)
    {
        $count = $request->query->get('items', 10);
        $options = $request->query->get('options', false);

        $context = array(
            'stack'     => $this->app['stack']->listitems($count),
            'filetypes' => $this->app['stack']->getFileTypes(),
            'namespace' => $this->app['upload.namespace'],
            'canUpload' => $this->getUsers()->isAllowed('files:uploads')
        );

        switch ($options) {
            case 'minimal':
                $twig = 'components/stack-minimal.twig';
                break;

            case 'list':
                $twig = 'components/stack-list.twig';
                break;

            case 'full':
            default:
                $twig = 'components/panel-stack.twig';
                break;
        }

        return $this->render($twig, array('context' => $context));
    }

    /**
     * Rename a file within the files directory tree.
     *
     * @param Request $request The HTTP Request Object containing the GET Params
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
        $destination = substr($filename, 0, $extensionPos) . "_copy" . substr($filename, $extensionPos);
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
     * Rename a folder within the files directory tree.
     *
     * @param Request $request The HTTP Request Object containing the GET Params
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
     * Delete a folder recursively if writeable.
     *
     * @param Request $request The HTTP Request Object containing the GET Params
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
     * Create a new folder.
     *
     * @param Request $request The HTTP Request Object containing the GET Params
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
     * Generate the change log box for a single record in edit.
     *
     * @param string  $contenttype
     * @param integer $contentid
     *
     * @return string
     */
    public function actionChangeLogRecord($contenttype, $contentid)
    {
        $options = array(
            'contentid' => $contentid,
            'limit'     => 4,
            'order'     => 'date',
            'direction' => 'DESC'
        );

        $context = array(
            'contenttype' => $contenttype,
            'entries'     => $this->app['logger.manager.change']->getChangelogByContentType($contenttype, $options)
        );

        return $this->render('components/panel-change-record.twig', array('context' => $context));
    }

    /**
     * Send an e-mail ping test.
     *
     * @param Request $request
     * @param string  $type
     *
     * @return Response
     */
    public function actionEmailNotification(Request $request, $type)
    {
        $user = $this->getUsers()->getCurrentUser();

        // Create an email
        $mailhtml = $this->render(
            'email/pingtest.twig',
            array(
                'sitename' => $this->getOption('general/sitename'),
                'user'     => $user['displayname'],
                'ip'       => $request->getClientIp()
            )
        )->getContent();

        $senderMail = $this->getOption('general/mailoptions/senderMail', 'bolt@' . $request->getHost());
        $senderName = $this->getOption('general/mailoptions/senderName', $this->getOption('general/sitename'));

        $message = $this->app['mailer']
            ->createMessage('message')
            ->setSubject('Test email from ' . $this->getOption('general/sitename'))
            ->setFrom(array($senderMail  => $senderName))
            ->setTo(array($user['email'] => $user['displayname']))
            ->setBody(strip_tags($mailhtml))
            ->addPart($mailhtml, 'text/html');

        $this->app['mailer']->send($message);

        return new Response('Done', Response::HTTP_OK);
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
        if (!$this->getUsers()->isValidSession()) {
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
