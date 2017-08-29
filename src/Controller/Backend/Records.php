<?php

namespace Bolt\Controller\Backend;

use Bolt\Exception\InvalidRepositoryException;
use Bolt\Storage\ContentRequest\ListingOptions;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Backend controller for record manipulation routes.
 *
 * Prior to v3.0 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Records extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->method('GET|POST');

        $c->match('/editcontent/{contenttypeslug}/{id}', 'edit')
            ->bind('editcontent')
            ->assert('id', '\d*')
            ->value('id', '');

        $c->get('/overview/{contenttypeslug}', 'overview')
            ->bind('overview');

        $c->get('/relatedto/{contenttypeslug}/{id}', 'related')
            ->bind('relatedto')
            ->assert('id', '\d*');
    }

    /**
     * Edit a record, or create a new one.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param integer $id              The content ID
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function edit(Request $request, $contenttypeslug, $id)
    {
        // Is the record new or existing
        $new = empty($id);

        // Test the access control
        if ($response = $this->checkEditAccess($contenttypeslug, $id)) {
            return $response;
        }

        // Get the ContentType object
        $contentType = $this->getContentType($contenttypeslug);

        // Save the POSTed record
        if ($request->isMethod('POST')) {
            $this->validateCsrfToken();

            $formValues = $request->request->all();
            $returnTo = $request->get('returnto');
            $editReferrer = $request->get('editreferrer');

            return $this->recordSave()->action($formValues, $contentType, $id, $new, $returnTo, $editReferrer);
        }

        try {
            // Get the record
            $repo = $this->getRepository($contenttypeslug);
        } catch (InvalidRepositoryException $e) {
            $this->flashes()->error(Trans::__('contenttypes.generic.not-existing', ['%contenttype%' => $contenttypeslug]));

            return $this->redirectToRoute('dashboard');
        }

        if ($new) {
            $content = $repo->create(['contenttype' => $contenttypeslug, 'status' => $contentType['default_status']]);
        } else {
            $content = $repo->find($id);
            if ($content === false) {
                // Record not found, advise and redirect to the dashboard
                $this->flashes()->error(Trans::__('contenttypes.generic.not-existing', ['%contenttype%' => $contenttypeslug]));

                return $this->redirectToRoute('dashboard');
            }
        }

        // We're doing a GET
        $duplicate = $request->query->get('duplicate', false);
        $context = $this->recordEdit()->action($content, $content->getContenttype(), $duplicate);
        $context['file_matcher'] = $this->app['filesystem.matcher'];

        // Get the editreferrer
        $referrer = $this->getEditReferrer($request);
        if ($referrer) {
            $content['editreferrer'] = $referrer;
        }

        return $this->render('@bolt/editcontent/editcontent.twig', $context);
    }

    /**
     * Content type overview page.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function overview(Request $request, $contenttypeslug)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (!$this->isAllowed('contenttype:' . $contenttypeslug)) {
            $this->flashes()->error(Trans::__('general.phrase.access-denied-privilege-view-page'));

            return $this->redirectToRoute('dashboard');
        }

        $taxonomy = null;
        foreach (array_keys($this->getOption('taxonomy', [])) as $taxonomyKey) {
            if ($request->query->get('taxonomy-' . $taxonomyKey)) {
                $taxonomy[$taxonomyKey] = $request->query->get('taxonomy-' . $taxonomyKey);
            }
        }

        $options = (new ListingOptions())
            ->setOrder($request->query->get('order'))
            ->setPage($request->query->get('page_' . $contenttypeslug))
            ->setFilter($request->query->get('filter'))
            ->setTaxonomies($taxonomy)
        ;

        $context = [
            'contenttype'     => $this->getContentType($contenttypeslug),
            'multiplecontent' => $this->recordListing()->action($contenttypeslug, $options),
            'filter'          => array_merge((array) $taxonomy, (array) $options->getFilter()),
            'permissions'     => $this->getContentTypeUserPermissions($contenttypeslug, $this->users()->getCurrentUser()),
        ];

        return $this->render('@bolt/overview/overview.twig', $context);
    }

    /**
     * Get related records.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param integer $id              The ID
     *
     * @return \Bolt\Response\TemplateResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function related(Request $request, $contenttypeslug, $id)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes' for Editors.
        if (!$this->isAllowed('contenttype:' . $contenttypeslug)) {
            $this->flashes()->error(Trans::__('general.phrase.access-denied-privilege-edit-record'));

            return $this->redirectToRoute('dashboard');
        }

        // Get content record, and the contenttype config from $contenttypeslug
        $content = $this->getContent($contenttypeslug, ['id' => $id]);
        $contenttype = $this->getContentType($contenttypeslug);

        // Get relations
        $showContentType = null;
        $relations = null;
        if (isset($contenttype['relations'])) {
            $relations = $contenttype['relations'];

            // Which related contenttype is to be shown?
            // If non is selected or selection does not exist, take the first one
            $showSlug = $request->query->get('show');
            if (!isset($relations[$showSlug])) {
                reset($relations);
                $showSlug = key($relations);
            }

            foreach (array_keys($relations) as $relatedslug) {
                $relatedtype = $this->getContentType($relatedslug);

                if ($relatedtype['slug'] == $showSlug) {
                    $showContentType = $relatedtype;
                }

                $relations[$relatedslug] = [
                    'name'   => Trans::__($relatedtype['name']),
                    'active' => ($relatedtype['slug'] === $showSlug),
                ];
            }
        }

        $context = [
            'id'               => $id,
            'name'             => Trans::__($contenttype['singular_name']),
            'title'            => $content['title'],
            'contenttype'      => $contenttype,
            'relations'        => $relations,
            'show_contenttype' => $showContentType,
            'related_content'  => $relations === null ? null : $content->related($showContentType['slug']),
            'permissions'      => $this->getContentTypeUserPermissions($contenttypeslug, $this->users()->getCurrentUser()),
        ];

        return $this->render('@bolt/relatedto/relatedto.twig', $context);
    }

    /**
     * Check that the user is allowed to edit the record.
     *
     * @param string  $contenttypeslug
     * @param integer $id
     *
     * @return bool|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function checkEditAccess($contenttypeslug, $id)
    {
        // Is the record new or existing
        $new = empty($id) ?: false;

        /*
         * Check the user is allowed to create/edit this record, based on:
         *     contenttype-all:
         *     contenttype-default:
         *     contenttypes:
         *         edit: []
         *         create: []
         */
        $perm = $new ? "contenttype:$contenttypeslug:create" : "contenttype:$contenttypeslug:edit:$id";
        if (!$this->isAllowed($perm)) {
            $action = $new ? 'create' : 'edit';
            $this->flashes()->error(Trans::__("You do not have the right privileges to $action that record."));

            return $this->redirectToRoute('dashboard');
        }

        return false;
    }

    /**
     * Set the editreferrer in twig if it was not set yet.
     *
     * @param Request $request
     *
     * @return string|null
     */
    private function getEditReferrer(Request $request)
    {
        $tmp = parse_url($request->server->get('HTTP_REFERER'));

        $referrer = $tmp['path'];
        if (!empty($tmp['query'])) {
            $referrer .= '?' . $tmp['query'];
        }

        if (strpos($referrer, '/overview/') !== false || ($referrer === $this->generateUrl('dashboard') . '/')) {
            if ($this->getOption('general/compatibility/twig_globals', true)) {
                $this->app['twig']->addGlobal('editreferrer', $referrer);
            }

            return $referrer;
        }

        return null;
    }

    /**
     * @return \Bolt\Storage\ContentRequest\Edit
     */
    protected function recordEdit()
    {
        return $this->app['storage.request.edit'];
    }

    /**
     * @return \Bolt\Storage\ContentRequest\Listing
     */
    protected function recordListing()
    {
        return $this->app['storage.request.listing'];
    }

    /**
     * @return \Bolt\Storage\ContentRequest\Save
     */
    protected function recordSave()
    {
        return $this->app['storage.request.save'];
    }
}
