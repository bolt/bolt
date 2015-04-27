<?php

namespace Bolt\Controller;

use Bolt\Application;
use Bolt\Content;
use Bolt\Extensions\Snippets\Location as SnippetLocation;
use Bolt\Helpers\Input;
use Bolt\Library as Lib;
use Bolt\Pager;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use utilphp\util;

/**
 * Standard Frontend actions.
 *
 * This file acts as a grouping for the default front-end controllers.
 *
 * For overriding the default behavior here, please reference
 * http://docs.bolt.cm/templates-routes#routing or the routing.yml
 * file in your configuration.
 */
class Frontend extends Base
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/', 'controller.routing:actionHomepage')
            ->bind('homepage');

        $c->get('/{contenttypeslug}', 'controller.routing:actionListing')
            ->bind('listing');

        $c->get('/preview/{contenttypeslug}', 'controller.routing:actionPreview')
            ->bind('preview');

        $c->get('/{contenttypeslug}/{slug}', 'controller.routing:actionRecord')
            ->bind('record');

        $c->match('/search', 'controller.routing:actionSearch')
            ->bind('search');

        $c->get('/{taxonomytype}/{slug}', 'controller.routing:actionTaxonomy')
            ->bind('taxonomy');

//         $c->get('/', 'controller.routing:actionTemplate')
//             ->bind('template');
    }

    /**
     * The default before filter for the controllers in this file.
     *
     * Refer to the routing.yml config file for overridding.
     *
     * @param Request     $request The Symfony Request
     * @param Application $app     The application/container
     *
     * @return null|Response|RedirectResponse
     */
    public function before(Request $request, Application $app)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.frontend.before');

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$app['users']->getUsers()) {
            $this->addFlash('info', Trans::__('There are no users in the database. Please create the first user.'));

            return $this->redirectToRoute('useredit', array('id' => ''));
        }

        $app['debugbar'] = true;
        $app['htmlsnippets'] = true;

        // If we are in maintenance mode and current user is not logged in, show maintenance notice.
        if ($this->getOption('general/maintenance_mode')) {
            if (!$this->isAllowed('maintenance-mode')) {
                $template = $this->templateChooser()->maintenance();
                $body = $this->render($template);

                return new Response($body, Response::HTTP_SERVICE_UNAVAILABLE);
            }
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.frontend.before');

        return null;
    }

    /**
     * Controller for the "Homepage" route. Usually the front page of the website.
     *
     * @return \Twig_Markup
     */
    public function homepage()
    {
        $content = $this->getContent($this->getOption('general/homepage'));

        $template = $this->templateChooser()->homepage();

        if (is_array($content)) {
            $first = current($content);
            $globals = array(
                'records'                   => $content,
                $first->contenttype['slug'] => $content
            );
        } elseif (!empty($content)) {
            $globals = array(
                'record'                               => $content,
                $content->contenttype['singular_slug'] => $content
            );
        }

        return $this->render($template, 'homepage', array(), $globals);
    }

    /**
     * Controller for a single record page, like '/page/about/' or '/entry/lorum'.
     *
     * @param Request $request         The request
     * @param string  $contenttypeslug The content type slug
     * @param string  $slug            The content slug
     *
     * @return \Twig_Markup
     */
    public function record($request, $contenttypeslug, $slug = '')
    {
        $contenttype = $this->getContentType($contenttypeslug);

        // If the contenttype is 'viewless', don't show the record page.
        if (isset($contenttype['viewless']) && $contenttype['viewless'] === true) {
            return $this->abort(Response::HTTP_NOT_FOUND, "Page $contenttypeslug/$slug not found.");
        }

        // Perhaps we don't have a slug. Let's see if we can pick up the 'id', instead.
        if (empty($slug)) {
            $slug = $request->get('id');
        }

        $slug = $this->app['slugify']->slugify($slug);

        // First, try to get it by slug.
        $content = $this->getContent($contenttype['slug'], array('slug' => $slug, 'returnsingle' => true, 'log_not_found' => !is_numeric($slug)));

        if (!$content && is_numeric($slug)) {
            // And otherwise try getting it by ID
            $content = $this->getContent($contenttype['slug'], array('id' => $slug, 'returnsingle' => true));
        }

        // No content, no page!
        if (!$content) {
            return $this->abort(Response::HTTP_NOT_FOUND, "Page $contenttypeslug/$slug not found.");
        }

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $this->templateChooser()->record($content);

        $paths = $this->app['resources']->getPaths();

        // Setting the canonical URL.
        if ($content->isHome() && ($template == $this->getOption('general/homepage_template'))) {
            $this->app['resources']->setUrl('canonicalurl', $paths['rooturl']);
        } else {
            $url = $paths['canonical'] . $content->link();
            $this->app['resources']->setUrl('canonicalurl', $url);
        }

        // Setting the editlink
        $this->app['editlink'] = $this->generateUrl('editcontent', array('contenttypeslug' => $contenttype['slug'], 'id' => $content->id));
        $this->app['edittitle'] = $content->getTitle();

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = array(
            'record'                      => $content,
            $contenttype['singular_slug'] => $content
        );

        // Render the template and return.
        return $this->render($template, $content->getTitle(), array(), $globals);
    }

    /**
     * The controller for previewing a content from posted data.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return \Twig_Markup
     */
    public function preview(Request $request, $contenttypeslug)
    {
        $contenttype = $this->getContentType($contenttypeslug);

        // First, get the preview from Post.
        $content = $this->app['storage']->getContentObject($contenttypeslug);
        $content->setFromPost($request->request->all(), $contenttype);

        $liveEditor = $request->get('_live-editor-preview');
        if (!empty($liveEditor)) {
            $jsFile = $this->app['resources']->getUrl('app') . 'view/js/ckeditor/ckeditor.js';
            $cssFile = $this->app['resources']->getUrl('app') . 'view/css/liveeditor.css';
            $this->getExtensions()->insertSnippet(SnippetLocation::BEFORE_HEAD_JS, '<script>window.boltIsEditing = true;</script>');
            $this->getExtensions()->addJavascript($jsFile, array('late' => false, 'priority' => 1));
            $this->getExtensions()->addCss($cssFile, false, 5);
        }

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $this->templateChooser()->record($content);

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = array(
            'record'                      => $content,
            $contenttype['singular_slug'] => $content
        );

        // Chrome (unlike Firefox and Internet Explorer) has a feature that helps prevent
        // XSS attacks for uncareful people. It blocks embeds, links and src's that have
        // a URL that's also in the request. In Bolt we wish to enable this type of embeds,
        // because otherwise Youtube, Vimeo and Google Maps embeds will simply not show,
        // causing confusion for the editor, because they don't know what's happening.
        // Is this a security concern, you may ask? I believe it cannot be exploited:
        //   - Disabled, the behaviour on Chrome matches Firefox and IE.
        //   - The user must be logged in to see the 'preview' page at all.
        //   - Our CSRF-token ensures that the user will only see their own posted preview.
        // @see: http://security.stackexchange.com/questions/53474/is-chrome-completely-secure-against-reflected-xss
        header("X-XSS-Protection: 0");

        return $this->render($template, array(), $globals);
    }

    /**
     * The listing page controller.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return \Twig_Markup
     */
    public function listing($request, $contenttypeslug)
    {
        $contenttype = $this->getContentType($request, $contenttypeslug);

        // If the contenttype is 'viewless', don't show the record page.
        if (isset($contenttype['viewless']) && $contenttype['viewless'] === true) {
            return $this->abort(Response::HTTP_NOT_FOUND, "Page $contenttypeslug not found.");
        }

        $pagerid = Pager::makeParameterId($contenttypeslug);
        /* @var $query \Symfony\Component\HttpFoundation\ParameterBag */
        $query = $request->query;
        // First, get some content
        $page = $query->get($pagerid, $query->get('page', 1));
        $amount = (!empty($contenttype['listing_records']) ? $contenttype['listing_records'] : $this->getOption('general/listing_records'));
        $order = (!empty($contenttype['sort']) ? $contenttype['sort'] : $this->getOption('general/listing_sort'));
        $content = $this->getContent($contenttype['slug'], array('limit' => $amount, 'order' => $order, 'page' => $page, 'paging' => true));

        $template = $this->templateChooser()->listing($contenttype);

        // Make sure we can also access it as {{ pages }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = array(
            'records'            => $content,
            $contenttype['slug'] => $content,
            'contenttype'        => $contenttype['name']
        );

        return $this->render($template, $contenttypeslug, array(), $globals);
    }

    /**
     * The taxonomy listing page controller.
     *
     * @param Request $request      The Symfony Request
     * @param string  $taxonomytype The taxonomy type slug
     * @param string  $slug         The taxonomy slug
     *
     * @return \Twig_Markup
     */
    public function taxonomy($request, $taxonomytype, $slug)
    {
        $taxonomy = $this->app['storage']->getTaxonomyType($taxonomytype);
        // No taxonomytype, no possible content.
        if (empty($taxonomy)) {
            return false;
        } else {
            $taxonomyslug = $taxonomy['slug'];
        }
        // First, get some content
        $context = $taxonomy['singular_slug'] . '_' . $slug;
        $pagerid = Pager::makeParameterId($context);
         /* @var $query \Symfony\Component\HttpFoundation\ParameterBag */
        $query = $request->query;
        $page = $query->get($pagerid, $query->get('page', 1));
        $amount = $this->getOption('general/listing_records');
        $order = $this->getOption('general/listing_sort');
        $content = $this->app['storage']->getContentByTaxonomy($taxonomytype, $slug, array('limit' => $amount, 'order' => $order, 'page' => $page));

        // See https://github.com/bolt/bolt/pull/2310
        if (
                ($taxonomy['behaves_like'] === 'tags' && !$content) ||
                (
                    in_array($taxonomy['behaves_like'], array('categories', 'grouping')) &&
                    !in_array($slug, isset($taxonomy['options']) ? array_keys($taxonomy['options']) : array())
                )
            ) {
            return $this->abort(Response::HTTP_NOT_FOUND, "No slug '$slug' in taxonomy '$taxonomyslug'");
        }

        $template = $this->templateChooser()->taxonomy($taxonomyslug);

        $name = $slug;
        // Look in taxonomies in 'content', to get a display value for '$slug', perhaps.
        foreach ($content as $record) {
            $flat = util::array_flatten($record->taxonomy);
            $key = $this->app['resources']->getPath('root' . $taxonomy['slug'] . '/' . $slug);
            if (isset($flat[$key])) {
                $name = $flat[$key];
            }
            $key = $this->app['resources']->getPath('root' . $taxonomy['singular_slug'] . '/' . $slug);
            if (isset($flat[$key])) {
                $name = $flat[$key];
            }
        }

        $globals = array(
            'records'     => $content,
            'slug'        => $name,
            'taxonomy'    => $this->getOption('taxonomy/' . $taxonomyslug),
            'taxonomytype' => $taxonomyslug
        );

        return $this->render($template, $taxonomyslug, array(), $globals);
    }

    /**
     * The search result page controller.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Twig_Markup
     */
    public function search(Request $request)
    {
        $q = '';
        $context = __FUNCTION__;

        if ($request->query->has('q')) {
            $q = $request->get('q');
        } elseif ($request->query->has($context)) {
            $q = $request->get($context);
        }
        $q = Input::cleanPostedData($q, false);

        $param = Pager::makeParameterId($context);
        /* @var $query \Symfony\Component\HttpFoundation\ParameterBag */
        $query = $request->query;
        $page = ($query) ? $query->get($param, $query->get('page', 1)) : 1;

        $pageSize = $this->getOption('general/search_results_records') ?: ($this->getOption('general/listing_records') ?: 10);

        $offset = ($page - 1) * $pageSize;
        $limit = $pageSize;

        // set-up filters from URL
        $filters = array();
        foreach ($request->query->all() as $key => $value) {
            if (strpos($key, '_') > 0) {
                list($contenttypeslug, $field) = explode('_', $key, 2);
                if (isset($filters[$contenttypeslug])) {
                    $filters[$contenttypeslug][$field] = $value;
                } else {
                    $contenttype = $this->getContentType($contenttypeslug);
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

        $result = $this->app['storage']->searchContent($q, null, $filters, $limit, $offset);

        $pager = array(
            'for'          => $context,
            'count'        => $result['no_of_results'],
            'totalpages'   => ceil($result['no_of_results'] / $pageSize),
            'current'      => $page,
            'showing_from' => $offset + 1,
            'showing_to'   => $offset + count($result['results']),
            'link'         => '/search?q=' . rawurlencode($q) . '&page_search='
        );

        $this->app['storage']->setPager($context, $pager);

        $globals = array(
            'records'      => $result['results'],
            $context       => $result['query']['use_q'],
            'searchresult' => $result
        );

        $template = $this->templateChooser()->search();

        return $this->render($template, 'search', array(), $globals);
    }

    /**
     * Renders the specified template from the current theme in response to a request without
     * loading any content.
     *
     * @param string $template The template name
     *
     * @throws \Exception
     *
     * @return \Twig_Markup
     */
    public function template($template)
    {
        // Add the template extension if it is missing
        if (!preg_match('/\\.twig$/i', $template)) {
            $template .= '.twig';
        }

        return $this->render($template, $template);
    }

    /**
     * Render a template while wrapping Twig_Error_Loader in 404
     * in case the template is not found by Twig.
     *
     * @param \Silex\Application $app
     * @param string             $template Ex: 'listing.twig'
     * @param string             $title    '%s' in "No template for '%s' defined."
     *
     * @return \Twig_Markup Rendered template
     */
//     protected function render($template, $title)
//     {
//         try {
//             return $app['twig']->render($template);
//         } catch (\Twig_Error_Loader $e) {
//             $error = sprintf(
//                 'Rendering %s failed: %s',
//                 $title,
//                 $e->getMessage()
//             );

//             // Log it
//             $app['logger.system']->error($error, array('event' => 'twig'));

//             // Set the template error
//             $this->setTemplateError($app, $error);

//             // Abort ship
//             return $this->abort(Response::HTTP_INTERNAL_SERVER_ERROR, $error);
//         }
//     }

    /**
     * @deprecated to be removed in Bolt 3.0
     *
     * @param \Silex\Application $app
     * @param string             $error
     */
//     protected function setTemplateError($error)
//     {
//         if (isset($app['twig.logger'])) {
//             $app['twig.logger']->setTrackedValue('templateerror', $error);
//         }
//     }
}
