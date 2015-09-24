<?php

namespace Bolt\Controllers;

use Bolt\Translation\Translator as Trans;
use Guzzle\Http\Exception\RequestException as V3RequestException;
use GuzzleHttp\Exception\RequestException;
use League\Flysystem\FileNotFoundException;
use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Async implements ControllerProviderInterface
{
    public function connect(Silex\Application $app)
    {
        /** @var $ctr \Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        $ctr->before(array($this, 'before'));

        $ctr->get("/dashboardnews", array($this, 'dashboardnews'))
            ->bind('dashboardnews');

        $ctr->get("/latestactivity", array($this, 'latestactivity'))
            ->bind('latestactivity');

        $ctr->get("/filesautocomplete", array($this, 'filesautocomplete'));

        $ctr->get("/readme/{filename}", array($this, 'readme'))
            ->assert('filename', '.+')
            ->bind('readme');

        $ctr->get("/widget/{key}", array($this, 'widget'))
            ->bind('widget');

        $ctr->get("/makeuri", array($this, 'makeuri'));

        $ctr->get("/lastmodified/{contenttypeslug}/{contentid}", array($this, 'lastmodified'))
            ->value('contentid', '')
            ->bind('lastmodified');

        $ctr->get("/filebrowser/{contenttype}", array($this, 'filebrowser'))
            ->assert('contenttype', '.*')
            ->bind('filebrowser');

        $ctr->get("/browse/{namespace}/{path}", array($this, 'browse'))
            ->assert('path', '.*')
            ->value('namespace', 'files')
            ->value('path', '')
            ->bind('asyncbrowse');

        $ctr->post("/renamefile", array($this, 'renamefile'))
            ->bind('renamefile');

        $ctr->post("/deletefile", array($this, 'deletefile'))
            ->bind('deletefile');

        $ctr->post("/duplicatefile", array($this, 'duplicatefile'))
            ->bind('duplicatefile');

        $ctr->get("/addstack/{filename}", array($this, 'addstack'))
            ->assert('filename', '.*')
            ->bind('addstack');

        $ctr->get("/tags/{taxonomytype}", array($this, 'tags'))
            ->bind('tags');

        $ctr->get("/populartags/{taxonomytype}", array($this, 'populartags'))
            ->bind('populartags');

        $ctr->get("/showstack", array($this, 'showstack'))
            ->bind('showstack');

        $ctr->get("/omnisearch", array($this, 'omnisearch'));

        $ctr->post("/folder/rename", array($this, 'renamefolder'))
            ->bind('renamefolder');

        $ctr->post("/folder/remove", array($this, 'removefolder'))
            ->bind('removefolder');

        $ctr->post("/folder/create", array($this, 'createfolder'))
            ->bind('createfolder');

        $ctr->get('/changelog/{contenttype}/{contentid}', array($this, 'changelogRecord'))
            ->value('contenttype', '')
            ->value('contentid', '0')
            ->bind('changelogrecord');

        $ctr->get('/email/{type}/{recipient}', array($this, 'emailNotification'))
            ->assert('type', '.*')
            ->bind('emailNotification');

        return $ctr;
    }

    /**
     * News. Film at 11.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardnews(Silex\Application $app, Request $request)
    {
        $source = 'http://news.bolt.cm/';
        $news = $app['cache']->fetch('dashboardnews'); // Two hours.
        $hostname = $request->getHost();
        $body = '';

        // If not cached, get fresh news.
        if ($news === false) {
            $app['logger.system']->info('Fetching from remote server: ' . $source, array('event' => 'news'));

            $driver = $app['db']->getDatabasePlatform()->getName();

            $url = sprintf(
                '%s?v=%s&p=%s&db=%s&name=%s',
                $source,
                rawurlencode($app->getVersion()),
                phpversion(),
                $driver,
                base64_encode($hostname)
            );

            // Options valid if using a proxy
            if ($app['config']->get('general/httpProxy')) {
                $curlOptions = array(
                    'CURLOPT_PROXY'        => $app['config']->get('general/httpProxy/host'),
                    'CURLOPT_PROXYTYPE'    => 'CURLPROXY_HTTP',
                    'CURLOPT_PROXYUSERPWD' => $app['config']->get('general/httpProxy/user') . ':' .
                                                $app['config']->get('general/httpProxy/password')
                );
            }

            // Standard option(s)
            $curlOptions['CURLOPT_CONNECTTIMEOUT'] = 5;

            try {
                if ($app['deprecated.php']) {
                    $fetchedNewsData = $app['guzzle.client']->get($url, null, $curlOptions)->send()->getBody(true);
                } else {
                    $fetchedNewsData = $app['guzzle.client']->get($url, array(), $curlOptions)->getBody(true);
                }

                $fetchedNewsItems = json_decode($fetchedNewsData);

                if ($fetchedNewsItems) {
                    $news = array();

                    // Iterate over the items, pick the first news-item that applies and the first alert we need to show
                    $version = $app->getVersion();
                    foreach ($fetchedNewsItems as $item) {
                        $type = ($item->type === 'alert') ? 'alert' : 'information';
                        if (!isset($news[$type])
                            && (empty($item->target_version) || version_compare($item->target_version, $version, '>'))
                        ) {
                            $news[$type] = $item;
                        }
                    }

                    $app['cache']->save('dashboardnews', $news, 7200);
                } else {
                    $app['logger.system']->error('Invalid JSON feed returned', array('event' => 'news'));
                }
            } catch (RequestException $e) {
                $app['logger.system']->critical(
                    'Error occurred during newsfeed fetch',
                    array('event' => 'exception', 'exception' => $e)
                );

                $body .= "<p>Unable to connect to $source</p>";
            } catch (V3RequestException $e) {
                /** @deprecated remove with the end of PHP 5.3 support */
                $app['logger.system']->critical(
                    'Error occurred during newsfeed fetch',
                    array('event' => 'exception', 'exception' => $e)
                );

                $body .= "<p>Unable to connect to $source</p>";
            }
        } else {
            $app['logger.system']->info('Using cached data', array('event' => 'news'));
        }

        // Combine the body. One 'alert' and one 'info' max. Regular info-items can be disabled, but Alerts can't.
        if (!empty($news['alert'])) {
            $body .= $app['render']->render(
                'components/panel-news.twig',
                array('news' => $news['alert'])
            )->getContent();
        }
        if (!empty($news['information']) && !$app['config']->get('general/backend/news/disable')) {
            $body .= $app['render']->render(
                'components/panel-news.twig',
                array('news' => $news['information'])
            )->getContent();
        }

        return new Response($body, Response::HTTP_OK, array('Cache-Control' => 's-maxage=3600, public'));
    }

    /**
     * Get the 'latest activity' for the dashboard.
     *
     * @param \Silex\Application $app
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function latestactivity(Silex\Application $app)
    {
        $activity = $app['logger.manager']->getActivity('change', 8);

        $body = "";

        if (!empty($activity)) {
            $body .= $app['render']->render(
                'components/panel-change.twig',
                array(
                    'activity' => $activity
                )
            )->getContent();
        }

        $activity = $app['logger.manager']->getActivity('system', 8, null, 'authentication');

        if (!empty($activity)) {
            $body .= $app['render']->render(
                'components/panel-system.twig',
                array(
                    'activity' => $activity
                )
            )->getContent();
        }

        return new Response($body, Response::HTTP_OK, array('Cache-Control' => 's-maxage=3600, public'));
    }

    /**
     * Return autocomplete data for a file name.
     *
     * @param Silex\Application $app
     * @param Request           $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function filesautocomplete(Silex\Application $app, Request $request)
    {
        $term = $request->get('term');

        $extensions = $request->query->get('ext');
        $files = $app['filesystem']->search($term, $extensions);

        $app['debug'] = false;

        return $app->json($files);
    }

    /**
     * Render a widget, and return the HTML, so it can be inserted in the page.
     *
     * @param string             $key
     * @param \Silex\Application $app
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function widget($key, Silex\Application $app)
    {
        $html = $app['extensions']->renderWidget($key);

        return new Response($html, Response::HTTP_OK, array('Cache-Control' => 's-maxage=180, public'));
    }

    /**
     * Render an extension's README.md file.
     *
     * @param string            $filename
     * @param Silex\Application $app
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function readme($filename, Silex\Application $app)
    {
        $paths = $app['resources']->getPaths();

        $filename = $paths['extensionspath'] . '/vendor/' . $filename;

        // don't allow viewing of anything but "readme.md" files.
        if (strtolower(basename($filename)) != 'readme.md') {
            $app->abort(Response::HTTP_UNAUTHORIZED, 'Not allowed');
        }
        if (!is_readable($filename)) {
            $app->abort(Response::HTTP_UNAUTHORIZED, 'Not readable');
        }

        $readme = file_get_contents($filename);

        // Parse the field as Markdown, return HTML
        $html = $app['markdown']->text($readme);

        return new Response($html, Response::HTTP_OK, array('Cache-Control' => 's-maxage=180, public'));
    }

    /**
     * Generate a URI based on request parmaeters
     *
     * @param Silex\Application $app
     * @param Request           $request
     *
     * @return string
     */
    public function makeuri(Silex\Application $app, Request $request)
    {
        $uri = $app['storage']->getUri(
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
     * @param Silex\Application $app
     * @param string            $taxonomytype
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function tags(Silex\Application $app, $taxonomytype)
    {
        $table = $app['config']->get('general/database/prefix', 'bolt_');
        $table .= 'taxonomy';

        $query = $app['db']->createQueryBuilder()
            ->select("DISTINCT $table.name")
            ->from($table)
            ->where('taxonomytype = :taxonomytype')
            ->orderBy('slug', 'ASC')
            ->setParameters(array(
                ':taxonomytype' => $taxonomytype
            ));

        $results = $query->execute()->fetchAll();

        return $app->json($results);
    }

    /**
     * Fetch a JSON encoded set of the most popular taxonomy specific tags.
     *
     * @param Silex\Application $app
     * @param Request           $request
     * @param string            $taxonomytype
     *
     * @return integer|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function populartags(Silex\Application $app, Request $request, $taxonomytype)
    {
        $table = $app['config']->get('general/database/prefix', 'bolt_');
        $table .= 'taxonomy';

        $query = $app['db']->createQueryBuilder()
            ->select('name, COUNT(slug) AS count')
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
                if ($a['name'] == $b['name']) {
                    return 0;
                }

                return ($a['name'] < $b['name']) ? -1 : 1;
            }
        );

        return $app->json($results);
    }

    /**
     * Perform an OmniSearch search and return the results.
     *
     * @param Silex\Application $app
     * @param Request           $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function omnisearch(Silex\Application $app, Request $request)
    {
        $query = $request->query->get('q', '');

        if (strlen($query) < 3) {
            return $app->json(array());
        }

        $options = $app['omnisearch']->query($query);

        return $app->json($options);
    }

    /**
     * Latest {contenttype} to show a small listing in the sidebars.
     *
     * @param \Silex\Application $app
     * @param string             $contenttypeslug
     * @param integer|null       $contentid
     *
     * @return Response
     */
    public function lastmodified(Silex\Application $app, $contenttypeslug, $contentid = null)
    {
        // Let's find out how we should determine what the latest changes were:
        $contentLogEnabled = (bool) $app['config']->get('general/changelog/enabled');

        if ($contentLogEnabled) {
            return $this->lastmodifiedByContentLog($app, $contenttypeslug, $contentid);
        } else {
            return $this->lastmodifiedSimple($app, $contenttypeslug);
        }
    }

    /**
     * Only get latest {contenttype} record edits based on date changed.
     *
     * @param \Silex\Application $app
     * @param string             $contenttypeslug
     *
     * @return Response
     */
    private function lastmodifiedSimple(Silex\Application $app, $contenttypeslug)
    {
        // Get the proper contenttype.
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // Get the 'latest' from the requested contenttype.
        $latest = $app['storage']->getContent($contenttype['slug'], array('limit' => 5, 'order' => 'datechanged DESC', 'hydrate' => false));

        $context = array(
            'latest'      => $latest,
            'contenttype' => $contenttype
        );

        $body = $app['render']->render('components/panel-lastmodified.twig', array('context' => $context))->getContent();

        return new Response($body, Response::HTTP_OK, array('Cache-Control' => 's-maxage=60, public'));
    }

    /**
     * Get last modified records from the content log.
     *
     * @param \Silex\Application $app
     * @param string             $contenttypeslug
     * @param integer            $contentid
     *
     * @return Response
     */
    private function lastmodifiedByContentLog(Silex\Application $app, $contenttypeslug, $contentid)
    {
        // Get the proper contenttype.
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // get the changelog for the requested contenttype.
        $options = array('limit' => 5, 'order' => 'date', 'direction' => 'DESC');

        if (intval($contentid) == 0) {
            $isFiltered = false;
        } else {
            $isFiltered = true;
            $options['contentid'] = intval($contentid);
        }

        $changelog = $app['logger.manager.change']->getChangelogByContentType($contenttype['slug'], $options);

        $context = array(
            'changelog'   => $changelog,
            'contenttype' => $contenttype,
            'contentid'   => $contentid,
            'filtered'    => $isFiltered,
        );

        $body = $app['render']->render('components/panel-lastmodified.twig', array('context' => $context))->getContent();

        return new Response($body, Response::HTTP_OK, array('Cache-Control' => 's-maxage=60, public'));
    }

    /**
     * List pages in given contenttype, to easily insert links through the Wysywig editor.
     *
     * @param string             $contenttype
     * @param \Silex\Application $app
     *
     * @return mixed
     */
    public function filebrowser($contenttype, Silex\Application $app)
    {
        $results = array();

        foreach ($app['storage']->getContentTypes() as $contenttype) {
            $records = $app['storage']->getContent($contenttype, array('published' => true, 'hydrate' => false));

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

        return $app['render']->render('filebrowser/filebrowser.twig', array('context' => $context));
    }

    /**
     * List browse on the server, so we can insert them in the file input.
     *
     * @param string             $namespace
     * @param string             $path
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return mixed
     */
    public function browse($namespace, $path, Silex\Application $app, Request $request)
    {
        // No trailing slashes in the path.
        $path = rtrim($path, '/');

        $filesystem = $app['filesystem']->getFilesystem($namespace);

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
            $app['session']->getFlashBag()->add('error', $msg);
        }

        $app['twig']->addGlobal('title', Trans::__('Files in %s', array('%s' => $path)));

        list($files, $folders) = $filesystem->browse($path, $app);

        $context = array(
            'namespace'    => $namespace,
            'files'        => $files,
            'folders'      => $folders,
            'pathsegments' => $pathsegments,
            'key'          => $key
        );

        return $app['render']->render('files_async/files_async.twig', array('context' => $context));
    }

    /**
     * Add a file to the user's stack.
     *
     * @param string            $filename
     * @param Silex\Application $app
     *
     * @return true
     */
    public function addstack($filename, Silex\Application $app)
    {
        $app['stack']->add($filename);

        return true;
    }

    /**
     * Render a user's current stack.
     *
     * @param Silex\Application $app
     * @param Request $request
     *
     * @return \Twig_Markup
     */
    public function showstack(Silex\Application $app, Request $request)
    {
        $count = $request->query->get('items', 10);
        $options = $request->query->get('options', false);

        $context = array(
            'stack'     => $app['stack']->listitems($count),
            'filetypes' => $app['stack']->getFileTypes(),
            'namespace' => $app['upload.namespace'],
            'canUpload' => $app['users']->isAllowed('files:uploads')
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

        return $app['render']->render($twig, array('context' => $context));
    }

    /**
     * Rename a file within the files directory tree.
     *
     * @param \Silex\Application $app     The Silex Application Container
     * @param Request            $request The HTTP Request Object containing the GET Params
     *
     * @return Boolean Whether the renaming action was successful
     */
    public function renamefile(Silex\Application $app, Request $request)
    {
        $namespace  = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $oldName    = $request->request->get('oldname');
        $newName    = $request->request->get('newname');

        if (!$this->isMatchingExtension($app, $oldName, $newName)) {
            return new JsonResponse(Trans::__('Only root can change file extensions.'), Response::HTTP_FORBIDDEN);
        }

        try {
            if ($app['filesystem']->rename("$namespace://$parentPath/$oldName", "$parentPath/$newName")) {
                return new JsonResponse(null, Response::HTTP_OK);
            }

            return new JsonResponse(Trans::__('Unable to rename file: %FILE%', array('%FILE%' => $oldName)), Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a file on the server.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return bool
     */
    public function deletefile(Silex\Application $app, Request $request)
    {
        $namespace = $request->request->get('namespace');
        $filename = $request->request->get('filename');

        try {
            if ($app['filesystem']->delete("$namespace://$filename")) {
                return new JsonResponse(null, Response::HTTP_OK);
            }

            return new JsonResponse(Trans::__('Unable to delete file: %FILE%', array('%FILE%' => $filename)), Response::HTTP_FORBIDDEN);
        } catch (FileNotFoundException $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Duplicate a file on the server.
     *
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return bool
     */
    public function duplicatefile(Silex\Application $app, Request $request)
    {
        $namespace = $request->request->get('namespace');
        $filename = $request->request->get('filename');

        $filesystem = $app['filesystem']->getFilesystem($namespace);

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
     * @param \Silex\Application $app     The Silex Application Container
     * @param Request            $request The HTTP Request Object containing the GET Params
     *
     * @return Boolean Whether the renaming action was successful
     */
    public function renamefolder(Silex\Application $app, Request $request)
    {
        $namespace  = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $oldName    = $request->request->get('oldname');
        $newName    = $request->request->get('newname');

        try {
            if ($app['filesystem']->rename("$namespace://$parentPath$oldName", "$parentPath$newName")) {
                return new JsonResponse(null, Response::HTTP_OK);
            }

            return new JsonResponse(Trans::__('Unable to rename directory: %DIR%', array('%DIR%' => $oldName)), Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a folder recursively if writeable.
     *
     * @param \Silex\Application $app     The Silex Application Container
     * @param Request            $request The HTTP Request Object containing the GET Params
     *
     * @return Boolean Whether the renaming action was successful
     */
    public function removefolder(Silex\Application $app, Request $request)
    {
        $namespace = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $folderName = $request->request->get('foldername');

        try {
            if ($app['filesystem']->deleteDir("$namespace://$parentPath$folderName")) {
                return new JsonResponse(null, Response::HTTP_OK);
            }

            return new JsonResponse(Trans::__('Unable to delete directory: %DIR%', array('%DIR%' => $folderName)), Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new folder.
     *
     * @param \Silex\Application $app     The Silex Application Container
     * @param Request            $request The HTTP Request Object containing the GET Params
     *
     * @return Boolean Whether the creation was successful
     */
    public function createfolder(Silex\Application $app, Request $request)
    {
        $namespace = $request->request->get('namespace');
        $parentPath = $request->request->get('parent');
        $folderName = $request->request->get('foldername');

        try {
            if ($app['filesystem']->createDir("$namespace://$parentPath$folderName")) {
                return new JsonResponse(null, Response::HTTP_OK);
            }

            return new JsonResponse(Trans::__('Unable to create directory: %DIR%', array('%DIR%' => $folderName)), Response::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return new JsonResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate the change log box for a single record in edit.
     *
     * @param string             $contenttype
     * @param integer            $contentid
     * @param \Silex\Application $app
     *
     * @return string
     */
    public function changelogRecord($contenttype, $contentid, Silex\Application $app)
    {
        $options = array(
            'contentid' => $contentid,
            'limit'     => 4,
            'order'     => 'date',
            'direction' => 'DESC'
        );

        $context = array(
            'contenttype' => $contenttype,
            'entries'     => $app['logger.manager.change']->getChangelogByContentType($contenttype, $options)
        );

        return $app['render']->render('components/panel-change-record.twig', array('context' => $context));
    }

    /**
     * Send an e-mail ping test.
     *
     * @param string             $type
     * @param \Silex\Application $app
     * @param Request            $request
     *
     * @return Response
     */
    public function emailNotification($type, Silex\Application $app, Request $request)
    {
        $user = $app['users']->getCurrentUser();

        // Create an email
        $mailhtml = $app['render']->render(
            'email/pingtest.twig',
            array(
                'sitename' => $app['config']->get('general/sitename'),
                'user'     => $user['displayname'],
                'ip'       => $request->getClientIp()
            )
        )->getContent();

        $senderMail = $app['config']->get('general/mailoptions/senderMail', 'bolt@' . $request->getHost());
        $senderName = $app['config']->get('general/mailoptions/senderName', $app['config']->get('general/sitename'));

        $message = $app['mailer']
            ->createMessage('message')
            ->setSubject('Test email from ' . $app['config']->get('general/sitename'))
            ->setFrom(array($senderMail => $senderName))
            ->setTo(array($user['email'] => $user['displayname']))
            ->setBody(strip_tags($mailhtml))
            ->addPart($mailhtml, 'text/html');

        $app['mailer']->send($message);

        return new Response('Done', Response::HTTP_OK);
    }

    /**
     * Middleware function to do some tasks that should be done for all asynchronous
     * requests.
     */
    public function before(Request $request, Silex\Application $app)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.async.before');

        // If there's no active session, don't do anything.
        if (!$app['users']->isValidSession()) {
            $app->abort(Response::HTTP_UNAUTHORIZED, 'You must be logged in to use this.');
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.async.before');
    }

    /**
     * Check that file extensions are not being changed.
     *
     * @param \Silex\Application $app
     * @param string             $oldName
     * @param string             $newName
     *
     * @return boolean
     */
    private function isMatchingExtension(Silex\Application $app, $oldName, $newName)
    {
        $user = $app['users']->getCurrentUser();
        if ($app['users']->hasRole($user['id'], 'root')) {
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
