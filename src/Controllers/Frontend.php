<?php

namespace Bolt\Controllers;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Library as Lib;
use Bolt\Pager;

/**
 * Standard Frontend actions
 *
 * This file acts as a grouping for the default front-end controllers.
 *
 * For overriding the default behavior here, please reference
 * http://docs.bolt.cm/templates-routes#routing or the routing.yml
 * file in your configuration.
 */
class Frontend
{
    /**
     * Perform contenttype-based permission check, aborting with a 403
     * Forbidden as appropriate.
     *
     * @param Silex\Application    $app     The application/container
     * @param \Bolt\Content|string $content The content to check
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

    /**
     * The default before filter for the controllers in this file.
     *
     * Refer to the routing.yml config file for overridding.
     *
     * @param Request           $request The Symfony Request
     * @param \Bolt\Application $app     The appliction/container
     * @return mixed
     */
    public static function before(Request $request, \Bolt\Application $app)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.frontend.before');

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['users']->getUsers()) {
            //!$app['storage']->getIntegrityChecker()->checkUserTableIntegrity() ||
            $app['session']->getFlashBag()->set('info', Lib::__('There are no users in the database. Please create the first user.'));

            return Lib::redirect('useredit', array('id' => ''));
        }

        $app['debugbar'] = true;
        $app['htmlsnippets'] = true;

        // If we are in maintenance mode and current user is not logged in, show maintenance notice.
        // @see /app/app.php, $app->error()
        if ($app['config']->get('general/maintenance_mode')) {
            if (!$app['users']->isAllowed('maintenance-mode')) {
                $template = $app['templatechooser']->maintenance();
                $body = $app['render']->render($template);

                return new Response($body, 503);
            }
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.frontend.before');
    }

    /**
     * Controller for the "Homepage" route. Usually the front page of the website.
     *
     * @param Silex\Application $app The application/container
     * @return mixed
     */
    public static function homepage(Silex\Application $app)
    {
        $content = $app['storage']->getContent($app['config']->get('general/homepage'));

        $template = $app['templatechooser']->homepage();

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

        return $app['render']->render($template);
    }

    /**
     * Controller for a single record page, like '/page/about/' or '/entry/lorum'.
     *
     * @param Silex\Application $app             The application/container
     * @param string            $contenttypeslug The content type slug
     * @param string            $slug            The content slug
     * @return mixed
     */
    public static function record(Silex\Application $app, $contenttypeslug, $slug)
    {
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $slug = Lib::makeSlug($slug, -1);

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
                Lib::simpleredirect($app['config']->get('general/branding/path') . '/');
            }
            $app->abort(404, "Page $contenttypeslug/$slug not found.");
        }

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $app['templatechooser']->record($content);

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

    /**
     * The controller for previewing a content from posted data.
     *
     * @param Request           $request         The Symfony Request
     * @param Silex\Application $app             The application/container
     * @param string            $contenttypeslug The content type slug
     * @return mixed
     */
    public static function preview(Request $request, Silex\Application $app, $contenttypeslug)
    {
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        // First, get the preview from Post.
        $content = $app['storage']->getContentObject($contenttypeslug);
        $content->setFromPost($request->request->all(), $contenttype);

        self::checkFrontendPermission($app, $content);

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $app['templatechooser']->record($content);

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

    /**
     * The listing page controller.
     *
     * @param Silex\Application $app             The application/container
     * @param string            $contenttypeslug The content type slug
     * @return mixed
     */
    public static function listing(Silex\Application $app, $contenttypeslug)
    {
        $contenttype = $app['storage']->getContentType($contenttypeslug);
        $pagerid = Pager::makeParameterId($contenttypeslug);
        /* @var $query \Symfony\Component\HttpFoundation\ParameterBag */
        $query = $app['request']->query;
        // First, get some content
        $page = $query->get($pagerid, $query->get('page', 1));
        $amount = (!empty($contenttype['listing_records']) ? $contenttype['listing_records'] : $app['config']->get('general/listing_records'));
        $order = (!empty($contenttype['sort']) ? $contenttype['sort'] : $app['config']->get('general/listing_sort'));
        $content = $app['storage']->getContent($contenttype['slug'], array('limit' => $amount, 'order' => $order, 'page' => $page, 'paging' => true));
        self::checkFrontendPermission($app, $contenttype['slug']);

        $template = $app['templatechooser']->listing($contenttype);

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

    /**
     * The taxonomy listing page controller.
     *
     * @param Silex\Application $app          The application/container
     * @param string            $taxonomytype The taxonomy type slug
     * @param string            $slug         The taxonomy slug
     * @return mixed
     */
    public static function taxonomy(Silex\Application $app, $taxonomytype, $slug)
    {
        // First, get some content
        $context = $taxonomytype . '_' . $slug;
        $pagerid = Pager::makeParameterId($context);
         /* @var $query \Symfony\Component\HttpFoundation\ParameterBag */
        $query = $app['request']->query;
        $page = $query->get($pagerid, $query->get('page', 1));
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

        $template = $app['templatechooser']->taxonomy($taxonomyslug);

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

    /**
     * The search result page controller.
     *
     * @param Request           $request The Symfony Request
     * @param Silex\Application $app     The application/container
     * @return mixed
     */
    public static function search(Request $request, Silex\Application $app)
    {
        $q = '';
        $context = __FUNCTION__;

        if ($request->query->has('q')) {
            $q = $request->get('q');
        } elseif ($request->query->has($context)) {
            $q = $request->get($context);
        }
        $q = Lib::cleanPostedData($q, false);

        $param = Pager::makeParameterId($context);
        /* @var $query \Symfony\Component\HttpFoundation\ParameterBag */
        $query = $request->query;
        $page = ($query) ? $query->get($param, $query->get('page', 1)) : 1;

        $config = $app['config'];
        $page_size = $config->get('general/search_results_records') ?: ($config->get('general/listing_records') ?: 10);

        $offset = ($page - 1) * $page_size;
        $limit = $page_size;

        // set-up filters from URL
        $filters = array();
        foreach ($request->query->all() as $key => $value) {
            if (strpos($key, '_') > 0) {
                list ($contenttypeslug, $field) = explode('_', $key, 2);
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
            'for' => $context,
            'count' => $result['no_of_results'],
            'totalpages' => ceil($result['no_of_results'] / $page_size),
            'current' => $page,
            'showing_from' => $offset + 1,
            'showing_to' => $offset + count($result['results']),
            'link' => '/search?q=' . rawurlencode($q) . '&page_search='
        );

        $app['storage']->setPager($context, $pager);

        $app['twig']->addGlobal('records', $result['results']);
        $app['twig']->addGlobal($context, $result['query']['use_q']);
        $app['twig']->addGlobal('searchresult', $result);

        $template = $app['templatechooser']->search();

        return $app['render']->render($template);
    }

    /**
     * Renders the specified template from the current theme in response to a request without
     * loading any content.
     *
     * @param Silex\Application $app      The application/container
     * @param string            $template The template name
     * @return mixed
     * @throws \Exception
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
