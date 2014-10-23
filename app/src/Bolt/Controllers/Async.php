<?php

namespace Bolt\Controllers;

use Guzzle\Http\Exception\RequestException;
use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Async implements ControllerProviderInterface
{
    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];

        $ctr->get("/dashboardnews", array($this, 'dashboardnews'))
            ->before(array($this, 'before'))
            ->bind('dashboardnews');

        $ctr->get("/latestactivity", array($this, 'latestactivity'))
            ->before(array($this, 'before'))
            ->bind('latestactivity');

        $ctr->get("/filesautocomplete", array($this, 'filesautocomplete'))
            ->before(array($this, 'before'));

        $ctr->get("/readme/{extension}", array($this, 'readme'))
            ->before(array($this, 'before'))
            ->bind('readme');

        $ctr->get("/widget/{key}", array($this, 'widget'))
            ->before(array($this, 'before'))
            ->bind('widget');

        $ctr->post("/markdownify", array($this, 'markdownify'))
            ->before(array($this, 'before'))
            ->bind('markdownify');

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

        $ctr->get("/browse/{path}", array($this, 'browse'))
            ->before(array($this, 'before'))
            ->assert('path', '.+')
            ->bind('asyncbrowse');

        $ctr->post("/deletefile", array($this, 'deletefile'))
            ->before(array($this, 'before'))
            ->bind('deletefile');

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

        $body = $app['render']->render('dashboard-news.twig', array('news' => $news));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }

    /**
     * Get the 'latest activity' for the dashboard..
     */
    public function latestactivity(Silex\Application $app)
    {
        $activity = $app['log']->getActivity(8, 3);

        $body = $app['render']->render('dashboard-activity.twig', array('activity' => $activity));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }

    public function filesautocomplete(Silex\Application $app, Request $request)
    {
        $term = $request->get('term');

        $extensions = $request->query->get('ext');
        if (empty($extensions)) {
            $extensions = 'jpg,jpeg,gif,png';
        }

        $files = findFiles($term, $extensions);

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

    public function readme($extension, Silex\Application $app, Request $request)
    {
        $filename = __DIR__ . "/../../../extensions/" . $extension . "/readme.md";

        //echo "<pre>\n" . \util::var_dump($filename, true) . "</pre>\n";

        $readme = file_get_contents($filename);

        // Parse the field as Markdown, return HTML
        $html = \Parsedown::instance()->parse($readme);

        return new Response($html, 200, array('Cache-Control' => 's-maxage=180, public'));

    }

    public function markdownify(Silex\Application $app, Request $request)
    {
        $html = $request->request->get('html');

        if (isHtml($html)) {

            require_once(__DIR__ . '/../../../classes/markdownify/markdownify_extra.php');
            $markdown = new \Markdownify(false, 80, false);

            $output = $markdown->parseString($html);

        } else {
            $output = $html;
        }

        return $output;

    }

    public function makeuri(Silex\Application $app, Request $request)
    {
        $uri = $app['storage']->getUri($request->query->get('title'), $request->query->get('id'), $request->query->get('contenttypeslug'), $request->query->get('fulluri'));

        return $uri;
    }


    public function tags(Silex\Application $app, $taxonomytype)
    {
        $prefix = $app['config']->get('general/database/prefix', "bolt_");

        // \util::var_dump($taxonomytype);
        $query = "select distinct `%staxonomy`.`slug` from `%staxonomy` where `taxonomytype` = ? order by `slug` asc;";
        $query = sprintf($query, $prefix, $prefix);
        $query = $app['db']->executeQuery($query, array($taxonomytype));

        $results = $query->fetchAll();
        return $app->json($results);
    }

    public function populartags(Silex\Application $app, $taxonomytype)
    {
        $prefix = $app['config']->get('general/database/prefix', "bolt_");

        $limit = $app['request']->get('limit', 20);

        $query = "select `slug` , count(`slug`) as `count` from  `%staxonomy` where `taxonomytype` = ? group by  `slug` order by `count` desc limit %s";
        $query = sprintf($query, $prefix, intval($limit));
        $query = $app['db']->executeQuery($query, array($taxonomytype));


        $results = $query->fetchAll();

        usort($results, function ($a, $b) {

            if ($a['slug'] == $b['slug']) {
                return 0;
            }
            return ($a['slug'] < $b['slug']) ? -1 : 1;

        });


        return $app->json($results);
    }


    /**
     * Latest {contenttype} to show a small listing in the sidebars..
     */
    public function lastmodified(Silex\Application $app, $contenttypeslug, $contentid = null)
    {
        // Let's find out how we should determine what the latest changes were:
        $contentLogEnabled = (bool)$app['config']->get('general/changelog/enabled');

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

        $body = $app['render']->render('_sub_lastmodified.twig', array('latest' => $latest, 'contenttype' => $contenttype));
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

        $renderVars = array(
            'changelog' => $changelog,
            'contenttype' => $contenttype,
            'contentid' => $contentid,
            'filtered' => $isFiltered,
            );
        $body = $app['render']->render('_sub_lastmodified.twig', $renderVars);
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
    public function filebrowser($contenttype = 'pages', Silex\Application $app, Request $request)
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

        return $app['render']->render('filebrowser.twig', array(
            'results' => $results
        ));

    }


    /**
     * List browse on the server, so we can insert them in the file input.
     *
     * @param $path
     * @param  Silex\Application $app
     * @param  Request           $request
     * @return mixed
     */
    public function browse($path, Silex\Application $app, Request $request)
    {
        $files = array();
        $folders = array();

        // $key is linked to the fieldname of the original field, so we can
        // Set the selected value in the proper field
        $key = $app['request']->get('key');

        $basefolder = BOLT_WEB_DIR . '/';
        $path = stripTrailingSlash(str_replace("..", "", $path));
        $currentfolder = realpath($basefolder . $path);

        $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");

        // Get the pathsegments, so we can show the path..
        $pathsegments = array();
        $cumulative = "";
        if (!empty($path)) {
            foreach (explode("/", $path) as $segment) {
                $cumulative .= $segment . "/";
                $pathsegments[$cumulative] = $segment;
            }
        }

        if (file_exists($currentfolder)) {

            $d = dir($currentfolder);

            while (false !== ($entry = $d->read())) {

                if (in_array($entry, $ignored)) {
                    continue;
                }

                $fullfilename = $currentfolder . "/" . $entry;

                if (is_file($fullfilename)) {
                    $relativepath = str_replace("files/", "", ($path . "/" . $entry));
                    $files[$entry] = array(
                        'path' => $path,
                        'filename' => $entry,
                        'newpath' => $path . "/" . $entry,
                        'relativepath' => $relativepath,
                        'writable' => is_writable($fullfilename),
                        'readable' => is_readable($fullfilename),
                        'type' => strtolower(getExtension($entry)),
                        'filesize' => formatFilesize(filesize($fullfilename)),
                        'modified' => date("Y/m/d H:i:s", filemtime($fullfilename)),
                        'permissions' => \util::full_permissions($fullfilename)
                    );

                    if (in_array(strtolower(getExtension($entry)), array('gif', 'jpg', 'png', 'jpeg'))) {
                        $size = getimagesize($fullfilename);
                        $files[$entry]['imagesize'] = sprintf("%s × %s", $size[0], $size[1]);
                    }
                }

                if (is_dir($fullfilename)) {
                    $folders[$entry] = array(
                        'path' => $path,
                        'foldername' => $entry,
                        'newpath' => $path . "/" . $entry,
                        'writable' => is_writable($fullfilename),
                        'modified' => date("Y/m/d H:i:s", filemtime($fullfilename))
                    );
                }

            }

            $d->close();

        } else {
            $app['session']->getFlashBag()->set('error', __("Folder '%s' could not be found, or is not readable.", array('%s' => $path)));
        }

        $app['twig']->addGlobal('title', __("Files in %s", array('%s' => $path)));

        // Make sure the files and folders are sorted properly.
        ksort($files);
        ksort($folders);

        return $app['render']->render('files_async.twig', array(
            'path' => $path,
            'files' => $files,
            'folders' => $folders,
            'pathsegments' => $pathsegments,
            'key' => $key
        ));

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
        $filename = $request->request->get('filename');

        $filePath = BOLT_WEB_DIR . '/' . $filename;

        // TODO: ensure that we are deleting a file inside /files folder

        if (is_file($filePath) && is_readable($filePath)) {
            @unlink($filePath);
            return true;
        } else {
            return false;
        }

    }

    public function addstack($filename = "", Silex\Application $app)
    {

        // \util::var_dump($filename);

        $app['stack']->add($filename);

        return true;

    }



    public function showstack(Silex\Application $app)
    {

        $count = $app['request']->get('items', 10);
        $options = $app['request']->get('options', false);

        $stack = $app['stack']->listitems($count);

        return $app['render']->render('_sub_stack.twig', array(
            'stack' => $stack,
            'options' => $options,
            'filetypes' => $app['stack']->getFileTypes()
        ));

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
