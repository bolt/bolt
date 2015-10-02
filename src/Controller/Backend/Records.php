<?php
namespace Bolt\Controller\Backend;

use Bolt\Storage\Entity\Content;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend controller for record manipulation routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Records extends BackendBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->method('GET|POST');

        $c->get('/content/deletecontent/{contenttypeslug}/{id}', 'delete')
            ->bind('deletecontent');

        $c->match('/editcontent/{contenttypeslug}/{id}', 'edit')
            ->bind('editcontent')
            ->assert('id', '\d*')
            ->value('id', '');

        $c->post('/content/{action}/{contenttypeslug}/{id}', 'modify')
            ->bind('contentaction');

        $c->get('/overview/{contenttypeslug}', 'overview')
            ->bind('overview');

        $c->get('/relatedto/{contenttypeslug}/{id}', 'related')
            ->bind('relatedto')
            ->assert('id', '\d*');
    }

    /**
     * Delete a record.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, $contenttypeslug, $id)
    {
        $ids = explode(',', $id);
        $contenttype = $this->getContentType($contenttypeslug);

        foreach ($ids as $id) {
            $content = $this->getContent($contenttype['slug'], ['id' => $id, 'status' => '!undefined']);
            $title = $content->getTitle();

            if (!$this->isAllowed("contenttype:$contenttypeslug:delete:$id")) {
                $this->flashes()->error(Trans::__('Permission denied', []));
            } elseif ($this->checkAntiCSRFToken() && $this->app['storage']->deleteContent($contenttypeslug, $id)) {
                $this->flashes()->info(Trans::__("Content '%title%' has been deleted.", ['%title%' => $title]));
            } else {
                $this->flashes()->info(Trans::__("Content '%title%' could not be deleted.", ['%title%' => $title]));
            }
        }

        return $this->redirectToRoute('overview', ['contenttypeslug' => $contenttypeslug]);
    }

    /**
     * Edit a record, or create a new one.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param integer $id              The content ID
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function edit(Request $request, $contenttypeslug, $id)
    {
        // Is the record new or existing
        $new = empty($id);

        // Test the access control
        if ($response = $this->checkEditAccess($request, $contenttypeslug, $id)) {
            return $response;
        }

        // Set the editreferrer in twig if it was not set yet.
        $this->setEditReferrer($request);

        // Get the Contenttype obejct
        $contenttype = $this->getContentType($contenttypeslug);

        // Save the POSTed record
        if ($request->isMethod('POST')) {
            $formValues = $request->request->all();
            $returnTo = $request->get('returnto');
            $editReferrer = $request->get('editreferrer');

            return $this->recordModifier()->handleSaveRequest($formValues, $contenttype, $id, $new, $returnTo, $editReferrer);
        }

        // Get the record
        $repo = $this->getRepository($contenttypeslug);
        if ($new) {
            $content = $repo->create(['contenttype' => $contenttypeslug, 'status' => $contenttype['default_status']]);
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
        $context = $this->recordModifier()->handleEditRequest($content, $contenttype, $duplicate);

        return $this->render('editcontent/editcontent.twig', $context);
    }

    /**
     * Perform an action on a Contenttype record.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param integer $id              The content ID
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function modify(Request $request, $action, $contenttypeslug, $id)
    {
        if ($action === 'delete') {
            return $this->delete($request, $contenttypeslug, $id);
        }

        // This shoudln't happen
        if (!$this->getContentType($contenttypeslug)) {
            $this->flashes()->error(Trans::__('Attempt to modify invalid Contenttype.'));

            return $this->redirectToRoute('dashboard');
        }

        // Map actions to new statuses
        $actionStatuses = [
            'held'    => 'held',
            'publish' => 'published',
            'draft'   => 'draft',
        ];
        // Map actions to requred permission
        $actionPermissions = [
            'publish' => 'publish',
            'held'    => 'depublish',
            'draft'   => 'depublish',
        ];

        if (!isset($actionStatuses[$action])) {
            $this->flashes()->error(Trans::__('No such action for content.'));

            return $this->redirectToRoute('overview', ['contenttypeslug' => $contenttypeslug]);
        }

        $newStatus = $actionStatuses[$action];
        $content = $this->getContent("$contenttypeslug/$id");
        $title = $content->getTitle();

        if (!$this->isAllowed("contenttype:$contenttypeslug:{$actionPermissions[$action]}:$id") ||
        !$this->users()->isContentStatusTransitionAllowed($content['status'], $newStatus, $contenttypeslug, $id)) {
            $this->flashes()->error(Trans::__('You do not have the right privileges to %ACTION% that record.', ['%ACTION%' => $actionPermissions[$action]]));

            return $this->redirectToRoute('overview', ['contenttypeslug' => $contenttypeslug]);
        }

        if ($this->app['storage']->updateSingleValue($contenttypeslug, $id, 'status', $newStatus)) {
            $this->flashes()->info(Trans::__("Content '%title%' has been changed to '%newStatus%'", ['%title%' => $title, '%newStatus%' => $newStatus]));
        } else {
            $this->flashes()->info(Trans::__("Content '%title%' could not be modified.", ['%title%' => $title]));
        }

        return $this->redirectToRoute('overview', ['contenttypeslug' => $contenttypeslug]);
    }

    /**
     * Content type overview page.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function overview(Request $request, $contenttypeslug)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (!$this->isAllowed('contenttype:' . $contenttypeslug)) {
            $this->flashes()->error(Trans::__('You do not have the right privileges to view that page.'));

            return $this->redirectToRoute('dashboard');
        }

        // Order has to be set carefully. Either set it explicitly when the user
        // sorts, or fall back to what's defined in the contenttype. Except for
        // a ContentType that has a "grouping taxonomy", as that should override
        // it. That exception state is handled by the query OrderHandler.
        $contenttype = $this->getContentType($contenttypeslug);
        $contentparameters = ['paging' => true, 'hydrate' => true];
        $contentparameters['order'] = $request->query->get('order', $contenttype['sort']);
        $contentparameters['page'] = $request->query->get('page');

        $filter = [];
        if ($request->query->get('filter')) {
            $contentparameters['filter'] = $request->query->get('filter');
            $filter[] = $request->query->get('filter');
        }

        // Set the amount of items to show per page.
        if (!empty($contenttype['recordsperpage'])) {
            $contentparameters['limit'] = $contenttype['recordsperpage'];
        } else {
            $contentparameters['limit'] = $this->getOption('general/recordsperpage');
        }

        // Perhaps also filter on taxonomies
        foreach (array_keys($this->getOption('taxonomy', [])) as $taxonomykey) {
            if ($request->query->get('taxonomy-' . $taxonomykey)) {
                $contentparameters[$taxonomykey] = $request->query->get('taxonomy-' . $taxonomykey);
                $filter[] = $request->query->get('taxonomy-' . $taxonomykey);
            }
        }

        $multiplecontent = $this->getContent($contenttypeslug, $contentparameters);

        $context = [
            'contenttype'     => $contenttype,
            'multiplecontent' => $multiplecontent,
            'filter'          => $filter,
            'permissions'     => $this->getContentTypeUserPermissions($contenttypeslug, $this->users()->getCurrentUser())
        ];

        return $this->render('overview/overview.twig', $context);
    }

    /**
     * Get related records.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param integer $id              The ID
     *
     * @return \Bolt\Response\BoltResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function related(Request $request, $contenttypeslug, $id)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes' for Editors.
        if (!$this->isAllowed('contenttype:' . $contenttypeslug)) {
            $this->flashes()->error(Trans::__('You do not have the right privileges to edit that record.'));

            return $this->redirectToRoute('dashboard');
        }

        // Get content record, and the contenttype config from $contenttypeslug
        $content = $this->getContent($contenttypeslug, ['id' => $id]);
        $contenttype = $this->getContentType($contenttypeslug);

        // Get relations
        $showContenttype = null;
        $relations = null;
        if (isset($contenttype['relations'])) {
            $relations = $contenttype['relations'];

            // Which related contenttype is to be shown?
            // If non is selected or selection does not exist, take the first one
            $showSlug = $request->get('show') ? $request->get('show') : null;
            if (!isset($relations[$showSlug])) {
                reset($relations);
                $showSlug = key($relations);
            }

            foreach (array_keys($relations) as $relatedslug) {
                $relatedtype = $this->getContentType($relatedslug);

                if ($relatedtype['slug'] == $showSlug) {
                    $showContenttype = $relatedtype;
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
            'show_contenttype' => $showContenttype,
            'related_content'  => is_null($relations) ? null : $content->related($showContenttype['slug']),
            'permissions'      => $this->getContentTypeUserPermissions($contenttypeslug, $this->users()->getCurrentUser())
        ];

        return $this->render('relatedto/relatedto.twig', $context);
    }

    /**
     * Check that the user has a valid GSRF token and the required access control
     * to action the record.
     *
     * @param Request $request
     * @param string  $contenttypeslug
     * @param integer $id
     *
     * @return bool|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    private function checkEditAccess(Request $request, $contenttypeslug, $id)
    {
        // Is the record new or existing
        $new = empty($id) ?: false;

        // Check for a valid CSRF token
        if ($request->isMethod('POST') && !$this->checkAntiCSRFToken()) {
            $this->app->abort(Response::HTTP_BAD_REQUEST, Trans::__('Something went wrong'));
        }

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
     * @return void
     */
    private function setEditReferrer(Request $request)
    {
        $tmp = parse_url($request->server->get('HTTP_REFERER'));

        $tmpreferrer = $tmp['path'];
        if (!empty($tmp['query'])) {
            $tmpreferrer .= '?' . $tmp['query'];
        }

        if (strpos($tmpreferrer, '/overview/') !== false || ($tmpreferrer === $this->resources()->getUrl('bolt'))) {
            $this->app['twig']->addGlobal('editreferrer', $tmpreferrer);
        }
    }

    /**
     * @return \Bolt\Storage\RecordModifier
     */
    protected function recordModifier()
    {
        return $this->app['storage.record_modifier'];
    }
}
