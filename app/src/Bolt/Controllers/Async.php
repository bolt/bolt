<?php

Namespace Bolt\Controllers;

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
            ->bind('dashboardnews')
        ;

        $ctr->get("/latestactivity", array($this, 'latestactivity'))
            ->before(array($this, 'before'))
            ->bind('latestactivity')
        ;

        $ctr->get("/filesautocomplete", array($this, 'filesautocomplete'))
            ->before(array($this, 'before'))
        ;

        $ctr->get("/readme/{extension}", array($this, 'readme'))
            ->before(array($this, 'before'))
            ->bind('readme')
        ;

        $ctr->get("/widget/{key}", array($this, 'widget'))
            ->before(array($this, 'before'))
            ->bind('widget')
        ;

        $ctr->post("/markdownify", array($this, 'markdownify'))
            ->before(array($this, 'before'))
            ->bind('markdownify')
        ;

        $ctr->get("/makeuri", array($this, 'makeuri'))
            ->before(array($this, 'before'))
        ;

        $ctr->get("/lastmodified/{contenttypeslug}", array($this, 'lastmodified'))
            ->before(array($this, 'before'))
            ->bind('lastmodified')
        ;

        return $ctr;
    }
    /**
     * News.
     */
    function dashboardnews(Silex\Application $app) {

        $news = $app['cache']->fetch('dashboardnews'); // Two hours.

        $name = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];

        // If not cached, get fresh news..
        if ($news == false) {

            $app['log']->add("News: fetch from remote server..", 1);

            $driver = !empty($app['config']['general']['database']['driver']) ? $app['config']['general']['database']['driver'] : 'sqlite';

            $url = sprintf('http://news.bolt.cm/?v=%s&p=%s&db=%s&name=%s',
                rawurlencode($app->getVersion()),
                phpversion(),
                $driver,
                base64_encode($name)
            );

            // If there's a proxy ...
            if (!empty($app['config']['general']['httpProxy'])) {
                $options = array (
                    'curl.options' => array (
                            'CURLOPT_PROXY'          => $app['config']['general']['httpProxy']['host'],     
                            'CURLOPT_PROXYTYPE'      => 'CURLPROXY_HTTP',
                            'CURLOPT_PROXYUSERPWD'   => $app['config']['general']['httpProxy']['user'] . ':' . $app['config']['general']['httpProxy']['password']
                    )
                );
                $guzzleclient = new \Guzzle\Http\Client($url, $options);
            } else {
                $guzzleclient = new \Guzzle\Http\Client($url);    
            }
            
            $news = $guzzleclient->get("/")->send()->getBody(true);
            $news = json_decode($news);

            // For now, just use the most current item.
            $news = current($news);

            $app['cache']->save('dashboardnews', $news, 7200);

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

        $activity = $app['log']->getActivity(8, 3);

        $body = $app['twig']->render('dashboard-activity.twig', array('activity' => $activity));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }

    function filesautocomplete(Silex\Application $app, Request $request) {

        $term = $request->get('term');

        $ext = $request->query->get('ext');
        if (empty($ext)) {
            $extensions = 'jpg,jpeg,gif,png';
        } else {
            $extensions = $request->query->get('extensions');
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

        // Parse the field as Markdown, return HTML
        $markdownParser = new \dflydev\markdown\MarkdownParser();
        $html = $markdownParser->transformMarkdown($readme);

        return new Response($html, 200, array('Cache-Control' => 's-maxage=180, public'));

    }

    function markdownify(Silex\Application $app, Request $request) {

        $html = $request->request->get('html');

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

        $uri = $app['storage']->getUri($request->query->get('title'), $request->query->get('id'), $request->query->get('contenttypeslug'), $request->query->get('fulluri'));

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

        $body = $app['twig']->render('_sub_lastmodified.twig', array('latest' => $latest, 'contenttype' => $contenttype ));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=60, public'));

    }

    /**
     * Middleware function to do some tasks that should be done for all aynchronous
     * requests.
     */
    function before(Request $request, Silex\Application $app) {

        // Only set which endpoint it is, if it's not already set. Which it is, in cases like
        // when it's embedded on a page using {{ render() }}
        // @todo Is this still needed?
        if (empty($app['end'])) {
            $app['end'] = "asynchronous";
        }

        // If there's no active session, don't do anything..
        if (!$app['users']->checkValidSession()) {
            $app->abort(404, "You must be logged in to use this.");
        }

    }


}
