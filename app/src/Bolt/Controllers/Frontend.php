<?php

namespace Bolt\Controllers;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Standard Frontend actions
 *
 * Strictly speaking this is no longer a controller, but logically
 * it still is.
 */
class Frontend
{
    /**
     * Perform contenttype-based permission check, aborting with a 403
     * Forbidden as appropriate.
     */
    private static function checkFrontendPermission(Silex\Application $app, $content)
    {
        if ($app['config']->get('general/frontend_permission_checks')) {
            if ($content instanceof \Bolt\Content) {
                $contenttypeslug = $content->contenttype['slug'];
                $contentid = $content['id'];
            } else {
                $contenttypeslug = (string) $content;
                $contentid = null;
            }
            if (!$app['users']->isAllowed('frontend', $contenttypeslug, $contentid)) {
                $app->abort(403, 'Not allowed.');
            }
        }
    }

    public static function before(Request $request, \Bolt\Application $app)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.frontend.before');

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['users']->getUsers()) {
            //!$app['storage']->getIntegrityChecker()->checkUserTableIntegrity() ||
            $app['session']->getFlashBag()->set('info', __('There are no users in the database. Please create the first user.'));

            return redirect('useredit', array('id' => ''));
        }

        $app['debugbar'] = true;
        $app['htmlsnippets'] = true;

        // If we are in maintenance mode and current user is not logged in, show maintenance notice.
        // @see /app/app.php, $app->error()
        if ($app['config']->get('general/maintenance_mode')) {
            if (!$app['users']->isAllowed('maintenance-mode')) {
                $template = $app['config']->get('general/maintenance_template');
                $body = $app['render']->render($template);

                return new Response($body, 503);
            }
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.frontend.before');
    }

    public static function homepage(Silex\Application $app)
    {
        if ($app['config']->get('general/homepage_template')) {
            $template = $app['config']->get('general/homepage_template');
            $content = $app['storage']->getContent($app['config']->get('general/homepage'));

            // Set the 'editlink', if $content contains a valid record.
            if (!empty($content->contenttype['slug'])) {
                $app['editlink'] = path('editcontent', array('contenttypeslug' => $content->contenttype['slug'], 'id' => $content->id));
                $app['edittitle'] = $content->getTitle();
            }

            if (is_array($content)) {
                $first = $record = current($content);
                $app['twig']->addGlobal('records', $content);
                $app['twig']->addGlobal($first->contenttype['slug'], $content);
            } elseif (!empty($content)) {
                $record = $content;
                $app['twig']->addGlobal('record', $content);
                $app['twig']->addGlobal($content->contenttype['singular_slug'], $content);
            }

            if (!empty($record)) {
                self::checkFrontendPermission($app, $record);
            }

            $chosen = 'homepage config';
        } else {
            $template = 'index.twig';
            $chosen = 'homepage fallback';
        }

        $app['log']->setValue('templatechosen', $app['config']->get('general/theme') . "/$template ($chosen)");

        return $app['render']->render($template);
    }

    public static function record(Silex\Application $app, $contenttypeslug, $slug)
    {
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $slug = makeSlug($slug, -1);

        // First, try to get it by slug.
        $content = $app['storage']->getContent($contenttype['slug'], array('slug' => $slug, 'returnsingle' => true));

        if (!$content && is_numeric($slug)) {
            // And otherwise try getting it by ID
            $content = $app['storage']->getContent($contenttype['slug'], array('id' => $slug, 'returnsingle' => true));
        }

        self::checkFrontendPermission($app, $content);

        // No content, no page!
        if (!$content) {
            // There's one special edge-case we check for: if the request is for the backend, without trailing
            // slash and it is intercepted by custom routing, we forward the client to that location.
            if ($slug == trim($app['config']->get('general/branding/path'), '/')) {
                simpleredirect($app['config']->get('general/branding/path') . '/');
            }
            $app->abort(404, "Page $contenttypeslug/$slug not found.");
        }

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $content->template();

        // Fallback: If file is not OK, show an error page
        $filename = $app['paths']['themepath'] . "/" . $template;
        if (!file_exists($filename) || !is_readable($filename)) {
            $error = sprintf(
                "No template for '%s' defined. Tried to use '%s/%s'.",
                $content->getTitle(),
                basename($app['config']->get('general/theme')),
                $template
            );
            $app['log']->setValue('templateerror', $error);
            $app->abort(404, $error);
        }

        // Setting the canonical path and the editlink.
        $app['canonicalpath'] = $content->link();
        $app['paths'] = $app['resources']->getPaths();
        $app['editlink'] = path('editcontent', array('contenttypeslug' => $contenttype['slug'], 'id' => $content->id));
        $app['edittitle'] = $content->getTitle();

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $app['twig']->addGlobal('record', $content);
        $app['twig']->addGlobal($contenttype['singular_slug'], $content);

        // Render the template and return.
        return $app['render']->render($template);
    }


    public static function preview(Request $request, Silex\Application $app, $contenttypeslug)
    {
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // First, get the preview from Post.
        $content = $app['storage']->getContentObject($contenttypeslug);
        $content->setFromPost($request->request->all(), $contenttype);

        self::checkFrontendPermission($app, $content);

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $content->template();

        // Fallback: If file is not OK, show an error page
        $filename = $app['paths']['themepath'] . "/" . $template;
        if (!file_exists($filename) || !is_readable($filename)) {
            $error = sprintf(
                "No template for '%s' defined. Tried to use '%s/%s'.",
                $content->getTitle(),
                basename($app['config']->get('general/theme')),
                $template
            );
            $app['log']->setValue('templateerror', $error);
            $app->abort(404, $error);
        }

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $app['twig']->addGlobal('record', $content);
        $app['twig']->addGlobal($contenttype['singular_slug'], $content);

        return $app['render']->render($template);
    }

    public static function listing(Silex\Application $app, $contenttypeslug)
    {
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // First, get some content
        $page = $app['request']->query->get('page', 1);
        $amount = (!empty($contenttype['listing_records']) ? $contenttype['listing_records'] : $app['config']->get('general/listing_records'));
        $order = (!empty($contenttype['sort']) ? $contenttype['sort'] : $app['config']->get('general/listing_sort'));
        $content = $app['storage']->getContent($contenttype['slug'], array('limit' => $amount, 'order' => $order, 'page' => $page, 'paging' => true));
        self::checkFrontendPermission($app, $contenttype['slug']);

        // We do _not_ abort when there's no content. Instead, we handle this in the template:
        // {% for record in records %} .. {% else %} no records! {% endif %}
        // if (!$content) {
        //     $app->abort(404, "Content for '$contenttypeslug' not found.");
        // }

        // Then, select which template to use, based on our 'cascading templates rules'
        if (!empty($contenttype['listing_template'])) {
            $template = $contenttype['listing_template'];
            $chosen = 'contenttype';
        } else {
            $filename = $app['paths']['themepath'] . '/' . $contenttype['slug'] . '.twig';
            if (file_exists($filename) && is_readable($filename)) {
                $template = $contenttype['slug'] . '.twig';
                $chosen = 'slug';
            } else {
                $template = $app['config']->get('general/listing_template');
                $chosen = 'config';

            }
        }

        $app['log']->setValue('templatechosen', $app['config']->get('general/theme') . "/$template ($chosen)");

        // Fallback: If file is not OK, show an error page
        $filename = $app['paths']['themepath'] . "/" . $template;
        if (!file_exists($filename) || !is_readable($filename)) {
            $error = sprintf(
                "No template for '%s'-listing defined. Tried to use '%s/%s'.",
                $contenttypeslug,
                basename($app['config']->get('general/theme')),
                $template
            );
            $app['log']->setValue('templateerror', $error);
            $app->abort(404, $error);
        }

        // Make sure we can also access it as {{ pages }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $app['twig']->addGlobal('records', $content);
        $app['twig']->addGlobal($contenttype['slug'], $content);
        $app['twig']->addGlobal('contenttype', $contenttype['name']);

        return $app['render']->render($template);
    }

    public static function taxonomy(Silex\Application $app, $taxonomytype, $slug)
    {
        // First, get some content
        $page = $app['request']->query->get('page', 1);
        $amount = $app['config']->get('general/listing_records');
        $order = $app['config']->get('general/listing_sort');
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

        $chosen = 'taxonomy';

        // Set the template based on the (optional) setting in taxonomy.yml, or fall back to default listing template
        if ($app['config']->get('taxonomy/' . $taxonomyslug . '/listing_template')) {
            $template = $app['config']->get('taxonomy/' . $taxonomyslug . '/listing_template');
        } else {
            $template = $app['config']->get('general/listing_template');
        }

        $app['log']->setValue('templatechosen', $app['config']->get('general/theme') . "/$template ($chosen)");

        // Fallback: If file is not OK, show an error page
        $filename = $app['paths']['themepath'] . "/" . $template;
        if (!file_exists($filename) || !is_readable($filename)) {
            $error = sprintf(
                "No template for '%s'-listing defined. Tried to use '%s/%s'.",
                $taxonomyslug,
                basename($app['config']->get('general/theme')),
                $template
            );
            $app['log']->setValue('templateerror', $error);
            $app->abort(404, $error);
        }

        $name = $slug;
        // Look in taxonomies in 'content', to get a display value for '$slug', perhaps.
        foreach ($content as $record) {
            $flat = \utilphp\util::array_flatten($record->taxonomy);
            $key = $app['paths']['root'] . $taxonomytype['slug'] . '/' . $slug;
            if (isset($flat[$key])) {
                $name = $flat[$key];
            }
            $key = $app['paths']['root'] . $taxonomytype['singular_slug'] . '/' . $slug;
            if (isset($flat[$key])) {
                $name = $flat[$key];
            }
        }

        $app['twig']->addGlobal('records', $content);
        $app['twig']->addGlobal('slug', $name);
        $app['twig']->addGlobal('taxonomy', $app['config']->get('taxonomy/' . $taxonomyslug));
        $app['twig']->addGlobal('taxonomytype', $taxonomyslug);

        return $app['render']->render($template);
    }

    public static function search(Request $request, Silex\Application $app)
    {
        $q = '';
        if ($request->query->has('q')) {
            $q = $request->get('q');
        } elseif ($request->query->has('search')) {
            $q = $request->get('search');
        }
        $q = cleanPostedData($q, false);

        // Make paging work
        $page_size = 10;
        $page = 1;
        if ($request->query->has('page')) {
            $page = intval($request->get('page'));
        }
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        // set-up filters from URL
        $filters = array();
        foreach ($request->query->all() as $key => $value) {
            if (strpos($key, '_') > 0) {
                list($contenttypeslug, $field) = explode('_', $key, 2);
                if (isset($filters[$contenttypeslug])) {
                    $filters[$contenttypeslug][$field] = $value;
                } else {
                    $contenttype = $app['storage']->getContentType($contenttypeslug);
                    if (is_array($contenttype)) {
                        $filters[$contenttypeslug] = array(
                            $field => $value
                        );
                    }
                }
            }
        }
        if (count($filters) == 0) {
            $filters = null;
        }

        $result = $app['storage']->searchContent($q, null, $filters, $limit, $offset);

        $pager = array(
            'for' => 'search',
            'count' => $result['no_of_results'],
            'totalpages' => ceil($result['no_of_results'] / $page_size),
            'current' => $page,
            'showing_from' => $offset + 1,
            'showing_to' => $offset + count($result['results']),
            'link' => '/search?q=' . rawurlencode($q) . '&page_search='
        );

        $app['storage']->setPager('search', $pager);

        $app['twig']->addGlobal('records', $result['results']);
        $app['twig']->addGlobal('search', $result['query']['use_q']);
        $app['twig']->addGlobal('searchresult', $result);

        $template = $app['config']->get('general/search_results_template', $app['config']->get('general/listing_template'));

        return $app['render']->render($template);
    }

    /**
     * Renders the specified template from the current theme in response to a request without
     * loading any content.
     */
    public static function template(Silex\Application $app, $template)
    {
        // Add the template extension if it is missing
        if (!preg_match('/\\.twig$/i', $template)) {
            $template .= '.twig';
        }

        $themePath    = realpath($app['paths']['themepath'] . '/');
        $templatePath = realpath($app['paths']['themepath'] . '/' . $template);

        // Verify that the resulting template path is located in the theme directory
        if ($themePath !== substr($templatePath, 0, strlen($themePath))) {
            throw new \Exception("Invalid template: $template");
        }

        return $app['render']->render(substr($templatePath, strlen($themePath)));
    }
}
