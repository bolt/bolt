<?php

namespace Bolt\Controller;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Helpers\Input;
use Bolt\Response\TemplateResponse;
use Bolt\Storage\Entity\Taxonomy;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ContentType;
use Bolt\Storage\Query\QueryResultset;
use Bolt\Storage\Repository\TaxonomyRepository;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Standard Frontend actions.
 *
 * This file acts as a grouping for the default front-end controllers.
 *
 * For overriding the default behavior here, please reference
 * https://docs.bolt.cm/templating/templates-routes#routing or the routing.yml
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
     * @return null|TemplateResponse|RedirectResponse
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
        if ($this->getOption('general/maintenance_mode') && !$this->isAllowed('maintenance-mode')) {
            $twig = $this->app['twig'];
            $template = $this->templateChooser()->maintenance();

            $html = $twig->resolveTemplate($template)->render([]);
            $response = new TemplateResponse($template, [], $html, Response::HTTP_SERVICE_UNAVAILABLE);

            return $response;
        }

        // Stop the 'stopwatch' for the profiler.
        $this->app['stopwatch']->stop('bolt.frontend.before');

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function after(Request $request, Response $response)
    {
        if ($this->session()->isStarted()) {
            $response->setPrivate();
        } else {
            $sharedMaxAge = $this->getOption('general/caching/duration', 10) * 60;
            $response
                ->setPublic()
                ->setSharedMaxAge($sharedMaxAge)
            ;
        }
    }

    /**
     * Controller for the "Homepage" route. Usually the front page of the website.
     *
     * @param Request $request
     *
     * @return TemplateResponse
     */
    public function homepage(Request $request)
    {
        $homepage = $this->getOption('theme/homepage') ?: $this->getOption('general/homepage');
        $listingParameters = $this->getListingParameters($homepage, true);
        $content = $this->getContent($homepage, $listingParameters);

        $template = $this->templateChooser()->homepage($content);
        $globals = [];

        if (is_array($content) && count($content) > 0) {
            $first = current($content);
            $globals[$first->contenttype['slug']] = $content;
            $globals['records'] = $content;
        } elseif (is_object($content)) {
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
     * @return TemplateResponse
     */
    public function record(Request $request, $contenttypeslug, $slug = '')
    {
        $contenttype = $this->getContentType($contenttypeslug);

        // If the ContentType is 'viewless', don't show the record page.
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

        if (is_numeric($slug) && !$content) {
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
     * @throws \Exception
     *
     * @return TemplateResponse
     */
    public function preview(Request $request, $contenttypeslug)
    {
        if (!$request->isMethod('POST')) {
            throw new MethodNotAllowedHttpException(['POST'], 'This route only accepts POST requests.');
        }

        $contenttype = $this->getContentType($contenttypeslug);

        $storage = $this->storage();
        if ($storage instanceof EntityManager) {
            // @todo find a better way to initiate Content object from POST data (current approach doesn't fill relations for example)
            /** @var EntityManager $storage */
            $content = $storage->create($contenttypeslug, $request->request->all());
        } else {
            $content = $storage->getContentObject($contenttypeslug, [], false);
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

        return $this->render($template, [], $globals);
    }

    /**
     * The listing page controller.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return TemplateResponse
     */
    public function listing(Request $request, $contenttypeslug)
    {
        $listingParameters = $this->getListingParameters($contenttypeslug);
        $content = $this->getContent($contenttypeslug, $listingParameters);
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
     * @throws \Bolt\Exception\InvalidRepositoryException
     *
     * @return TemplateResponse|false
     */
    public function taxonomy(Request $request, $taxonomytype, $slug)
    {
        $taxonomy = $this->app['config']->get('taxonomy/' . $taxonomytype);
        // No taxonomytype, no possible content.
        if (empty($taxonomy)) {
            return false;
        }
        $taxonomyslug = $taxonomy['slug'];

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
        $isLegacy = $this->getOption('general/compatibility/setcontent_legacy', true);
        if ($isLegacy) {
            $content = $this->storage()->getContentByTaxonomy($taxonomytype, $slug, ['limit' => $amount, 'order' => $order, 'page' => $page]);
        } else {
            $page = $this->app['pager']->getCurrentPage('taxonomy');
            $appCt = array_keys($this->app['query.search_config']->getSearchableTypes());
            /** @var TaxonomyRepository $repo */
            $repo = $this->app['storage']->getRepository(Taxonomy::class);
            $query = $repo->queryContentByTaxonomy($appCt, [$taxonomytype => $slug])
                ->setFirstResult(($page - 1) * $amount)
                ->setMaxResults($amount)
            ;

            $results = $repo->getContentByTaxonomy($query);
            $set = new QueryResultset();
            foreach ($results->getCollection() as $record) {
                $set->add([$record]);
            }
            $content = $this->app['twig.records.view']->createView($set);
        }

        if (!$this->isTaxonomyValid($content, $slug, $taxonomy)) {
            $this->abort(Response::HTTP_NOT_FOUND, "No slug '$slug' in taxonomy '$taxonomyslug'");
        }

        $template = $this->templateChooser()->taxonomy($taxonomyslug);

        // Get a display value for slug. This should be moved from 'slug' context key to 'name' in v4.0.
        $name = $slug;
        if ($taxonomy['behaves_like'] !== 'tags' && isset($taxonomy['options'][$slug])) {
            $name = $taxonomy['options'][$slug];
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
     * @param array|false $content
     * @param string      $slug
     * @param array       $taxonomy
     *
     * @return bool
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
     * @return TemplateResponse
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

        $isLegacy = $this->getOption('general/compatibility/setcontent_legacy', true);
        if ($isLegacy) {
            $result = $this->storage()->searchContent($q, $contenttypes, $filters, $limit, $offset);

            /** @var \Bolt\Pager\PagerManager $manager */
            $manager = $this->app['pager'];
            $manager
                ->createPager($context)
                ->setCount($result['no_of_results'])
                ->setTotalpages(ceil($result['no_of_results'] / $pageSize))
                ->setCurrent($page)
                ->setShowingFrom($offset + 1)
                ->setShowingTo($offset + ($result ? count($result['results']) : 0));
            ;

            $manager->setLink($this->generateUrl('search', ['q' => $q]) . '&page_search=');
        } else {
            $appCt = array_keys($this->app['query.search_config']->getSearchableTypes());
            $textQuery = '(' . join(',', $appCt) . ')/search';
            $params = [
                'filter' => $q,
                'page'   => $page,
                'limit'  => $pageSize,
            ];
            $searchResult = $this->getContent($textQuery, $params);

            $result = [
                'results' => $searchResult,
                'query'   => [
                    'sanitized_q' => strip_tags($q),
                ],
            ];
        }

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
     * @return TemplateResponse
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
     * @param string $contentTypeSlug The content type slug
     * @param bool   $allowViewless   Allow viewless contenttype
     *
     * @return array Parameters to use in getContent
     */
    private function getListingParameters($contentTypeSlug, $allowViewless = false)
    {
        $contentType = $this->getContentType(current(explode('/', $contentTypeSlug)));

        // If there is no ContentType, don't get parameters for it
        if ($contentType === false) {
            return [];
        }

        // If the ContentType is 'viewless', don't show the listing / record page.
        if ($contentType['viewless'] && !$allowViewless) {
            $this->abort(Response::HTTP_NOT_FOUND, 'Page ' . $contentType['slug'] . ' not found.');
        }

        // Build the pager
        $page = $this->app['pager']->getCurrentPage($contentType['slug']);
        $order = isset($contentType['listing_sort']) ? $contentType['listing_sort'] : $this->getListingOrder($contentType);

        // CT value takes precedence over theme & config.yml
        if (!empty($contentType['listing_records'])) {
            $amount = $contentType['listing_records'];
        } else {
            $amount = $this->getOption('theme/listing_records') ?: $this->getOption('general/listing_records');
        }

        return ['limit' => $amount, 'order' => $order, 'page' => $page, 'paging' => true];
    }

    /**
     * Return the listing order.
     *
     * If the ContentType's sort is false (default in Config::parseContentType),
     * either:
     *  - we let `getContent()` sort by itself
     *  - we explicitly set it to sort on the general/listing_sort setting
     *
     * @param ContentType|array $contentType
     *
     * @return null|string
     */
    private function getListingOrder($contentType)
    {
        // An empty default isn't set in config yet, arrays got to hate them.
        if (isset($contentType['taxonomy'])) {
            $taxonomies = $this->getOption('taxonomy');
            foreach ($contentType['taxonomy'] as $taxonomyName) {
                if ($taxonomies[$taxonomyName]['has_sortorder']) {
                    // Let getContent() handle it
                    return null;
                }
            }
        }

        return $this->getOption('theme/listing_sort') ?: $this->getOption('general/listing_sort');
    }
}
