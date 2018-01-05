<?php

namespace Bolt\Controller\Backend;

use Bolt\Collection\MutableBag;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Form\FormType\FileEditType;
use Bolt\Form\FormType\PrefillType;
use Bolt\Form\Validator\Constraints;
use Bolt\Helpers\Input;
use Bolt\Omnisearch;
use Bolt\Translation\TranslationFile;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Requirements\PhpConfigRequirement;
use Symfony\Requirements\RequirementCollection;

/**
 * General controller for basic backend routes.
 *
 * Prior to v3.0 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class General extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->get('/about', 'about')
            ->bind('about');

        $c->get('/checks', 'checks')
            ->bind('checks');

        $c->get('/clearcache', 'clearCache')
            ->bind('clearcache');

        $c->get('/', 'dashboard')
            ->bind('dashboard');

        $c->get('/omnisearch', 'omnisearch')
            ->bind('omnisearch-results');

        $c->match('/prefill', 'prefill')
            ->bind('prefill');

        $c->match('/tr/{domain}/{tr_locale}', 'translation')
            ->bind('translation')
            ->assert('domain', 'messages|infos')
            ->value('domain', 'messages')
            ->value('tr_locale', $this->app['locale']);
    }

    /**
     * About page route.
     *
     * @return \Bolt\Response\TemplateResponse
     */
    public function about()
    {
        return $this->render('@bolt/about/about.twig');
    }

    /**
     * Configuration checks/tests route.
     *
     * @param Request $request
     *
     * @return \Bolt\Response\TemplateResponse
     */
    public function checks(Request $request)
    {
        $defaults = [
            'pass' => ['php' => [], 'system' => []],
            'fail' => ['php' => [], 'system' => []],
        ];
        $checks = MutableBag::fromRecursive([
            'requirements'    => $defaults,
            'recommendations' => $defaults,
        ]);
        /** @var RequirementCollection $baseReqs */
        $baseReqs = $this->app['requirements'];

        foreach ($baseReqs->getRequirements() as $requirement) {
            $result = $requirement->isFulfilled() ? 'pass' : 'fail';
            if ($requirement instanceof PhpConfigRequirement) {
                $checks->get('requirements')->get($result)->get('php')->add($requirement);

                continue;
            }
            $checks->get('requirements')->get($result)->get('system')->add($requirement);
        }
        foreach ($baseReqs->getRecommendations() as $recommendation) {
            $result = $recommendation->isFulfilled() ? 'pass' : 'fail';
            if ($recommendation instanceof PhpConfigRequirement) {
                $checks->get('recommendations')->get($result)->get('php')->add($recommendation);

                continue;
            }
            $checks->get('recommendations')->get($result)->get('system')->add($recommendation);
        }
        $showAll = $request->query->getBoolean('all');

        return $this->render('@bolt/checks/checks.twig', ['checks' => $checks, 'show_all' => $showAll]);
    }

    /**
     * Clear the cache.
     *
     * @return \Bolt\Response\TemplateResponse
     */
    public function clearCache()
    {
        $result = $this->app['cache']->flushAll();

        if ($result) {
            $this->flashes()->success(Trans::__('general.phrase.clear-cache-complete'));
        } else {
            $this->flashes()->error(Trans::__('general.phrase.error-cache-clear'));
        }

        return $this->render('@bolt/clearcache/clearcache.twig');
    }

    /**
     * Dashboard or 'root' route.
     *
     * @return \Bolt\Response\TemplateResponse
     */
    public function dashboard()
    {
        return $this->render('@bolt/dashboard/dashboard.twig', $this->getLatest());
    }

    /**
     * Show the Omnisearch results.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\TemplateResponse
     */
    public function omnisearch(Request $request)
    {
        $query = $request->query->get('q', '');
        $records = [];
        $others = [];

        if (strlen($query) >= 3) {
            foreach ($this->app['omnisearch']->query($query, true) as $result) {
                if (isset($result['slug'])) {
                    $records[$result['slug']][] = $result;
                } elseif ($result['priority'] !== Omnisearch::OMNISEARCH_LANDINGPAGE) {
                    $others[] = $result;
                }
            }
        }

        $context = [
            'query'   => $query,
            'records' => $records,
            'others'  => $others,
        ];

        return $this->render('@bolt/omnisearch/omnisearch.twig', $context);
    }

    /**
     * Generate Lorem Ipsum records in the database for given ContentTypes.
     *
     * @param Request $request The Symfony Request
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function prefill(Request $request)
    {
        // Determine the ContentTypes that we're doing the prefill for
        $choices = [];
        foreach ($this->getOption('contenttypes') as $key => $contentType) {
            $nameKey = 'contenttypes.' . $key . '.name.plural';
            $nameTrans = Trans::__($nameKey, [], 'contenttypes');
            $name = ($nameTrans === $nameKey) ? $contentType['name'] : $nameTrans;
            $choices[$name] = $key;
        }

        // Create the form
        $options = [
            'attr'         => ['data-bind' => '{"bind":"prefill"}'],
            'contenttypes' => $choices,
        ];
        $form = $this->createFormBuilder(PrefillType::class, [], $options)
            ->getForm()
        ;

        if ($request->isMethod('POST') || $request->query->getBoolean('force')) {
            $form->handleRequest($request);
            $contentTypeNames = (array) $form->get('contenttypes')->getData();

            // ✓ - If the DB is empty
            // ✓ - If ContentType(s) *are* selected
            // ✓ - If *no* ContentTypes are selected *and* a ContentType's record count < max
            // X - If *no* ContentTypes are selected *and* a ContentType's record count >= max
            $canExceedMax = true;
            if (count($contentTypeNames) === 0) {
                $contentTypeNames = $choices;
                $canExceedMax = false;
            }

            $builder = $this->app['prefill.builder'];
            $results = $builder->build($contentTypeNames, 5, $canExceedMax);
            $this->session()->set('prefill_result', $results);

            return $this->redirectToRoute('prefill');
        }

        $prefillResult = $this->session()->remove('prefill_result') ?: ['created' => null, 'errors' => null, 'warnings' => null];
        $context = [
            'contenttypes' => $choices,
            'form'         => $form->createView(),
            'results'      => $prefillResult,
        ];

        return $this->render('@bolt/prefill/prefill.twig', $context);
    }

    /**
     * Prepare/edit/save a translation.
     *
     * @param Request $request   The Symfony Request
     * @param string  $domain    The domain
     * @param string  $tr_locale The translation locale
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function translation(Request $request, $domain, $tr_locale)
    {
        $tr = MutableBag::from([
            'domain' => $domain,
            'locale' => $tr_locale,
        ]);

        // Get the translation data
        $data = $this->getTranslationData($tr);
        $options = [
            'write_allowed'        => $tr['writeallowed'],
            'contents_constraints' => [new Constraints\Yaml()],
        ];

        // Create the form
        $form = $this->createFormBuilder(FileEditType::class, $data, $options)
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if ($response = $this->saveTranslationFile($data['contents'], $tr)) {
                return $response;
            }
        }

        $context = [
            'form'          => $form->createView(),
            'basename'      => basename($tr['shortPath']),
            'filetype'      => 'yml',
            'write_allowed' => $tr['writeallowed'],
        ];

        return $this->render('@bolt/editlocale/editlocale.twig', $context);
    }

    /**
     * Get the latest records for viewable ContentTypes that a user has access
     * to.
     *
     * When there are no ContentType records we will suggest to create some
     * dummy content.
     *
     * @param int $limit
     *
     * @return array
     */
    private function getLatest($limit = null)
    {
        $total = 0;
        $latest = [];
        $permissions = [];
        $user = $this->users()->getCurrentUser();
        $queryParams = [
            'limit'   => $limit ?: $this->getOption('general/recordsperdashboardwidget'),
            'order'   => '-datechanged',
            'hydrate' => false,
        ];

        // Get the 'latest' from each of the content types.
        foreach ($this->getOption('contenttypes') as $key => $contentType) {
            if (!$this->isAllowed('contenttype:' . $key) || $contentType['show_on_dashboard'] !== true || $user === null) {
                continue;
            }
            try {
                $queryResultSet = $this->getContent($key, $queryParams);
            } catch (TableNotFoundException $e) {
                // User will be alerted via flash notice
                continue;
            }
            $latest[$key] = $queryResultSet->get();
            $permissions[$key] = $this->getContentTypeUserPermissions($contentType, $user);
            $total += $queryResultSet->count();
        }

        return [
            'latest'          => $latest,
            'permissions'     => $permissions,
            'suggestloripsum' => ($total === 0),
        ];
    }

    /**
     * Get the translation data.
     *
     * @param MutableBag $tr
     *
     * @return array
     */
    private function getTranslationData(MutableBag $tr)
    {
        $translation = new TranslationFile($this->app, $tr->get('domain'), $tr->get('locale'));

        list($tr['path'], $tr['shortPath']) = $translation->path();
        $tr->set('writeallowed', $translation->isWriteAllowed());

        $this->app['logger.system']->info('Editing translation: ' . $tr->get('shortPath'), ['event' => 'translation']);

        return ['contents' => $translation->content()];
    }

    /**
     * Attempt to save the POST data for a translation file edit.
     *
     * @param string     $contents
     * @param MutableBag $tr
     *
     * @return bool|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function saveTranslationFile($contents, MutableBag $tr)
    {
        $contents = Input::cleanPostedData($contents) . "\n";

        // Clear any warning for file not found, we are creating it here
        // we'll set an error if someone still submits the form and write is not allowed
        $this->flashes()->clear();

        $file = $this->filesystem()->getFile('bolt://' . $tr->get('shortPath'));
        try {
            $file->put($contents);
        } catch (IOException $e) {
            $msg = Trans::__('general.phrase.file-not-writable', ['%s' => $tr->get('shortPath')]);
            $this->flashes()->error($msg);
            $tr['writeallowed'] = false;

            return false;
        }

        $msg = Trans::__('page.file-management.message.save-success', ['%s' => $tr->get('shortPath')]);
        $this->flashes()->info($msg);

        return $this->redirectToRoute('translation', ['domain' => $tr->get('domain'), 'tr_locale' => $tr->get('locale')]);
    }
}
