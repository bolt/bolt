<?php

namespace Bolt\Controllers;

use Bolt\Translation\Translator as Trans;

use Guzzle\Http\Exception\RequestException;
use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class Async implements ControllerProviderInterface
{
    public function connect(Silex\Application $app)
    {
        /** @var $ctr \Silex\ControllerCollection */
        $ctr = $app['controllers_factory'];

        $ctr->get("/dashboardnews", array($this, 'dashboardnews'))
            ->before(array($this, 'before'))
            ->bind('dashboardnews');

        $ctr->get("/latestactivity", array($this, 'latestactivity'))
            ->before(array($this, 'before'))
            ->bind('latestactivity');

        $ctr->get("/filesautocomplete", array($this, 'filesautocomplete'))
            ->before(array($this, 'before'));

        $ctr->get("/readme/{filename}", array($this, 'readme'))
            ->before(array($this, 'before'))
            ->assert('filename', '.+')
            ->bind('readme');

        $ctr->get("/widget/{key}", array($this, 'widget'))
            ->before(array($this, 'before'))
            ->bind('widget');

        $ctr->get("/makeuri", array($this, 'makeuri'))
            ->before(array($this, 'before'));

        $ctr->get("/lastmodified/{contenttypeslug}/{contentid}", array($this, 'lastmodified'))
            ->value('contentid', '')
            ->before(array($this, 'before'))
            ->bind('lastmodified');

        $ctr->get("/filebrowser/{contenttype}", array($this, 'filebrowser'))
            ->before(array($this, 'before'))
            ->assert('contenttype', '.*')
            ->bind('contenttype');

        $ctr->get("/browse/{namespace}/{path}", array($this, 'browse'))
            ->before(array($this, 'before'))
            ->assert('path', '.*')
            ->value('namespace', 'files')
            ->value('path', '')
            ->bind('asyncbrowse');

        $ctr->post("/renamefile", array($this, 'renamefile'))
            ->before(array($this, 'before'))
            ->bind('renamefile');

        $ctr->post("/deletefile", array($this, 'deletefile'))
            ->before(array($this, 'before'))
            ->bind('deletefile');

        $ctr->post("/duplicatefile", array($this, 'duplicatefile'))
            ->before(array($this, 'before'))
            ->bind('duplicatefile');

        $ctr->get("/addstack/{filename}", array($this, 'addstack'))
            ->before(array($this, 'before'))
            ->assert('filename', '.*')
            ->bind('addstack');

        $ctr->get("/tags/{taxonomytype}", array($this, 'tags'))
            ->before(array($this, 'before'))
            ->bind('tags');

        $ctr->get("/populartags/{taxonomytype}", array($this, 'populartags'))
            ->before(array($this, 'before'))
            ->bind('populartags');

        $ctr->get("/showstack", array($this, 'showstack'))
            ->before(array($this, 'before'))
            ->bind('showstack');

        $ctr->get("/omnisearch", array($this, 'omnisearch'))
            ->before(array($this, 'before'));

        $ctr->post("/folder/rename", array($this, 'renamefolder'))
            ->before(array($this, 'before'))
            ->bind('renamefolder');

        $ctr->post("/folder/remove", array($this, 'removefolder'))
            ->before(array($this, 'before'))
            ->bind('removefolder');

        $ctr->post("/folder/create", array($this, 'createfolder'))
            ->before(array($this, 'before'))
            ->bind('createfolder');

        return $ctr;
    }

    /**
     * News.
     */
    public function dashboardnews(Silex\Application $app)
    {
        $news = $app['cache']->fetch('dashboardnews'); // Two hours.

        $name = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];

        // If not cached, get fresh news..
        if ($news == false) {

            $app['log']->add("News: fetch from remote server..", 1);

            $driver = $app['config']->get('general/database/driver', 'sqlite');

            $url = sprintf(
                'http://news.bolt.cm/?v=%s&p=%s&db=%s&name=%s',
                rawurlencode($app->getVersion()),
                phpversion(),
                $driver,
                base64_encode($name)
            );

            $curlOptions = array('CURLOPT_CONNECTTIMEOUT' => 5);
            // If there's a proxy ...
            if ($app['config']->get('general/httpProxy')) {
                $curlOptions['CURLOPT_PROXY'] = $app['config']->get('general/httpProxy/host');
                $curlOptions['CURLOPT_PROXYTYPE'] = 'CURLPROXY_HTTP';
                $curlOptions['CURLOPT_PROXYUSERPWD'] = $app['config']->get('general/httpProxy/user') . ':' . $app['config']->get('general/httpProxy/password');
            }
            $guzzleclient = new \Guzzle\Http\Client($url, array('curl.options' => $curlOptions));

            try {
                $newsData = $guzzleclient->get("/")->send()->getBody(true);
                $news = json_decode($newsData);
                if ($news) {
                    // For now, just use the most current item.
                    $news = current($news);

                    $app['cache']->save('dashboardnews', $news, 7200);
                } else {
                    $app['log']->add("News: got invalid JSON feed", 1);
                }

            } catch (RequestException $re) {
                $app['log']->add("News: got exception: " . $re->getMessage(), 1);
            }

        } else {
            $app['log']->add("News: get from cache..", 1);
        }

        $body = $app['render']->render('components/panel-news.twig', array('news' => $news));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));
    }

    /**
     * Get the 'latest activity' for the dashboard..
     */
    public function latestactivity(Silex\Application $app)
    {
        $activity = $app['log']->getActivity(8, 3);

        $body = $app['render']->render('components/panel-activity.twig', array('activity' => $activity));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));
    }

    public function filesautocomplete(Silex\Application $app, Request $request)
    {
        $term = $request->get('term');
        $filesystem = $app['filesystem']->getManager('files');

        $extensions = $request->query->get('ext');
        $files = $filesystem->search($term, $extensions);

        $app['debug'] = false;

        return $app->json($files);
    }

    /**
     * Render a widget, and return the HTML, so it can be inserted in the page.
     *
     */
    public function widget($key, Silex\Application $app, Request $request)
    {
        $html = $app['extensions']->renderWidget($key);

        return new Response($html, 200, array('Cache-Control' => 's-maxage=180, public'));
    }

    public function readme($filename, Silex\Application $app, Request $request)
    {
        $paths = $app['resources']->getPaths();

        $filename = $paths['extensionspath'] . '/vendor/' . $filename;

        // don't allow viewing of anything but "readme.md" files.
        if (strtolower(basename($filename)) != 'readme.md') {
            $app->abort(401, 'Not allowed');
        }
        if (!is_readable($filename)) {
            $app->abort(401, 'Not readable');
        }

        $readme = file_get_contents($filename);

        // Parse the field as Markdown, return HTML
        $html = \ParsedownExtra::instance()->text($readme);

        return new Response($html, 200, array('Cache-Control' => 's-maxage=180, public'));
    }

    public function makeuri(Silex\Application $app, Request $request)
    {
        $uri = $app['storage']->getUri($request->query->get('title'), $request->query->get('id'), $request->query->get('contenttypeslug'), $request->query->get('fulluri'));

        return $uri;
    }

    public function tags(Silex\Application $app, $taxonomytype)
    {
        $table = $app['config']->get('general/database/prefix', "bolt_");
        $table .= 'taxonomy';

        $query = sprintf("SELECT DISTINCT %s.slug from %s where taxonomytype = ? order by slug ASC;",
            $table, $table);
        $query = $app['db']->executeQuery($query, array($taxonomytype));

        $results = $query->fetchAll();

        return $app->json($results);
    }

    public function populartags(Silex\Application $app, $taxonomytype)
    {
        $table = $app['config']->get('general/database/prefix', "bolt_");
        $table .= 'taxonomy';

        $limit = $app['request']->get('limit', 20);

        $query = sprintf("SELECT slug , COUNT(slug) as count from  %s where taxonomytype = ? GROUP BY  slug ORDER BY count DESC LIMIT %s",
            $table, intval($limit));
        $query = $app['db']->executeQuery($query, array($taxonomytype));

        $results = $query->fetchAll();

        usort(
            $results,
            function ($a, $b) {
                if ($a['slug'] == $b['slug']) {
                    return 0;
                }

                return ($a['slug'] < $b['slug']) ? -1 : 1;
            }
        );

        return $app->json($results);
    }

    public function omnisearch(Silex\Application $app)
    {
        $query = $app['request']->get('q', '');

        if (strlen($query) < 3) {
            return $app->json(array());
        }

        $options = $app['omnisearch']->query($query);

        return $app->json($options);
    }

    /**
     * Latest {contenttype} to show a small listing in the sidebars..
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

    private function lastmodifiedSimple(Silex\Application $app, $contenttypeslug)
    {
        // Get the proper contenttype..
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // get the 'latest' from the requested contenttype.
        $latest = $app['storage']->getContent($contenttype['slug'], array('limit' => 5, 'order' => 'datechanged DESC', 'hydrate' => false));

        $context = array(
            'latest' => $latest,
            'contenttype' => $contenttype
        );

        $body = $app['render']->render('components/panel-lastmodified.twig', array('context' => $context));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=60, public'));
    }

    private function lastmodifiedByContentLog(Silex\Application $app, $contenttypeslug, $contentid)
    {
        // Get the proper contenttype..
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // get the changelog for the requested contenttype.
        $options = array('limit' => 5, 'order' => 'date DESC');
        if (intval($contentid) == 0) {
            $isFiltered = false;
        } else {
            $isFiltered = true;
            $options['contentid'] = intval($contentid);
        }
        $changelog = $app['storage']->getChangelogByContentType($contenttype['slug'], $options);

        $context = array(
            'changelog' => $changelog,
            'contenttype' => $contenttype,
            'contentid' => $contentid,
            'filtered' => $isFiltered,
        );

        $body = $app['render']->render('components/panel-lastmodified.twig', array('context' => $context));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=60, public'));
    }

    /**
     * List pages in given contenttype, to easily insert links through the Wysywig editor.
     *
     * @param  string            $contenttype
     * @param  Silex\Application $app
     * @param  Request           $request
     * @return mixed
     */
    public function filebrowser($contenttype, Silex\Application $app, Request $request)
    {
        foreach ($app['storage']->getContentTypes() as $contenttype) {

            $records = $app['storage']->getContent($contenttype, array('published' => true, 'hydrate' => false));

            foreach ($records as $key => $record) {
                $results[$contenttype][] = array(
                    'title' => $record->gettitle(),
                    'id' => $record->id,
                    'link' => $record->link(),
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
     * @param $namespace
     * @param $path
     * @param  Silex\Application $app
     * @param  Request           $request
     * @return mixed
     */
    public function browse($namespace, $path, Silex\Application $app, Request $request)
    {
        // No trailing slashes in the path.
        $path = rtrim($path, '/');

        $filesystem = $app['filesystem']->getManager($namespace);

        // $key is linked to the fieldname of the original field, so we can
        // Set the selected value in the proper field
        $key = $app['request']->get('key');

        // Get the pathsegments, so we can show the path..
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
            $app['session']->getFlashBag()->set('error', Trans::__("Folder '%s' could not be found, or is not readable.", array('%s' => $path)));
        }

        $app['twig']->addGlobal('title', Trans::__('Files in %s', array('%s' => $path)));

        list($files, $folders) = $filesystem->browse($path, $app);

        $context = array(
            'namespace' => $namespace,
            //'path' => $path, Unused?!
            'files' => $files,
            'folders' => $folders,
            'pathsegments' => $pathsegments,
            'key' => $key
        );

        return $app['render']->render('files_async/files_async.twig', array('context' => $context));
    }

    public function addstack($filename, Silex\Application $app)
    {
        $app['stack']->add($filename);

        return true;
    }

    public function showstack(Silex\Application $app)
    {
        $count = $app['request']->get('items', 10);
        $options = $app['request']->get('options', false);

        $context = array(
            'stack' => $app['stack']->listitems($count),
            'filetypes' => $app['stack']->getFileTypes(),
            'namespace' => $app['upload.namespace']
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
     * @param Silex\Application $app     The Silex Application Container
     * @param Request           $request The HTTP Request Object containing the GET Params
     *
     * @return Boolean Whether the renaming action was successful
     */
    public function renamefile(Silex\Application $app, Request $request)
    {
        $namespace  = $request->request->get('namespace', 'files');
        $parentPath = $request->request->get('parent');
        $oldName    = $request->request->get('oldname');
        $newName    = $request->request->get('newname');

        $oldPath    = $app['resources']->getPath($namespace)
                      . DIRECTORY_SEPARATOR
                      . $parentPath
                      . DIRECTORY_SEPARATOR
                      . $oldName;

        $newPath    = $app['resources']->getPath($namespace)
                      . DIRECTORY_SEPARATOR
                      . $parentPath
                      . DIRECTORY_SEPARATOR
                      . $newName;

        $fileSystemHelper = new Filesystem();

        try {
            $fileSystemHelper->rename($oldPath, $newPath, false /* Don't rename if target exists already! */);
        } catch (IOException $exception) {

            /* Thrown if target already exists or renaming failed. */

            return false;
        }

        return true;
    }

    /**
     * Delete a file on the server.
     *
     * @param  Silex\Application $app
     * @param  Request           $request
     * @return bool
     */
    public function deletefile(Silex\Application $app, Request $request)
    {
        $namespace = $request->request->get('namespace', 'files');
        $filename = $request->request->get('filename');

        $filesystem = $app['filesystem']->getManager($namespace);

        if ($filesystem->delete($filename)) {
            return true;
        }

        return false;
    }

    /**
     * Duplicate a file on the server.
     *
     * @param  Silex\Application $app
     * @param  Request           $request
     * @return bool
     */
    public function duplicatefile(Silex\Application $app, Request $request)
    {
        $namespace = $request->request->get('namespace', 'files');
        $filename = $request->request->get('filename');

        $filesystem = $app['filesystem']->getManager($namespace);

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
     * @param Silex\Application $app     The Silex Application Container
     * @param Request           $request The HTTP Request Object containing the GET Params
     *
     * @return Boolean Whether the renaming action was successful
     */
    public function renamefolder(Silex\Application $app, Request $request)
    {
        $namespace = $request->request->get('namespace', 'files');

        $parentPath = $request->request->get('parent');
        $oldName    = $request->request->get('oldname');
        $newName    = $request->request->get('newname');

        $oldPath    = $app['resources']->getPath($namespace)
                      . DIRECTORY_SEPARATOR
                      . $parentPath
                      . $oldName;

        $newPath    = $app['resources']->getPath($namespace)
                      . DIRECTORY_SEPARATOR
                      . $parentPath
                      . $newName;

        $fileSystemHelper = new Filesystem();

        try {
            $fileSystemHelper->rename(
                $oldPath,
                $newPath,
                false /* Don't rename if target exists already! */
            );
        } catch (IOException $exception) {

            /* Thrown if target already exists or renaming failed. */

            return false;
        }

        return true;
    }

    /**
     * Delete a folder recursively if writeable.
     *
     * @param Silex\Application $app     The Silex Application Container
     * @param Request           $request The HTTP Request Object containing the GET Params
     *
     * @return Boolean Whether the renaming action was successful
     */
    public function removefolder(Silex\Application $app, Request $request)
    {
        $namespace = $request->request->get('namespace', 'files');

        $parentPath = $request->request->get('parent');
        $folderName = $request->request->get('foldername');

        $completePath = $parentPath . $folderName;

        $filesystem = $app['filesystem']->getManager($namespace);

        if ($filesystem->deleteDir($completePath)) {
            return true;
        }

        return false;
    }

    /**
     * Create a new folder.
     *
     * @param Silex\Application $app     The Silex Application Container
     * @param Request           $request he HTTP Request Object containing the GET Params
     *
     * @return Boolean Whether the creation was successful
     */
    public function createfolder(Silex\Application $app, Request $request)
    {
        $namespace = $request->request->get('namespace', 'files');

        $parentPath = $request->request->get('parent');
        $folderName = $request->request->get('foldername');

        $newpath = $parentPath . $folderName;

        $filesystem = $app['filesystem']->getManager($namespace);

        if ($filesystem->createDir($newpath)) {
            return true;
        }

        return false;
    }

    /**
     * Middleware function to do some tasks that should be done for all aynchronous
     * requests.
     */
    public function before(Request $request, Silex\Application $app)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.async.before');

        // Only set which endpoint it is, if it's not already set. Which it is, in cases like
        // when it's embedded on a page using {{ render() }}
        // @todo Is this still needed?
        if (empty($app['end'])) {
            $app['end'] = "asynchronous";
        }

        // If there's no active session, don't do anything..
        if (!$app['users']->isValidSession()) {
            $app->abort(404, "You must be logged in to use this.");
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.async.before');
    }
}
