<?php

namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Frontend implements ControllerProviderInterface
{
    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];

        $ctr->match("/", array($this, 'homepage'))
            ->before(array($this, 'before'))
            ->bind('homepage')
        ;

        $ctr->match('/search', array($this, 'search'))
            ->before(array($this, 'before'))
        ;

        $ctr->match('/sitemap', array($this, 'sitemap'))
            ->before(array($this, 'before'))
        ;

        $ctr->match('/sitemap.xml', array($this, 'sitemapXml'))
            ->before(array($this, 'before'))
        ;

        $ctr->match('/preview/{contenttypeslug}', array($this, 'preview'))
            ->before(array($this, 'before'))
            ->assert('contenttypeslug', $app['storage']->getContentTypeAssert(true))
            ->bind('preview')
        ;

        $ctr->match('/{contenttypeslug}/{slug}', array($this, 'record'))
            ->before(array($this, 'before'))
            ->assert('contenttypeslug', $app['storage']->getContentTypeAssert(true))
            ->bind('contentlink')
        ;

        $ctr->match('/{taxonomytype}/{slug}', array($this, 'taxonomy'))
            ->before(array($this, 'before'))
            ->assert('taxonomytype', $app['storage']->getTaxonomyTypeAssert(true))
            ->bind('taxonomylink')
        ;

        $ctr->match('/{contenttypeslug}', array($this, 'listing'))
            ->before(array($this, 'before'))
            ->assert('contenttypeslug', $app['storage']->getContentTypeAssert())
        ;



        return $ctr;
    }

    function before(Request $request, \Bolt\Application $app)
    {

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['storage']->getIntegrityChecker()->checkUserTableIntegrity() || !$app['users']->getUsers()) {
            $app['session']->getFlashBag()->set('info', __("There are no users in the database. Please create the first user."));
            return redirect('useredit', array('id' => ""));
        }

        $app['debugbar']     = true;
        $app['htmlsnippets'] = true;

    }

    function homepage(Silex\Application $app)
    {
        if (!empty($app['config']['general']['homepage_template'])) {
            $template = $app['config']['general']['homepage_template'];
            $content = $app['storage']->getContent($app['config']['general']['homepage']);

            if (is_array($content)) {
                $first = current($content);
                $app['twig']->addGlobal('records', $content);
                $app['twig']->addGlobal($first->contenttype['slug'], $content);
            } else if (!empty($content)) {
                $app['twig']->addGlobal('record', $content);
                $app['twig']->addGlobal($content->contenttype['singular_slug'], $content);
            }

            $chosen = 'homepage config';
        } else {
            $template = 'index.twig';
            $chosen = 'homepage fallback';
        }

        $app['log']->setValue('templatechosen', $app['config']['general']['theme'] . "/$template ($chosen)");

        return $app['twig']->render($template);
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
            $error = sprintf("No template for '%s' defined. Tried to use '%s/%s'.",
                $content->getTitle(),
                basename($app['config']['general']['theme']),
                $template);
            $app['log']->setValue('templateerror', $error);
            $app->abort(404, $error);
        }

        // Setting the canonical path and the editlink.
        $app['canonicalpath'] = $content->link();
        $app['paths'] = getPaths($app);
        $app['editlink'] = path('editcontent', array('contenttypeslug' => $contenttypeslug, 'id' => $content->id));

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $app['twig']->addGlobal('record', $content);
        $app['twig']->addGlobal($contenttype['singular_slug'], $content);

        // Render the template and return.
        return $app['twig']->render($template);

    }




    function preview(Request $request, Silex\Application $app, $contenttypeslug)
    {

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // First, get the preview from Post.
        $content = $app['storage']->getContentObject($contenttypeslug);
        $content->setFromPost($request->request->all(), $contenttype);

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

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $app['twig']->addGlobal('record', $content);
        $app['twig']->addGlobal($contenttype['singular_slug'], $content);

        return $app['twig']->render($template);

    }




    function listing(Silex\Application $app, $contenttypeslug)
    {

        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // First, get some content
        $page = $app['request']->query->get('page', 1);
        $amount = (!empty($contenttype['listing_records']) ? $contenttype['listing_records'] : $app['config']['general']['listing_records']);
        $order = (!empty($contenttype['sort']) ? $contenttype['sort'] : $app['config']['general']['listing_sort']);
        $content = $app['storage']->getContent($contenttype['slug'], array('limit' => $amount, 'order' => $order, 'page' => $page));

        // We do _not_ abort when there's no content. Instead, we handle this in the template:
        // {% for record in records %} .. {% else %} no records! {% endif %}
        // if (!$content) {
        //     $app->abort(404, "Content for '$contenttypeslug' not found.");
        // }

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

        // Make sure we can also access it as {{ pages }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $app['twig']->addGlobal('records', $content);
        $app['twig']->addGlobal($contenttype['slug'], $content);

        return $app['twig']->render($template);

    }


    function taxonomy(Silex\Application $app, $taxonomytype, $slug)
    {

        // First, get some content
        $page = $app['request']->query->get('page', 1);
        $amount = $app['config']['general']['listing_records'];
        $order = $app['config']['general']['listing_sort'];
        $content = $app['storage']->getContentByTaxonomy($taxonomytype, $slug, array('limit' => $amount, 'order' => $order, 'page' => $page));

        $taxonomytype = $app['storage']->getTaxonomyType($taxonomytype);

        // No taxonomytype, no possible content..
        if (empty($taxonomytype)) {
            return false;
        } else {
            $taxonomyslug = $taxonomytype['slug'];
        }


        if (!$content) {
            $app->abort(404, "Content for '$taxonomyslug/$slug' not found.");
        }

        $chosen = "taxonomy";

        // Set the template based on the (optional) setting in taxonomy.yml, or fall back to default listing template
        if (isset($app['config']['taxonomy'][$taxonomyslug]['listing_template'])) {
            $template = $app['config']['taxonomy'][$taxonomyslug]['listing_template'];
        } else {
            $template = $app['config']['general']['listing_template'];
        }

        $app['log']->setValue('templatechosen', $app['config']['general']['theme'] . "/$template ($chosen)");


        // Fallback: If file is not OK, show an error page
        $filename = $app['paths']['themepath'] . "/" . $template;
        if (!file_exists($filename) || !is_readable($filename)) {
            $error = sprintf("No template for '%s'-listing defined. Tried to use '%s/%s'.",
                $taxonomyslug,
                basename($app['config']['general']['theme']),
                $template);
            $app['log']->setValue('templateerror', $error);
            $app->abort(404, $error);
        }

        // $app['editlink'] = path('editcontent', array('contenttypeslug' => $contenttypeslug, 'id' => $content->id));

        $app['twig']->addGlobal('records', $content);
        $app['twig']->addGlobal('slug', $slug);
        $app['twig']->addGlobal('taxonomy', $app['config']['taxonomy'][$taxonomyslug]);
        $app['twig']->addGlobal('taxonomytype', $taxonomyslug);

        return $app['twig']->render($template);

    }

    public function search(Request $request, Silex\Application $app)
    {
        //$searchterms =  safeString($request->get('search'));
        $template = (!empty($app['config']['general']['search_results_template'])) ? $app['config']['general']['search_results_template'] : $app['config']['general']['listing_template'] ;

        // @todo Preparation for stage 2
        //$resultsPP = (int) $app['config']['general']['search_results_records'];
        //$page = (!empty($_GET['page']) ? $_GET['page'] : 1);

        //$parameters = array('limit' => $resultsPP, 'page' => $page, 'filter' => $request->get('search'));

        $search = $request->get('search');
        $parameters = array('filter' => $search);

        //$content = $searchterms . " and " . $resultsPP;
        $content = $app['storage']->searchAllContentTypes($parameters);
        //$content = $app['storage']->searchContentType('entries', $searchterms, $parameters);

        $app['twig']->addGlobal('records', $content);
        $app['twig']->addGlobal('search', $search);

        return $app['twig']->render($template);

    }

    public function sitemap(Silex\Application $app, $xml = false)
    {
        if($xml){
            $app['extensions']->clearSnippetQueue();
            $app['extensions']->disableJquery();
            $app['debugbar'] = false;
        }

        $links = array(array('link' => $app['paths']['root'], 'title' => $app['config']['general']['sitename']));
        foreach( $app['config']['contenttypes'] as $contenttype ) {
            if (isset($contenttype['listing_template'])) {
                $links[] = array( 'link' => $app['paths']['root'].$contenttype['slug'], 'title' => $contenttype['name'] );
            }
            if (isset($contenttype['record_template'])) {
                $content = $app['storage']->getContent($contenttype['slug']);
                foreach( $content as $entry ) {
                    $links[] = array('link' => $entry->link(), 'title' => $entry->getTitle(),
                        'lastmod' => date( \DateTime::W3C, strtotime($entry->get('datechanged'))));
                }
            }
        }
        if ($xml) {
            $template = $app['config']['general']['sitemap_xml_template'];
        } else {
            $template = $app['config']['general']['sitemap_template'];
        }

        $body = $app['twig']->render($template, array(
            'entries' => $links
        ));
        $headers = array();
        if ($xml) {
            $headers['Content-Type'] = 'application/xml; charset=utf-8';
        }
        $headers['Cache-Control'] = 's-maxage=3600, public';

        return new Response($body, 200, $headers);

    }

    public function sitemapXml(Silex\Application $app)
    {
        return $this->sitemap($app,true);
    }


}
