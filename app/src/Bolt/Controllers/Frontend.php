<?php

namespace Bolt\Controllers;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Frontend
{

    function before(Request $request, Application $app)
    {

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['storage']->checkUserTableIntegrity() || !$app['users']->getUsers()) {
            $app['session']->setFlash('info', "There are no users in the database. Please create the first user.");
            return redirect('useredit', array('id' => ""));
        }

        $app['debugbar'] = true;

    }

    function homepage(Application $app)
    {
        if (!empty($app['config']['general']['homepage_template'])) {
            $template = $app['config']['general']['homepage_template'];
            $content = $app['storage']->getContent($app['config']['general']['homepage']);
            $twigvars = array(
                'record' => $content,
                $content->contenttype['singular_slug'] => $content
            );
            $chosen = 'homepage config';
        } else {
            $template = 'index.twig';
            $twigvars = array();
            $chosen = 'homepage fallback';
        }

        $app['log']->setValue('templatechosen', $app['config']['general']['theme'] . "/$template ($chosen)");


        $body = $app['twig']->render($template, $twigvars);

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }

    function record(Application $app, $contenttypeslug, $slug)
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
            $error = sprintf("No template for '%s' defined. Tried to use '%s/%s'.",
                $content->getTitle(),
                basename($app['config']['general']['theme']),
                $template);
            $app['log']->setValue('templateerror', $error);
            $app->abort(404, $error);
        }

        $app['editlink'] = path('editcontent', array('contenttypeslug' => $contenttypeslug, 'id' => $content->id));

        $body = $app['twig']->render($template, array(
            'record' => $content,
            $contenttype['singular_slug'] => $content // Make sure we can also access it as {{ page.title }} for pages, etc.
        ));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }


    function listing(Application $app, $contenttypeslug)
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
            $chosen = "contenttype";
        } else {
            $filename = $app['paths']['themepath'] . "/" . $contenttype['slug'] . ".twig";
            if (file_exists($filename) && is_readable($filename)) {
                $template = $contenttype['slug'] . ".twig";
                $chosen = "slug";
            } else {
                $template = $app['config']['general']['listing_template'];
                $chosen = "config";

            }
        }

        $app['log']->setValue('templatechosen', $app['config']['general']['theme'] . "/$template ($chosen)");


        // Fallback: If file is not OK, show an error page
        $filename = $app['paths']['themepath'] . "/" . $template;
        if (!file_exists($filename) || !is_readable($filename)) {
            $error = sprintf("No template for '%s'-listing defined. Tried to use '%s/%s'.",
                $contenttypeslug,
                basename($app['config']['general']['theme']),
                $template);
            $app['log']->setValue('templateerror', $error);
            $app->abort(404, $error);
        }

        // $app['editlink'] = path('editcontent', array('contenttypeslug' => $contenttypeslug, 'id' => $content->id));

        $body = $app['twig']->render($template, array(
            'records' => $content,
            $contenttype['slug'] => $content // Make sure we can also access it as {{ pages }} for pages, etc.
        ));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

    }

    public function feed(Application $app, $contenttypeslug)
    {
        // Clear the snippet queue
        $app['extensions']->clearSnippetQueue();
        $app['debugbar'] = false;

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
        if (!empty($contenttype['rss']['feed_template'])) {
            $template = $contenttype['rss']['feed_template'];
        } else if (!empty($app['config']['rss']['feed_template'])) {
            $template = $app['config']['rss']['feed_template'];
        } else {
            $template = 'rss.twig';
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

    public function search(Request $request, Application $app)
    {
        //$searchterms =  safeString($request->get('search'));
        $template = $app['config']['general']['search_results_template'];

        // @todo Preparation for stage 2
        //$resultsPP = (int) $app['config']['general']['search_results_records'];
        //$page = (!empty($_GET['page']) ? $_GET['page'] : 1);

        //$parameters = array('limit' => $resultsPP, 'page' => $page, 'filter' => $request->get('search'));
        $parameters = array('filter' => $request->get('search'));

        //$content = $searchterms . " and " . $resultsPP;
        $content = $app['storage']->searchAllContentTypes($parameters);
        //$content = $app['storage']->searchContentType('entries', $searchterms, $parameters);

        $body = $app['twig']->render($template, array(
            'records' => $content
        ));

        return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));
    }


}
