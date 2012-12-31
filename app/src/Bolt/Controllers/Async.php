<?php

Namespace Bolt\Controllers;

Use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Async
{
    /**
     * News.
     */
    function dashboardnews(Silex\Application $app) {
        global $bolt_version, $app;

        $news = $app['cache']->get('dashboardnews', 7200); // Two hours.

        $name = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];

        // If not cached, get fresh news..
        if ($news == false) {

            $app['log']->add("News: fetch from remote server..", 1);

            $driver = !empty($app['config']['general']['database']['driver']) ? $app['config']['general']['database']['driver'] : 'sqlite';

            $url = sprintf('http://news.bolt.cm/?v=%s&p=%s&db=%s&name=%s',
                $bolt_version,
                phpversion(),
                $driver,
                base64_encode($name)
            );

            $guzzleclient = new \Guzzle\Http\Client($url);

            $news = $guzzleclient->get("/")->send()->getBody(true);
            $news = json_decode($news);

            // For now, just use the most current item.
            $news = current($news);

            $app['cache']->set('dashboardnews', $news);

        } else {
            $app['log']->add("News: get from cache..", 1);
        }

        $body = $app['twig']->render('dashboard-news.twig', array('news' => $news ));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }

    /**
     * Get the 'latest activity' for the dashboard..
     */
    function latestactivity(Silex\Application $app) {
        global $bolt_version, $app;

        $activity = $app['log']->getActivity(8, 3);

        $body = $app['twig']->render('dashboard-activity.twig', array('activity' => $activity));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }

    function filesautocomplete(Silex\Application $app, Request $request) {

        $term = $request->get('term');

        if (empty($_GET['ext'])) {
            $extensions = 'jpg,jpeg,gif,png';
        } else {
            $extensions = $_GET['extensions'];
        }

        $files = findFiles($term, $extensions);

        $app['debug'] = false;

        return $app->json($files);

    }

    /**
     * Render a widget, and return the HTML, so it can be inserted in the page.
     *
     */
    function widget($key, Silex\Application $app, Request $request) {

        $html = $app['extensions']->renderWidget($key);

        return new Response($html, 200, array('Cache-Control' => 's-maxage=180, public'));

    }

    function readme($extension, Silex\Application $app, Request $request) {

        $filename = __DIR__."/../../../extensions/".$extension."/readme.md";

        //echo "<pre>\n" . \util::var_dump($filename, true) . "</pre>\n";

        $readme = file_get_contents($filename);

        include_once __DIR__. "/../../../classes/markdown.php";
        $html = \Markdown($readme);

        return new Response($html, 200, array('Cache-Control' => 's-maxage=180, public'));

    }

    function markdownify(Silex\Application $app, Request $request) {

        $html = $_POST['html'];

        if (isHtml($html)) {

            require_once(__DIR__.'/../../../classes/markdownify/markdownify_extra.php');
            $md = new \Markdownify(false, 80, false);

            $output = $md->parseString($html);

        } else {
            $output = $html;
        }

        return $output;

    }

    function makeuri(Silex\Application $app, Request $request) {

        $uri = $app['storage']->getUri($_GET['title'], $_GET['id'], $_GET['contenttypeslug'], $_GET['fulluri']);

        return $uri;

    }


    /**
     * Latest {contenttype} to show a small listing in the sidebars..
     */
    function lastmodified(Silex\Application $app, $contenttypeslug) {

        // Get the proper contenttype..
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // get the 'latest' from the requested contenttype.
        $latest = $app['storage']->getContent($contenttype['slug'], array('limit' => 5, 'order' => 'datechanged DESC'));

        $body = $app['twig']->render('sidebar-lastmodified.twig', array('latest' => $latest, 'contenttype' => $contenttype ));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=60, public'));

    }

    /**
     * Middleware function to do some tasks that should be done for all aynchronous
     * requests.
     */
    function before(Request $request, Silex\Application $app) {

        // Only set which endpoint it is, if it's not already set. Which it is, in cases like
        // when it's embedded on a page using {{ render() }}
        if (empty($app['end'])) {
            $app['end'] = "asynchronous";
        }

        // If there's no active session, don't do anything..
        if (!$app['users']->checkValidSession()) {
            $app->abort(404, "You must be logged in to use this.");
        }

    }


}
