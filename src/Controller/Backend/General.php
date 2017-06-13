<?php

namespace Bolt\Controller\Backend;

use Bolt\Collection\Bag;
use Bolt\Form\FormType\PrefillType;
use Bolt\Helpers\Input;
use Bolt\Omnisearch;
use Bolt\Requirement\BoltRequirements;
use Bolt\Translation\TranslationFile;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Requirements\PhpConfigRequirement;

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
            ->assert('domain', 'messages|contenttypes|infos')
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
        $checks = Bag::fromRecursive([
            'requirements'    => $defaults,
            'recommendations' => $defaults,
        ]);
        $baseReqs = new BoltRequirements($this->app['path_resolver']->resolve('%root%'));

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
            'attr' => [
                'data-bind' => json_encode(['bind' => 'prefill']),
            ],
            'contenttypes' => $choices,
        ];
        $form = $this->createFormBuilder(PrefillType::class, [], $options)
            ->getForm()
        ;

        if ($request->isMethod('POST') || $request->query->getBoolean('force')) {
            $form->handleRequest($request);
            if ($form->get('contenttypes')->has('contenttypes')) {
                $contentTypeNames = (array) $form->get('contenttypes')->getData();
            } else {
                $contentTypes = $this->app['config']->get('contenttypes');
                $contentTypeNames = array_keys($contentTypes);
            }

            $builder = $this->app['prefill.builder'];
            $results = $builder->build($contentTypeNames, 5);
            $this->session()->set('prefill_result', $results);

            return $this->redirectToRoute('prefill');
        }

        $prefillResult = $this->session()->remove('prefill_result') ?: ['created' => null, 'errors' => null];
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
        $tr = [
            'domain' => $domain,
            'locale' => $tr_locale,
        ];

        // Get the translation data
        $data = $this->getTranslationData($tr);

        // Create the form
        $form = $this->createFormBuilder(FormType::class, $data)
            ->add(
                'contents',
                TextareaType::class,
                [
                    'constraints' => [
                        new Assert\NotBlank(),
                        new Assert\Length(['min' => 10]),
                    ],
                ]
            )
            ->add(
                'submit',
                SubmitType::class,
                [
                    'label'    => Trans::__('page.edit-locale.button.save'),
                    'disabled' => !$tr['writeallowed'],
                ]
            )
            ->getForm();

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
     * @param integer $limit
     *
     * @return array
     */
    private function getLatest($limit = null)
    {
        $total  = 0;
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
            if ($this->isAllowed('contenttype:' . $key) && $contentType['show_on_dashboard'] === true && $user !== null) {
                $queryResultSet = $this->getContent($key, $queryParams);
                $latest[$key] = $queryResultSet->get();
                $permissions[$key] = $this->getContentTypeUserPermissions($contentType, $user);
                $total += $queryResultSet->count();
            }
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
     * @param array $tr
     *
     * @return string
     */
    private function getTranslationData(array &$tr)
    {
        $translation = new TranslationFile($this->app, $tr['domain'], $tr['locale']);

        list($tr['path'], $tr['shortPath']) = $translation->path();

        $this->app['logger.system']->info('Editing translation: ' . $tr['shortPath'], ['event' => 'translation']);

        $tr['writeallowed'] = $translation->isWriteAllowed();

        return ['contents' => $translation->content()];
    }

    /**
     * Attempt to save the POST data for a translation file edit.
     *
     * @param string $contents
     * @param array  $tr
     *
     * @return boolean|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function saveTranslationFile($contents, array &$tr)
    {
        $contents = Input::cleanPostedData($contents) . "\n";

        // Before trying to save a yaml file, check if it's valid.
        try {
            Yaml::parse($contents);
        } catch (ParseException $e) {
            $msg = Trans::__('page.file-management.message.save-failed-colon', ['%s' => $tr['shortPath']]);
            $this->flashes()->error($msg . ' ' . $e->getMessage());

            return false;
        }

        // Clear any warning for file not found, we are creating it here
        // we'll set an error if someone still submits the form and write is not allowed
        $this->flashes()->clear();

        try {
            $fs = new Filesystem();
            $fs->dumpFile($tr['path'], $contents);
        } catch (IOException $e) {
            $msg = Trans::__('general.phrase.file-not-writable', ['%s' => $tr['shortPath']]);
            $this->flashes()->error($msg);
            $tr['writeallowed'] = false;

            return false;
        }

        $msg = Trans::__('page.file-management.message.save-success', ['%s' => $tr['shortPath']]);
        $this->flashes()->info($msg);

        return $this->redirectToRoute('translation', ['domain' => $tr['domain'], 'tr_locale' => $tr['locale']]);
    }
}
