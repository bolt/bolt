<?php

namespace Bolt\Controller;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
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
 * https://docs.bolt.cm/templates/templates-routes#routing or the routing.yml
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

        // If there are no users in the users table, or the table doesn't exist.
        // Repair the DB, and let's add a new user.
        if (!$this->hasUsers()) {
            $this->flashes()->info(Trans::__('general.phrase.users-none-create-first'));

            return $this->redirectToRoute('userfirst');
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
     * @param Request $request
     *
     * @return BoltResponse
     */
    public function homepage(Request $request)
    {
        $homepage = $this->getOption('theme/homepage') ?: $this->getOption('general/homepage');
        $listingparameters = $this->getListingParameters($request, $homepage);
        $content = $this->getContent($homepage, $listingparameters);

        $template = $this->templateChooser()->homepage($content);
        $globals = [];

        if (is_array($content)) {
            $first = current($content);
            $globals[$first->contenttype['slug']] = $content;
            $globals['records'] = $content;
        } elseif (!empty($content)) {
            $globals['record'] = $content;
            $globals[$content->contenttype['singular_slug']] = $content;
            $globals['records'] = [$content->id => $content];
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

        // Setting the canonical URL.
        if ($content->isHome() && ($template === $this->getOption('general/homepage_template'))) {
            $url = $this->app['resources']->getUrl('rooturl');
        } else {
            $url = $this->app['resources']->getUrl('rooturl') . ltrim($content->link(), '/');
        }
        $this->app['resources']->setUrl('canonicalurl', $url);

        // Setting the editlink
        $this->app['editlink'] = $this->generateUrl('editcontent', ['contenttypeslug' => $contenttype['slug'], 'id' => $content->id]);
        $this->app['edittitle'] = $content->getTitle();

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = [
            'record'                      => $content,
            $contenttype['singular_slug'] => $content,
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

        $id = $request->request->get('id');
        if ($id) {
            $content = $this->storage()->getContent($contenttype['slug'], ['id' => $id, 'returnsingle' => true, 'status' => '!undefined']);
        } else {
            $content = $this->storage()->getContentObject($contenttypeslug);
        }

        $content->setFromPost($request->request->all(), $contenttype);

        $liveEditor = $request->get('_live-editor-preview');
        if (!empty($liveEditor)) {
            $jsFile = (new JavaScript('js/ckeditor/ckeditor.js', 'bolt'))
                ->setPriority(1)
                ->setLate(false);
            $cssFile = (new Stylesheet('css/liveeditor.css', 'bolt'))
                ->setPriority(5)
                ->setLate(false);
            $snippet = (new Snippet())
                ->setCallback('<script>window.boltIsEditing = true;</script>')
                ->setLocation(Target::BEFORE_HEAD_JS);

            $this->app['asset.queue.snippet']->add($snippet);
            $this->app['asset.queue.file']->add($jsFile);
            $this->app['asset.queue.file']->add($cssFile);
        }

        // Then, select which template to use, based on our 'cascading templates rules'
        $template = $this->templateChooser()->record($content);

        // Make sure we can also access it as {{ page.title }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = [
            'record'                      => $content,
            $contenttype['singular_slug'] => $content,
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
        $listingparameters = $this->getListingParameters($request, $contenttypeslug);
        $content = $this->getContent($contenttypeslug, $listingparameters);
        $contenttype = $this->getContentType($contenttypeslug);

        $template = $this->templateChooser()->listing($contenttype);

        // Make sure we can also access it as {{ pages }} for pages, etc. We set these in the global scope,
        // So that they're also available in menu's and templates rendered by extensions.
        $globals = [
            'records'        => $content,
            $contenttypeslug => $content,
            'contenttype'    => $contenttype['name'],
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
    public function taxonomy(Request $request, $taxonomytype, $slug)
    {
        $taxonomy = $this->storage()->getTaxonomyType($taxonomytype);
        // No taxonomytype, no possible content.
        if (empty($taxonomy)) {
            return false;
        } else {
            $taxonomyslug = $taxonomy['slug'];
        }
        // First, get some content
        $context = $taxonomy['singular_slug'] . '_' . $slug;
        $page = $this->app['pager']->getCurrentPage($context);
        // Theme value takes precedence over default config @see https://github.com/bolt/bolt/issues/3951
        $amount = $this->getOption('theme/listing_records', false) ?: $this->getOption('general/listing_records');

        // Handle case where listing records has been override for specific taxonomy
        if (array_key_exists('listing_records', $taxonomy) && is_int($taxonomy['listing_records'])) {
            $amount = $taxonomy['listing_records'];
        }

        $order = $this->getOption('theme/listing_sort', false) ?: $this->getOption('general/listing_sort');
        $content = $this->storage()->getContentByTaxonomy($taxonomytype, $slug, ['limit' => $amount, 'order' => $order, 'page' => $page]);

        if (!$this->isTaxonomyValid($content, $slug, $taxonomy)) {
            $this->abort(Response::HTTP_NOT_FOUND, "No slug '$slug' in taxonomy '$taxonomyslug'");

            return;
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
            'taxonomytype' => $taxonomyslug,
        ];

        return $this->render($template, [], $globals);
    }

    /**
     * Check if the taxonomy is valid.
     *
     * @see https://github.com/bolt/bolt/pull/2310
     *
     * @param Content $content
     * @param string  $slug
     * @param array   $taxonomy
     *
     * @return boolean
     */
    protected function isTaxonomyValid($content, $slug, array $taxonomy)
    {
        if ($taxonomy['behaves_like'] === 'tags' && !$content) {
            return false;
        }

        $isNotTag = in_array($taxonomy['behaves_like'], ['categories', 'grouping']);
        $options = isset($taxonomy['options']) ? array_keys($taxonomy['options']) : [];
        $isTax = in_array($slug, $options);
        if ($isNotTag && !$isTax) {
            return false;
        }

        return true;
    }

    /**
     * The search result page controller.
     *
     * @param Request $request      The Symfony Request
     * @param array   $contenttypes The content type slug(s) you want to search for
     *
     * @return BoltResponse
     */
    public function search(Request $request, array $contenttypes = null)
    {
        $q = '';
        $context = __FUNCTION__;

        if ($request->query->has('q')) {
            $q = $request->query->get('q');
        } elseif ($request->query->has($context)) {
            $q = $request->query->get($context);
        }
        $q = Input::cleanPostedData($q, false);

        $page = $this->app['pager']->getCurrentPage($context);

        // Theme value takes precedence over default config @see https://github.com/bolt/bolt/issues/3951
        $pageSize = $this->getOption('theme/search_results_records', false);
        if ($pageSize === false && !$pageSize = $this->getOption('general/search_results_records', false)) {
            $pageSize = $this->getOption('theme/listing_records', false) ?: $this->getOption('general/listing_records', 10);
        }

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
                            $field => $value,
                        ];
                    }
                }
            }
        }
        if (count($filters) == 0) {
            $filters = null;
        }

        $result = $this->storage()->searchContent($q, $contenttypes, $filters, $limit, $offset);

        /** @var \Bolt\Pager\PagerManager $manager */
        $manager = $this->app['pager'];
        $manager
            ->createPager($context)
            ->setCount($result['no_of_results'])
            ->setTotalpages(ceil($result['no_of_results'] / $pageSize))
            ->setCurrent($page)
            ->setShowingFrom($offset + 1)
            ->setShowingTo($offset + count($result['results']));

        $manager->setLink($this->generateUrl('search', ['q' => $q]) . '&page_search=');

        $globals = [
            'records'      => $result['results'],
            $context       => $result['query']['sanitized_q'],
            'searchresult' => $result,
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

    /**
     * Returns an array of the parameters used in getContent for listing pages.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return array Parameters to use in getContent
     */
    private function getListingParameters(Request $request, $contenttypeslug)
    {
        $contenttype = $this->getContentType(current(explode('/', $contenttypeslug)));

        // If the contenttype is 'viewless', don't show the listing / record page.
        if (isset($contenttype['viewless']) && $contenttype['viewless'] === true) {
            $this->abort(Response::HTTP_NOT_FOUND, 'Page ' . $contenttype['slug'] . ' not found.');
        }

        // Build the pager
        $page = $this->app['pager']->getCurrentPage($contenttype['slug']);

        // Theme value takes precedence over CT & default config
        // @see https://github.com/bolt/bolt/issues/3951
        if (!$amount = $this->getOption('theme/listing_records', false)) {
            $amount = empty($contenttype['listing_records']) ? $this->getOption('general/listing_records') : $contenttype['listing_records'];
        }
        if (!$order = $this->getOption('theme/listing_sort', false)) {
            $order = empty($contenttype['sort']) ? null : $contenttype['sort'];
        }
        // If $order is not set, one of two things can happen: Either we let `getContent()` sort by itself, or we
        // explicitly set it to sort on the general/listing_sort setting.
        if ($order === null) {
            $taxonomies = $this->getOption('taxonomy');
            $hassortorder = false;
            if (!empty($contenttype['taxonomy'])) {
                foreach ($contenttype['taxonomy'] as $contenttypetaxonomy) {
                    if ($taxonomies[$contenttypetaxonomy]['has_sortorder']) {
                        // We have a taxonomy with a sortorder, so we must keep $order = false, in order
                        // to let `getContent()` handle it. We skip the fallback that's a few lines below.
                        $hassortorder = true;
                    }
                }
            }
            if (!$hassortorder) {
                $order = $this->getOption('general/listing_sort');
            }
        }

        return ['limit' => $amount, 'order' => $order, 'page' => $page, 'paging' => true];
    }
}
