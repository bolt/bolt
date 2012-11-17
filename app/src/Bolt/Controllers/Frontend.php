<?php

namespace Bolt\Controllers;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Frontend
{

    function before(Request $request, Silex\Application $app)
    {

        $app['end'] = "frontend";

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['storage']->checkUserTableIntegrity() || !$app['users']->getUsers()) {
            $app['session']->setFlash('info', "There are no users in the database. Please create the first user.");
            return redirect('useredit', array('id' => ""));
        }

        $app['twig']->addGlobal('frontend', true);
        $app['twig']->addGlobal('paths', $app['paths']);
    }

    function homepage(Silex\Application $app)
    {

        if (!empty($app['config']['general']['homepage_template'])) {
            $template = $app['config']['general']['homepage_template'];
            $content = $app['storage']->getContent($app['config']['general']['homepage']);
            $twigvars = array(
                'record' => $content,
                $content->contenttype['singular_slug'] => $content
            );
        } else {
            $template = 'index.twig';
            $twigvars = array();
        }

        $body = $app['twig']->render($template, $twigvars);

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }

    function record(Silex\Application $app, $contenttypeslug, $slug)
    {

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $slug = makeSlug($slug);

        // First, try to get it by slug.
        $content = $app['storage']->getContent($contenttype['slug'], array('slug' => $slug, 'returnsingle' => true));

        if (!$content && is_numeric($slug)) {
            // And otherwise try getting it by ID
            $content = $app['storage']->getContent($contenttype['slug'], array('id' => $slug, 'returnsingle' => true));
        }

        // No content, no page!
        if (!$content) {
            $app->abort(404, "Page $contenttypeslug/$slug not found.");
        }

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $content->template();

        // Fallback: If file is not OK, show an error page
        $filename = $app['paths']['themepath'] . "/" . $template;
        if (!file_exists($filename) || !is_readable($filename)) {
            $app->abort(404, "No template for '" . $content->getTitle() . "' defined. Tried to use '$template'.");
        }

        $app['editlink'] = path('editcontent', array('contenttypeslug' => $contenttypeslug, 'id' => $content->id));

        $body = $app['twig']->render($template, array(
            'record' => $content,
            $contenttype['singular_slug'] => $content // Make sure we can also access it as {{ page.title }} for pages, etc.
        ));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }


    function listing(Silex\Application $app, $contenttypeslug)
    {

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // First, get some content
        $page = (!empty($_GET['page']) ? $_GET['page'] : 1);
        $amount = (!empty($contenttype['listing_records']) ? $contenttype['listing_records'] : $app['config']['general']['listing_records']);
        $content = $app['storage']->getContent($contenttype['slug'], array('limit' => $amount, 'order' => 'datepublish desc', 'page' => $page));

        if (!$content) {
            $app->abort(404, "Content for '$contenttypeslug' not found.");
        }

        // Then, select which template to use, based on our 'cascading templates rules'
        if (!empty($contenttype['listing_template'])) {
            $template = $contenttype['listing_template'];
        } else {
            $filename = $app['paths']['themepath'] . "/" . $contenttype['slug'] . ".twig";
            if (file_exists($filename) && is_readable($filename)) {
                $template = $contenttype['slug'] . ".twig";
            } else {
                $template = $app['config']['general']['listing_template'];
            }
        }

        // Fallback: If file is not OK, show an error page
        $filename = $app['paths']['themepath'] . "/" . $template;
        if (!file_exists($filename) || !is_readable($filename)) {
            $app->abort(404, "No template for '$contenttypeslug' defined. Tried to use '$template'.");
        }

        // $app['editlink'] = path('editcontent', array('contenttypeslug' => $contenttypeslug, 'id' => $content->id));

        $body = $app['twig']->render($template, array(
            'records' => $content,
            $contenttype['slug'] => $content // Make sure we can also access it as {{ pages }} for pages, etc.
        ));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }

    public function feed(Silex\Application $app, $contenttypeslug)
    {
        // Clear the snippet queue
        $app['extensions']->clearSnippetQueue();
        // You *will* have to debug the feed yourself. The debug toolbar cannot
        // help you with that (yet?)
        $app['debug'] = false;

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        if (!isset($contenttype['rss']['enabled']) ||
            $contenttype['rss']['enabled'] != 'true'
        ) {
            $app->abort(404, "Feed for '$contenttypeslug' not found.");
        }

        // Better safe than sorry: abs to prevent negative values
        $amount = (int) abs((!empty($contenttype['rss']['feed_records']) ?
            $contenttype['rss']['feed_records'] :
            $app['config']['rss']['feed_records']));
        // How much to display in the description. Value of 0 means full body!
        $contentLength = (int) abs(
            (!empty($contenttype['rss']['content_length']) ?
                $contenttype['rss']['content_length'] :
                $app['config']['rss']['content_length'])
        );

        $content = $app['storage']->getContent(
            $contenttype['slug'],
            array('limit' => $amount, 'order' => 'datepublish desc')
        );

        if (!$content) {
            $app->abort(404, "Feed for '$contenttypeslug' not found.");
        }

        // Then, select which template to use, based on our
        // 'cascading templates rules'
        if (!empty($contenttype['feed_template'])) {
            $template = $contenttype['feed_template'];
        } else {
            $filename = $app['paths']['themepath'] . "/rss.twig";
            if (file_exists($filename) && is_readable($filename)) {
                $template = 'rss.twig';
            } else {
                $template = $app['config']['rss']['feed_template'];
            }
        }

        // Fallback: If file is not OK, show an error page
        $filename = $app['paths']['themepath'] . "/" . $template;
        if (!file_exists($filename) || !is_readable($filename)) {
            $app->abort(404, "No template for '$contenttypeslug' defined. Tried to use '$template'.");
        }

        $body = $app['twig']->render($template, array(
            'records' => $content,
            'content_length' => $contentLength,
            $contenttype['slug'] => $content,
        ));

        return new Response($body, 200,
            array('Content-Type' => 'application/rss+xml; charset=utf-8',
                'Cache-Control' => 's-maxage=3600, public',
            )
        );
    }


}
