<?php

namespace Bolt\Controller;

use Bolt\Extensions\Snippets\Location as SnippetLocation;
use Bolt\Helpers\Input;
use Bolt\Pager;
use Bolt\Response\BoltResponse;
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
class Frontend extends ConfigurableBase
{
    protected function getConfigurationRoutes()
    {
        return $this->app['config']->get('routing', []);
    }

    protected function addRoutes(ControllerCollection $c)
    {
        $c->value(Zone::KEY, Zone::FRONTEND);
        parent::addRoutes($c);
    }

    /**
     * The default before filter for the controllers in this file.
     *
     * Refer to the routing.yml config file for overridding.
     *
     * @param Request $request The Symfony Request
     *
     * @return null|BoltResponse|RedirectResponse
     */
    public function before(Request $request)
    {
        // Start the 'stopwatch' for the profiler.
        $this->app['stopwatch']->start('bolt.frontend.before');

        // If there are no users in the users table, or the table doesn't exist. Repair
        // the DB, and let's add a new user.
        if (!$this->app['users']->getUsers()) {
            $this->flashes()->info(Trans::__('There are no users in the database. Please create the first user.'));

            return $this->redirectToRoute('useredit', ['id' => '']);
        }

        // If we are in maintenance mode and current user is not logged in, show maintenance notice.
        if ($this->getOption('general/maintenance_mode')) {
            if (!$this->isAllowed('maintenance-mode')) {
                $template = $this->templateChooser()->maintenance();
                $response = $this->render($template);
                $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
                return $response;
            }
        }

        // If we have a valid cache respose, return it.
        if ($response = $this->app['render']->fetchCachedRequest()) {
            // Stop the 'stopwatch' for the profiler.
            $this->app['stopwatch']->stop('bolt.frontend.before');

            // Short-circuit the request, return the HTML/response. YOLO.
            return $response;
        }

        // Stop the 'stopwatch' for the profiler.
        $this->app['stopwatch']->stop('bolt.frontend.before');

        return null;
    }

    /**
     * Controller for the "Homepage" route. Usually the front page of the website.
     *
     * @return BoltResponse
     */
    public function homepage()
    {
        $content = $this->getContent($this->getOption('general/homepage'));

        $template = $this->templateChooser()->homepage();

        $globals = [
            'records' => $content,
        ];

        if (is_array($content)) {
            $first = current($content);
            $globals[$first->contenttype['slug']] = $content;
        } elseif (!empty($content)) {
            $globals['record'] = $content;
            $globals[$content->contenttype['singular_slug']] = $content;
        }

        return $this->render($template, [], $globals);
    }

    /**
     * Controller for a single record page, like '/page/about/' or '/entry/lorum'.
     *
     * @param Request $request         The request
     * @param string  $contenttypeslug The content type slug
     * @param string  $slug            The content slug
     *
     * @return BoltResponse
     */
    public function record(Request $request, $contenttypeslug, $slug = '')
    {
        $contenttype = $this->getContentType($contenttypeslug);

        // If the contenttype is 'viewless', don't show the record page.
        if (isset($contenttype['viewless']) && $contenttype['viewless'] === true) {
            $this->abort(Response::HTTP_NOT_FOUND, "Page $contenttypeslug/$slug not found.");
            return null;
        }

        // Perhaps we don't have a slug. Let's see if we can pick up the 'id', instead.
        if (empty($slug)) {
            $slug = $request->get('id');
        }

        $slug = $this->app['slugify']->slugify($slug);

        // First, try to get it by slug.
        $content = $this->getContent($contenttype['slug'], ['slug' => $slug, 'returnsingle' => true, 'log_not_found' => !is_numeric($slug)]);

        if (!$content && is_numeric($slug)) {
            // And otherwise try getting it by ID
            $content = $this->getContent($contenttype['slug'], ['id' => $slug, 'returnsingle' => true]);
        }

        // No content, no page!
        if (!$content) {
            $this->abort(Response::HTTP_NOT_FOUND, "Page $contenttypeslug/$slug not found.");
            return null;
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
        $this->app['editlink'] = $this->generateUrl('editcontent', ['contenttypeslug' => $contenttype['slug'], 'id' => $content->id]);
        $this->app['edittitle'] = $content->getTitle();

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = [
            'record'                      => $content,
            $contenttype['singular_slug'] => $content
        ];

        return $this->render($template, [], $globals);
    }

    /**
     * The controller for previewing a content from posted data.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return BoltResponse
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
            $this->extensions()->insertSnippet(SnippetLocation::BEFORE_HEAD_JS, '<script>window.boltIsEditing = true;</script>');
            $this->extensions()->addJavascript($jsFile, ['late' => false, 'priority' => 1]);
            $this->extensions()->addCss($cssFile, false, 5);
        }

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $this->templateChooser()->record($content);

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = [
            'record'                      => $content,
            $contenttype['singular_slug'] => $content
        ];
        $response = $this->render($template, [], $globals);

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
        $response->headers->set('X-XSS-Protection', 0);

        return $response;
    }

    /**
     * The listing page controller.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return BoltResponse
     */
    public function listing(Request $request, $contenttypeslug)
    {
        $contenttype = $this->getContentType($contenttypeslug);

        // If the contenttype is 'viewless', don't show the record page.
        if (isset($contenttype['viewless']) && $contenttype['viewless'] === true) {
            $this->abort(Response::HTTP_NOT_FOUND, "Page $contenttypeslug not found.");
            return null;
        }

        $pagerid = Pager::makeParameterId($contenttypeslug);
        // First, get some content
        $page = $request->query->get($pagerid, $request->query->get('page', 1));
        $amount = (!empty($contenttype['listing_records']) ? $contenttype['listing_records'] : $this->getOption('general/listing_records'));
        $order = (!empty($contenttype['sort']) ? $contenttype['sort'] : $this->getOption('general/listing_sort'));
        $content = $this->getContent($contenttype['slug'], ['limit' => $amount, 'order' => $order, 'page' => $page, 'paging' => true]);

        $template = $this->templateChooser()->listing($contenttype);

        // Make sure we can also access it as {{ pages }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = [
            'records'            => $content,
            $contenttype['slug'] => $content,
            'contenttype'        => $contenttype['name']
        ];

        return $this->render($template, [], $globals);
    }

    /**
     * The taxonomy listing page controller.
     *
     * @param Request $request      The Symfony Request
     * @param string  $taxonomytype The taxonomy type slug
     * @param string  $slug         The taxonomy slug
     *
     * @return BoltResponse|false
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
        $content = $this->app['storage']->getContentByTaxonomy($taxonomytype, $slug, ['limit' => $amount, 'order' => $order, 'page' => $page]);

        // See https://github.com/bolt/bolt/pull/2310
        if (
                ($taxonomy['behaves_like'] === 'tags' && !$content) ||
                (
                    in_array($taxonomy['behaves_like'], ['categories', 'grouping']) &&
                    !in_array($slug, isset($taxonomy['options']) ? array_keys($taxonomy['options']) : [])
                )
            ) {
            $this->abort(Response::HTTP_NOT_FOUND, "No slug '$slug' in taxonomy '$taxonomyslug'");
            return null;
        }

        $template = $this->templateChooser()->taxonomy($taxonomyslug);

        $name = $slug;
        // Look in taxonomies in 'content', to get a display value for '$slug', perhaps.
        foreach ($content as $record) {
            $flat = util::array_flatten($record->taxonomy);
            $key = $this->app['resources']->getUrl('root') . $taxonomy['slug'] . '/' . $slug;
            if (isset($flat[$key])) {
                $name = $flat[$key];
            }
            $key = $this->app['resources']->getUrl('root') . $taxonomy['singular_slug'] . '/' . $slug;
            if (isset($flat[$key])) {
                $name = $flat[$key];
            }
        }

        $globals = [
            'records'      => $content,
            'slug'         => $name,
            'taxonomy'     => $this->getOption('taxonomy/' . $taxonomyslug),
            'taxonomytype' => $taxonomyslug
        ];

        return $this->render($template, [], $globals);
    }

    /**
     * The search result page controller.
     *
     * @param Request $request The Symfony Request
     *
     * @return BoltResponse
     */
    public function search(Request $request)
    {
        $q = '';
        $context = __FUNCTION__;

        if ($request->query->has('q')) {
            $q = $request->query->get('q');
        } elseif ($request->query->has($context)) {
            $q = $request->query->get($context);
        }
        $q = Input::cleanPostedData($q, false);

        $param = Pager::makeParameterId($context);
        $page = $request->query->get($param, $request->query->get('page', 1));

        $pageSize = $this->getOption('general/search_results_records') ?: ($this->getOption('general/listing_records') ?: 10);

        $offset = ($page - 1) * $pageSize;
        $limit = $pageSize;

        // set-up filters from URL
        $filters = [];
        foreach ($request->query->all() as $key => $value) {
            if (strpos($key, '_') > 0) {
                list($contenttypeslug, $field) = explode('_', $key, 2);
                if (isset($filters[$contenttypeslug])) {
                    $filters[$contenttypeslug][$field] = $value;
                } else {
                    $contenttype = $this->getContentType($contenttypeslug);
                    if (is_array($contenttype)) {
                        $filters[$contenttypeslug] = [
                            $field => $value
                        ];
                    }
                }
            }
        }
        if (count($filters) == 0) {
            $filters = null;
        }

        $result = $this->app['storage']->searchContent($q, null, $filters, $limit, $offset);

        $pager = [
            'for'          => $context,
            'count'        => $result['no_of_results'],
            'totalpages'   => ceil($result['no_of_results'] / $pageSize),
            'current'      => $page,
            'showing_from' => $offset + 1,
            'showing_to'   => $offset + count($result['results']),
            'link'         => $this->generateUrl('search', ['q' => $q]) . '&page_search='
        ];

        $this->app['storage']->setPager($context, $pager);

        $globals = [
            'records'      => $result['results'],
            $context       => $result['query']['use_q'],
            'searchresult' => $result
        ];

        $template = $this->templateChooser()->search();

        return $this->render($template, [], $globals);
    }

    /**
     * Renders the specified template from the current theme in response to a request without
     * loading any content.
     *
     * @param string $template The template name
     *
     * @return BoltResponse
     */
    public function template($template)
    {
        // Add the template extension if it is missing
        if (!preg_match('/\\.twig$/i', $template)) {
            $template .= '.twig';
        }

        return $this->render($template);
    }
}
