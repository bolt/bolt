<?php
namespace Bolt\Controllers\Backend;

use Bolt\Content;
use Bolt\Translation\Translator as Trans;
use Cocur\Slugify\Slugify;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backend controller for record manipulation rotues.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Records extends BackendBase
{
    protected function addControllers(ControllerCollection $c)
    {
    }

    /*
     * Routes
     */

    /**
     * Delete a record.
     *
     * @param Request $request The Symfony Request
     * @param string  $contenttypeslug
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionDelete(Request $request, $contenttypeslug, $id)
    {
        $ids = explode(',', $id);
        $contenttype = $this->app['storage']->getContentType($contenttypeslug);

        foreach ($ids as $id) {
            $content = $this->getContent($contenttype['slug'] . '/' . $id);
            $title = $content->getTitle();

            if (!$this->isAllowed("contenttype:$contenttypeslug:delete:$id")) {
                $this->addFlash('error', Trans::__('Permission denied', array()));
            } elseif ($this->checkAntiCSRFToken() && $this->app['storage']->deleteContent($contenttypeslug, $id)) {
                $this->addFlash('info', Trans::__("Content '%title%' has been deleted.", array('%title%' => $title)));
            } else {
                $this->addFlash('info', Trans::__("Content '%title%' could not be deleted.", array('%title%' => $title)));
            }
        }

        return $this->redirectToRoute('overview', array('contenttypeslug' => $contenttypeslug));
    }

    /**
     * Edit a record, or create a new one.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param integer $id              The content ID
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionEdit(Request $request, $contenttypeslug, $id)
    {
        // Is the record new or existing
        $new = empty($id) ?: false;

        // Test the access control
        if ($response = $this->checkEditAccess($request, $contenttypeslug, $id)) {
            return $response;
        }

        // Set the editreferrer in twig if it was not set yet.
        $this->setEditReferrer();

        // Get the Contenttype obejct
        $contenttype = $this->app['storage']->getContentType($contenttypeslug);

        // Save the POSTed record
        if ($request->isMethod('POST')) {
            return $this->handleSaveRequest($contenttype, $contenttypeslug, $id, $new);
        }

        // We're doing a GET
        return $this->handleEditRequest($contenttype, $contenttypeslug, $id, $new);
    }

    /**
     * Perform an action on a Contenttype record.
     *
     * @param Request $request The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param integer $id              The content ID
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionModify(Request $request, $action, $contenttypeslug, $id)
    {
        if ($action === 'delete') {
            return $this->actionDelete($contenttypeslug, $id);
        }

        // This shoudln't happen
        if (!$this->app['storage']->getContentType($contenttypeslug)) {
            $this->addFlash('error', Trans::__('Attempt to modify invalid Contenttype.'));

            return $this->redirectToRoute('dashboard');
        }

        // Map actions to new statuses
        $actionStatuses = array(
            'held'    => 'held',
            'publish' => 'published',
            'draft'   => 'draft',
        );

        if (!isset($actionStatuses[$action])) {
            $this->addFlash('error', Trans::__('No such action for content.'));

            return $this->redirectToRoute('overview', array('contenttypeslug' => $contenttypeslug));
        }

        $newStatus = $actionStatuses[$action];
        $content = $this->getContent("$contenttypeslug/$id");
        $title = $content->getTitle();

        if (!$this->isAllowed("contenttype:$contenttypeslug:edit:$id") ||
        !$this->app['users']->isContentStatusTransitionAllowed($content['status'], $newStatus, $contenttypeslug, $id)) {
            $this->addFlash('error', Trans::__('You do not have the right privileges to edit that record.'));

            return $this->redirectToRoute('overview', array('contenttypeslug' => $contenttypeslug));
        }

        if ($this->app['storage']->updateSingleValue($contenttypeslug, $id, 'status', $newStatus)) {
            $this->addFlash('info', Trans::__("Content '%title%' has been changed to '%newStatus%'", array('%title%' => $title, '%newStatus%' => $newStatus)));
        } else {
            $this->addFlash('info', Trans::__("Content '%title%' could not be modified.", array('%title%' => $title)));
        }

        return $this->redirectToRoute('overview', array('contenttypeslug' => $contenttypeslug));
    }

    /**
     * Content type overview page.
     *
     * @param Request $request The Symfony Request
     * @param string  $contenttypeslug The content type slug
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionOverview(Request $request, $contenttypeslug)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes'
        // for Editors.
        if (!$this->isAllowed('contenttype:' . $contenttypeslug)) {
            $this->addFlash('error', Trans::__('You do not have the right privileges to view that page.'));

            return $this->redirectToRoute('dashboard');
        }

        $contenttype = $this->getContentType($contenttypeslug);

        $filter = array();

        $contentparameters = array('paging' => true, 'hydrate' => true);

        // Order has to be set carefully. Either set it explicitly when the user
        // sorts, or fall back to what's defined in the contenttype. The exception
        // is a contenttype that has a "grouping taxonomy", because that should
        // override it. The exception is handled in $app['storage']->getContent().
        $contentparameters['order'] = $request->query->get('order', $contenttype['sort']);
        $contentparameters['page'] = $request->query->get('page');

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
        foreach (array_keys($this->getOption('taxonomy', array())) as $taxonomykey) {
            if ($request->query->get('taxonomy-' . $taxonomykey)) {
                $contentparameters[$taxonomykey] = $request->query->get('taxonomy-' . $taxonomykey);
                $filter[] = $request->query->get('taxonomy-' . $taxonomykey);
            }
        }

        $multiplecontent = $this->getContent($contenttypeslug, $contentparameters);

        $context = array(
            'contenttype'     => $contenttype,
            'multiplecontent' => $multiplecontent,
            'filter'          => $filter
        );

        return $this->render('overview/overview.twig', $context);
    }

    /**
     * Get related records.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param integer $id              The ID
     *
     * @return \Twig_Markup|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function actionRelated(Request $request, $contenttypeslug, $id)
    {
        // Make sure the user is allowed to see this page, based on 'allowed contenttypes' for Editors.
        if (!$this->isAllowed('contenttype:' . $contenttypeslug)) {
            $this->addFlash('error', Trans::__('You do not have the right privileges to edit that record.'));

            return $this->redirectToRoute('dashboard');
        }

        // Get content record, and the contenttype config from $contenttypeslug
        $content = $this->getContent($contenttypeslug, array('id' => $id));
        $contenttype = $this->app['storage']->getContentType($contenttypeslug);

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
                $relatedtype = $this->app['storage']->getContentType($relatedslug);

                if ($relatedtype['slug'] == $showSlug) {
                    $showContenttype = $relatedtype;
                }

                $relations[$relatedslug] = array(
                    'name'   => Trans::__($relatedtype['name']),
                    'active' => ($relatedtype['slug'] === $showSlug),
                );
            }
        }

        $context = array(
            'id'               => $id,
            'name'             => Trans::__($contenttype['singular_name']),
            'title'            => $content['title'],
            'contenttype'      => $contenttype,
            'relations'        => $relations,
            'show_contenttype' => $showContenttype,
            'related_content'  => is_null($relations) ? null : $content->related($showContenttype['slug']),
        );

        return $this->render('relatedto/relatedto.twig', $context);
    }

    /*
     * actionEditContent() Helper Functions
     */

    /**
     * Check that the user has a valid GSRF token and the required access control
     * to action the record.
     *
     * @param Request $request
     * @param string  $contenttypeslug
     * @param integer $id
     */
    private function checkEditAccess(Request $request, $contenttypeslug, $id)
    {
        // Is the record new or existing
        $new = empty($id) ?: false;

        // Check for a valid CSRF token
        if ($request->isMethod('POST') && !$this->checkAntiCSRFToken()) {
// FIXME
// $this->app->abort(Response::HTTP_BAD_REQUEST, Trans::__('Something went wrong'));
            return $this->redirectToRoute('dashboard', array(), Response::HTTP_BAD_REQUEST);
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
            $this->addFlash('error', Trans::__("You do not have the right privileges to $action that record."));

            return $this->redirectToRoute('dashboard');
        }

        return false;
    }

    /**
     * Set the editreferrer in twig if it was not set yet.
     *
     * @return void
     */
    private function setEditReferrer()
    {
// FIXME: use the shortcut instead
//$this->generateUrl();

        $tmp = parse_url($this->app['request']->server->get('HTTP_REFERER'));

        $tmpreferrer = $tmp['path'];
        if (!empty($tmp['query'])) {
            $tmpreferrer .= '?' . $tmp['query'];
        }

        if (strpos($tmpreferrer, '/overview/') !== false || ($tmpreferrer === $this->app['resources']->getUrl('bolt'))) {
            $this->app['twig']->addGlobal('editreferrer', $tmpreferrer);
        }
    }

    /**
     * Do the save for a POSTed record.
     *
     * @param Request $request
     * @param array   $contenttype The contenttype data
     * @param integer $id          The record ID
     * @param boolean $new         If TRUE this is a new record
     *
     * @return Response
     */
    private function handleSaveRequest($request, array $contenttype, $id, $new)
    {
        $contenttypeslug = $contenttype['slug'];

        // If we have an ID now, this is an existing record
        if ($id) {
            $content = $this->getContent($contenttypeslug, array('id' => $id));
            $oldStatus = $content['status'];
        } else {
            $content = $this->app['storage']->getContentObject($contenttypeslug);
            $oldStatus = '';
        }

        // Don't allow spoofing the $id.
        if (!empty($content['id']) && $id != $content['id']) {
            $this->addFlash('error', "Don't try to spoof the id!");

            return $this->redirectToRoute('dashboard');
        }

        // Ensure all fields have valid values
        $requestAll = $this->setSuccessfulControlValues($contenttype['fields']);

        // To check whether the status is allowed, we act as if a status
        // *transition* were requested.
        $content->setFromPost($requestAll, $contenttype);
        $newStatus = $content['status'];

        $statusOK = $this->app['users']->isContentStatusTransitionAllowed($oldStatus, $newStatus, $contenttypeslug, $id);
        if ($statusOK) {
            // Save the record
            return $this->saveContentRecord($request, $contenttype, $content, $new);
        } else {
            $this->addFlash('error', Trans::__('contenttypes.generic.error-saving', array('%contenttype%' => $contenttypeslug)));
            $this->app['logger.system']->error('Save error: ' . $content->getTitle(), array('event' => 'content'));
        }
    }

    /**
     * Commit the record to the database.
     *
     * @param Request $request
     * @param array   $contenttype
     * @param Content $content
     * @param boolean $new
     *
     * @return Response
     */
    private function saveContentRecord(Request $request, array $contenttype, Content $content, $new)
    {
        // Get the associated record change comment
        $comment = $request->request->get('changelog-comment');

        // Save the record
        $id = $this->app['storage']->saveContent($content, $comment);

        // Log the change
        if ($new) {
            $this->addFlash('success', Trans::__('contenttypes.generic.saved-new', array('%contenttype%' => $contenttype['slug'])));
            $this->app['logger.system']->info('Created: ' . $content->getTitle(), array('event' => 'content'));
        } else {
            $this->addFlash('success', Trans::__('contenttypes.generic.saved-changes', array('%contenttype%' => $contenttype['slug'])));
            $this->app['logger.system']->info('Saved: ' . $content->getTitle(), array('event' => 'content'));
        }

        /*
         * We now only get a returnto parameter if we are saving a new
         * record and staying on the same page, i.e. "Save {contenttype}"
         */
        if ($this->app['request']->get('returnto')) {
            $returnto = $this->app['request']->get('returnto');

            if ($returnto === 'new') {
                return $this->redirectToRoute('editcontent', array('contenttypeslug' => $contenttype['slug'], 'id' => $id), '#' . $this->app['request']->get('returnto'));
            } elseif ($returnto == 'saveandnew') {
                return $this->redirectToRoute('editcontent', array('contenttypeslug' => $contenttype['slug'], 'id' => 0), '#' . $this->app['request']->get('returnto'));
            } elseif ($returnto === 'ajax') {
                return $this->createJsonUpdate($contenttype, $id);
            }
        }

        // No returnto, so we go back to the 'overview' for this contenttype.
        // check if a pager was set in the referrer - if yes go back there
        $editreferrer = $this->app['request']->get('editreferrer');
        if ($editreferrer) {
            return $this->redirect($editreferrer);
        } else {
            return $this->redirectToRoute('overview', array('contenttypeslug' => $contenttype['slug']));
        }
    }

    /**
     * Add successful control values to request values, and do needed corrections.
     *
     * @see http://www.w3.org/TR/html401/interact/forms.html#h-17.13.2
     *
     * @param Request $request
     * @param array   $fields
     */
    private function setSuccessfulControlValues(Request $request, $fields)
    {
        $formValues = $request->request->all();

        foreach ($fields as $key => $values) {
            if (isset($formValues[$key])) {
                switch ($values['type']) {
                    case 'float':
                        // We allow ',' and '.' as decimal point and need '.' internally
                        $formValues[$key] = str_replace(',', '.', $formValues[$key]);
                        break;
                }
            } else {
                switch ($values['type']) {
                    case 'select':
                        if (isset($values['multiple']) && $values['multiple'] === true) {
                            $formValues[$key] = array();
                        }
                        break;

                    case 'checkbox':
                        $formValues[$key] = 0;
                        break;
                }
            }
        }

        return $formValues;
    }

    /**
     * Build a valid AJAX response for in-place saves that account for pre/post
     * save events.
     *
     * @param array   $contenttype
     * @param integer $id
     *
     * @return JsonResponse
     */
    private function createJsonUpdate($contenttype, $id)
    {
        /*
         * Flush any buffers from saveConent() dispatcher hooks
         * and make sure our JSON output is clean.
         *
         * Currently occurs due to exceptions being generated in the dispatchers
         * in \Bolt\Storage::saveContent()
         *     StorageEvents::PRE_SAVE
         *     StorageEvents::POST_SAVE
         */
        Response::closeOutputBuffers(0, false); // '0' should be the default output handler
//         if (ob_get_length()) {
//             ob_end_clean();
//         }

        // Get our record after POST_SAVE hooks are dealt with and return the JSON
        $content = $this->getContent($contenttype['slug'], array('id' => $id, 'returnsingle' => true));

        $val = array();

        foreach ($content->values as $key => $value) {
            // Some values are returned as \Twig_Markup and JSON can't deal with that
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    if (gettype($subvalue) == 'object' && get_class($subvalue) == 'Twig_Markup') {
                        $val[$key][$subkey] = (string) $subvalue;
                    }
                }
            } else {
                $val[$key] = $value;
            }
        }

        if (isset($val['datechanged'])) {
            $val['datechanged'] = date_format(new \DateTime($val['datechanged']), 'c');
        }

        $lc = localeconv();
        foreach ($contenttype['fields'] as $key => $values) {
            switch ($values['type']) {
                case 'float':
                    // Adjust decimal point dependent on locale
                    if ($lc['decimal_point'] === ',') {
                        $val[$key] = str_replace('.', ',', $val[$key]);
                    }
                    break;
            }
        }

        // Unset flashbag for ajax
        $this->getSession()->getFlashBag()->clear();

        return $this->json($val);
    }

    /**
     * Do the edit rendering for a record.
     *
     * @param array   $contenttype The contenttype data
     * @param integer $id          The record ID
     * @param boolean $new         If TRUE this is a new record
     *
     * @return Response
     */
    private function handleEditRequest(array $contenttype, $id, $new)
    {
        $contenttypeslug = $contenttype['slug'];

        if ($new) {
            $content = $this->app['storage']->getEmptyContent($contenttypeslug);
        } else {
            $content = $this->getContent($contenttypeslug, array('id' => $id));

            if (empty($content)) {
// FIXME
//return $this->app->abort(Response::HTTP_NOT_FOUND, Trans::__('contenttypes.generic.not-existing', array('%contenttype%' => $contenttypeslug)));
                return $this->redirectToRoute('dashboard', array(), Response::HTTP_NOT_FOUND);
            }
        }

        $oldStatus = $content['status'];
        $allStatuses = array('published', 'held', 'draft', 'timed');
        $allowedStatuses = array();
        foreach ($allStatuses as $status) {
            if ($this->app['users']->isContentStatusTransitionAllowed($oldStatus, $status, $contenttypeslug, $id)) {
                $allowedStatuses[] = $status;
            }
        }

        // For duplicating a record, clear base field values
        if ($duplicate = $this->app['request']->query->get('duplicate')) {
            $content->setValues(array(
                'id'            => '',
                'slug'          => '',
                'datecreated'   => '',
                'datepublish'   => '',
                'datedepublish' => null,
                'datechanged'   => '',
                'username'      => '',
                'ownerid'       => '',
            ));

            $this->addFlash('info', Trans::__('contenttypes.generic.duplicated-finalize', array('%contenttype%' => $contenttypeslug)));
        }

        // Set the users and the current owner of this content.
        if ($new || $duplicate) {
            // For brand-new and duplicated items, the creator becomes the owner.
            $contentowner = $this->getUser();
        } else {
            // For existing items, we'll just keep the current owner.
            $contentowner = $this->getUser($content['ownerid']);
        }

        // Test write access for uploadable fields
        $contenttype['fields'] = $this->setCanUpload($contenttype['fields']);
        if ((!empty($content['templatefields'])) && (!empty($content['templatefields']->contenttype['fields']))) {
            $content['templatefields']->contenttype['fields'] = $this->setCanUpload($content['templatefields']->contenttype['fields']);
        }

        // Determine which templates will result in templatefields
        $templateFieldTemplates = $this->getTempateFieldTemplates();

        // Information flags about what the record contains
        $info = array(
            'hasIncomingRelations' => is_array($content->relation),
            'hasRelations'         => isset($contenttype['relations']),
            'hasTabs'              => $contenttype['groups'] !== false,
            'hasTaxonomy'          => isset($contenttype['taxonomy']),
            'hasTemplateFields'    => $content->hasTemplateFields()
        );

        // Generate tab groups
        $groups = $this->createGroupTabs($contenttype, $content, $info);

        // Build context for Twig
        $context = array(
            'contenttype'    => $contenttype,
            'content'        => $content,
            'allowed_status' => $allowedStatuses,
            'contentowner'   => $contentowner,
            'fields'         => $this->app['config']->fields->fields(),
            'fieldtemplates' => $templateFieldTemplates,
            'can_upload'     => $this->isAllowed('files:uploads'),
            'groups'         => $groups,
            'has'            => array(
                'incoming_relations' => $info['hasIncomingRelations'],
                'relations'          => $info['hasRelations'],
                'tabs'               => $info['hasTabs'],
                'taxonomy'           => $info['hasTaxonomy'],
                'templatefields'     => $info['hasTemplateFields'],
            ),
        );

        // Render
        return $this->render('editcontent/editcontent.twig', $context);
    }

    /**
     * Test write access for uploadable fields
     *
     * @param array $fields
     *
     * @return array
     */
    private function setCanUpload(array $fields)
    {
        $filesystem = $this->app['filesystem']->getFilesystem();

        foreach ($fields as &$values) {
            if (isset($values['upload'])) {
                $values['canUpload'] = $filesystem->has($values['upload']) && $filesystem->getVisibility($values['upload']);
            } else {
                $values['canUpload'] = true;
            }
        }

        return $fields;
    }

    /**
     * Determine which templates will result in templatefields
     *
     * @param array   $contenttype
     * @param Content $content
     *
     * @return array
     */
    private function getTempateFieldTemplates(array $contenttype, Content $content)
    {
        $templateFieldTemplates = array();

        if ($templateFieldsConfig = $this->app['config']->get('theme/templatefields')) {
            $templateFieldTemplates = array_keys($templateFieldsConfig);
            // Special case for default template
            $toRepair = array();
            foreach ($contenttype['fields'] as $name => $field) {
                if ($field['type'] == 'templateselect' && !empty($content->values[$name])) {
                    $toRepair[$name] = $content->values[$name];
                    $content->setValue($name, '');
                }
            }
            if ($content->hasTemplateFields()) {
                $templateFieldTemplates[] = '';
            }

            foreach ($toRepair as $name => $value) {
                $content->setValue($name, $value);
            }
        }

        return $templateFieldTemplates;
    }

    /**
     * Generate tab groups
     *
     * @param array   $contenttype
     * @param Content $content
     * @param array   $info
     *
     * @return array
     */
    private function createGroupTabs(array $contenttype, Content $content, $info)
    {
        $groups = array();
        $groupIds = array();

        $addGroup = function ($group, $label) use (&$groups, &$groupIds) {
            $nr = count($groups) + 1;
            $id = rtrim('tab-' . Slugify::create()->slugify($group), '-');
            if (isset($groupIds[$id]) || $id === 'tab') {
                $id .= '-' . $nr;
            }
            $groups[$group] = array(
                'label'     => $label,
                'id'        => $id,
                'is_active' => $nr === 1,
            );
            $groupIds[$id] = 1;
        };

        foreach ($contenttype['groups'] ? $contenttype['groups'] : array('ungrouped') as $group) {
            if ($group === 'ungrouped') {
                $addGroup($group, Trans::__('contenttypes.generic.group.ungrouped'));
            } elseif ($group !== 'meta' && $group !== 'relations' && $group !== 'taxonomy') {
                $default = array('DEFAULT' => ucfirst($group));
                $key = array('contenttypes', $contenttype['slug'], 'group', $group);
                $addGroup($group, Trans::__($key, $default));
            }
        }

        if ($info['hasRelations'] || $info['hasIncomingRelations']) {
            $addGroup('relations', Trans::__('contenttypes.generic.group.relations'));
        }

        if ($info['hasTaxonomy'] || (is_array($contenttype['groups']) && in_array('taxonomy', $contenttype['groups']))) {
            $addGroup('taxonomy', Trans::__('contenttypes.generic.group.taxonomy'));
        }

        if ($info['hasTemplateFields'] || (is_array($contenttype['groups']) && in_array('template', $contenttype['groups']))) {
            $addGroup('template', Trans::__('Template'));
        }

        $addGroup('meta', Trans::__('contenttypes.generic.group.meta'));

        return $groups;
    }
}
