<?php

namespace Bolt\Storage;

use Bolt\Exception\AccessControlException;
use Bolt\Helpers\Input;
use Bolt\Storage\Entity\Content;
use Bolt\Translation\Translator as Trans;
use Carbon\Carbon;
use Cocur\Slugify\Slugify;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Helper class for \Bolt\Controller\Backend\Records routes.
 *
 * Prior to v2.3 this functionality primarily existed in the monolithic
 * Bolt\Controllers\Backend class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RecordModifier
{
    /** @var Application $app */
    private $app;

    /**
     * Constructor function.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Do the save for a POSTed record.
     *
     * @param array   $formValues
     * @param array   $contenttype  The contenttype data
     * @param integer $id           The record ID
     * @param boolean $new          If TRUE this is a new record
     * @param string  $returnTo
     * @param string  $editReferrer
     *
     * @return Response
     */
    public function handleSaveRequest(array $formValues, array $contenttype, $id, $new, $returnTo, $editReferrer)
    {
        $contenttypeslug = $contenttype['slug'];
        $repo = $this->app['storage']->getRepository($contenttypeslug);

        // If we have an ID now, this is an existing record
        if ($id) {
            $content = $repo->find($id);
            $oldContent = clone $content;
            $oldStatus = $content['status'];
        } else {
            $content = $repo->create(['contenttype' => $contenttypeslug, 'status' => $contenttype['default_status']]);
            $oldContent = null;
            $oldStatus = '';
        }

        // Don't allow spoofing the ID.
        if ($content->getId() !== null && (integer) $id !== $content->getId()) {
            if ($returnTo === 'ajax') {
                throw new AccessControlException("Don't try to spoof the id!");
            }
            $this->app['logger.flash']->error("Don't try to spoof the id!");

            return new RedirectResponse($this->generateUrl('dashboard'));
        }

        // Set the POSTed values in the entity object
        $this->setPostedValues($content, $formValues, $contenttype);

        // To check whether the status is allowed, we act as if a status
        // *transition* were requested.
        $statusOK = $this->app['users']->isContentStatusTransitionAllowed($oldStatus, $content->getStatus(), $contenttypeslug, $id);
        if ($statusOK) {
            // Get the associated record change comment
            $comment = isset($formValues['changelog-comment']) ? $formValues['changelog-comment'] : '';

            // Save the record
            return $this->saveContentRecord($content, $oldContent, $contenttype, $new, $comment, $returnTo, $editReferrer);
        } else {
            $this->app['logger.flash']->error(Trans::__('contenttypes.generic.error-saving', ['%contenttype%' => $contenttypeslug]));
            $this->app['logger.system']->error('Save error: ' . $content->getTitle(), ['event' => 'content']);
        }
    }

    /**
     * Set a Contenttype record values from a HTTP POST.
     *
     * @param Content $content
     * @param array   $formValues
     * @param array   $contentType
     *
     * @throws AccessControlException
     */
    private function setPostedValues(Content $content, $formValues, $contentType)
    {
        // Ensure all fields have valid values
        $formValues = $this->setSuccessfulControlValues($formValues, $contentType['fields']);
        $formValues = Input::cleanPostedData($formValues);
        unset($formValues['contenttype']);

        if ($id = $content->getId()) {
            // Owner is set explicitly, is current user is allowed to do this?
            if (isset($formValues['ownerid']) && (integer) $formValues['ownerid'] !== $content->getOwnerid()) {
                if (!$this->app['permissions']->isAllowed("contenttype:{$contentType['slug']}:change-ownership:$id")) {
                    throw new AccessControlException('Changing ownership is not allowed.');
                }
                $content->setOwnerid($formValues['ownerid']);
            }
        } else {
            $user = $this->app['users']->getCurrentUser();
            $content->setOwnerid($user['id']);
        }

        // Make sure we have a proper status.
        if (!in_array($formValues['status'], ['published', 'timed', 'held', 'draft'])) {
            if ($status = $content->getStatus()) {
                $formValues['status'] = $status;
            } else {
                $formValues['status'] = 'draft';
            }
        }

        // Set the object values appropriately
        foreach ($formValues as $name => $value) {
            if ($name === 'relation') {
                $this->setPostedRelations($content, $formValues);
            } elseif ($name === 'taxonomy') {
                $this->setPostedTaxonomies($content, $formValues);
            } else {
                $content->set($name, empty($value) ? null : $value);
            }
        }
    }

    /**
     * Convert POST relationship values to an array of Entity objects keyed by
     * ContentType.
     *
     * @param Content    $content
     * @param array|null $formValues
     */
    private function setPostedRelations(Content $content, $formValues)
    {
        if (!isset($formValues['relation'])) {
            return;
        }

        $entities = [];
        foreach ($formValues['relation'] as $contentType => $relations) {
            $repo = $this->app['storage']->getRepository($contentType);
            foreach ($relations as $id) {
                if ($relation = $repo->find($id)) {
                    $entities[$contentType][] = $relation;
                }
            }
        }
        $content->setRelation($entities);
    }

    /**
     * Set valid POST taxonomies.
     *
     * @param Content    $content
     * @param array|null $formValues
     */
    private function setPostedTaxonomies(Content $content, $formValues)
    {
        if (!isset($formValues['taxonomy'])) {
            return;
        }
        $content->setTaxonomy($formValues['taxonomy']);
    }

    /**
     * Commit the record to the database.
     *
     * @param Content      $content
     * @param Content|null $oldContent
     * @param array        $contentType
     * @param boolean      $new
     * @param string       $comment
     * @param string       $returnTo
     * @param string       $editReferrer
     *
     * @return Response
     */
    private function saveContentRecord(Content $content, $oldContent, array $contentType, $new, $comment, $returnTo, $editReferrer)
    {
        // Save the record
        $repo = $this->app['storage']->getRepository($contentType['slug']);
        $repo->save($content);
        $id = $content->getId();

        // Create the change log entry if configured
        $this->logChange($contentType, $content->getId(), $content, $oldContent, $comment);

        // Log the change
        if ($new) {
            $this->app['logger.flash']->success(Trans::__('contenttypes.generic.saved-new', ['%contenttype%' => $contentType['slug']]));
            $this->app['logger.system']->info('Created: ' . $content->getTitle(), ['event' => 'content']);
        } else {
            $this->app['logger.flash']->success(Trans::__('contenttypes.generic.saved-changes', ['%contenttype%' => $contentType['slug']]));
            $this->app['logger.system']->info('Saved: ' . $content->getTitle(), ['event' => 'content']);
        }

        /*
         * We now only get a returnto parameter if we are saving a new
         * record and staying on the same page, i.e. "Save {contenttype}"
         */
        if ($returnTo) {
            if ($returnTo === 'new') {
                return new RedirectResponse($this->generateUrl('editcontent', [
                    'contenttypeslug' => $contentType['slug'],
                    'id'              => $id,
                    '#'               => $returnTo,
                ]));
            } elseif ($returnTo === 'saveandnew') {
                return new RedirectResponse($this->generateUrl('editcontent', [
                    'contenttypeslug' => $contentType['slug'],
                    '#'               => $returnTo,
                ]));
            } elseif ($returnTo === 'ajax') {
                return $this->createJsonUpdate($content, true);
            } elseif ($returnTo === 'test') {
                return $this->createJsonUpdate($content, false);
            }
        }

        // No returnto, so we go back to the 'overview' for this contenttype.
        // check if a pager was set in the referrer - if yes go back there
        if ($editReferrer) {
            return new RedirectResponse($editReferrer);
        } else {
            return new RedirectResponse($this->generateUrl('overview', ['contenttypeslug' => $contentType['slug']]));
        }
    }

    /**
     * Add successful control values to request values, and do needed corrections.
     *
     * @see http://www.w3.org/TR/html401/interact/forms.html#h-17.13.2
     *
     * @param array $formValues
     * @param array $fields
     *
     * @return array
     */
    private function setSuccessfulControlValues(array $formValues, $fields)
    {
        foreach ($fields as $key => $values) {
            if (isset($formValues[$key])) {
                if ($values['type'] === 'float') {
                    // We allow ',' and '.' as decimal point and need '.' internally
                    $formValues[$key] = str_replace(',', '.', $formValues[$key]);
                }
            } else {
                if ($values['type'] === 'select' && isset($values['multiple']) && $values['multiple'] === true) {
                    $formValues[$key] = [];
                } elseif ($values['type'] === 'checkbox') {
                    $formValues[$key] = 0;
                }
            }
        }

        return $formValues;
    }

    /**
     * Build a valid AJAX response for in-place saves that account for pre/post
     * save events.
     *
     * @param Content $content
     * @param boolean $flush
     *
     * @return JsonResponse
     */
    private function createJsonUpdate(Content $content, $flush)
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
        if ($flush) {
            Response::closeOutputBuffers(0, false);
        }

        $val = $content->toArray();

        if (isset($val['datechanged'])) {
            $val['datechanged'] = (new Carbon($val['datechanged']))->toIso8601String();
        }

        // Adjust decimal point as some locales use a comma and… JavaScript
        $lc = localeconv();
        $fields = $this->app['config']->get('contenttypes/' . $content->getContenttype() . '/fields');
        foreach ($fields as $key => $values) {
            if ($values['type'] === 'float' && $lc['decimal_point'] === ',') {
                $val[$key] = str_replace('.', ',', $val[$key]);
            }
        }

        // Unset flashbag for ajax
        $this->app['logger.flash']->clear();

        return new JsonResponse($val);
    }

    /**
     * Add a change log entry to track the change.
     *
     * @param string       $contentType
     * @param integer      $contentId
     * @param Content      $newContent
     * @param Content|null $oldContent
     * @param string|null  $comment
     */
    private function logChange($contentType, $contentId, $newContent = null, $oldContent = null, $comment = null)
    {
        $type = $oldContent ? 'Update' : 'Insert';

        $this->app['logger.change']->info(
            $type . ' record',
            [
                'action'      => strtoupper($type),
                'contenttype' => $contentType,
                'id'          => $contentId,
                'new'         => $newContent ? $newContent->toArray() : null,
                'old'         => $oldContent ? $oldContent->toArray() : null,
                'comment'     => $comment
            ]
        );
    }

    /**
     * Do the edit form for a record.
     *
     * @param Content $content     A content record
     * @param array   $contenttype The contenttype data
     * @param boolean $duplicate   If TRUE create a duplicate record
     *
     * @return array
     */
    public function handleEditRequest(Content $content, array $contenttype, $duplicate)
    {
        $contenttypeSlug = $contenttype['slug'];
        $new = $content->getId() === null ?: false;
        $oldStatus = $content->getStatus();
        $allStatuses = ['published', 'held', 'draft', 'timed'];
        $allowedStatuses = [];

        foreach ($allStatuses as $status) {
            if ($this->app['users']->isContentStatusTransitionAllowed($oldStatus, $status, $contenttypeSlug, $content->getId())) {
                $allowedStatuses[] = $status;
            }
        }

        // For duplicating a record, clear base field values.
        if ($duplicate) {
            $content->setId('');
            $content->setSlug('');
            $content->setDatecreated('');
            $content->setDatepublish('');
            $content->setDatedepublish(null);
            $content->setDatechanged('');
            $content->setUsername('');
            $content->setOwnerid('');

            $this->app['logger.flash']->info(Trans::__('contenttypes.generic.duplicated-finalize', ['%contenttype%' => $contenttypeSlug]));
        }

        // Set the users and the current owner of this content.
        if ($new || $duplicate) {
            // For brand-new and duplicated items, the creator becomes the owner.
            $contentowner = $this->app['users']->getCurrentUser();
        } else {
            // For existing items, we'll just keep the current owner.
            $contentowner = $this->app['users']->getUser($content->getOwnerid());
        }

        // Test write access for uploadable fields.
        $contenttype['fields'] = $this->setCanUpload($contenttype['fields']);
        if ($templatefields = $content->getTemplatefields()) {
            $this->setCanUpload($templatefields->getContenttype());
        }

        // Build context for Twig.
        $contextCan = [
            'upload'             => $this->app['users']->isAllowed('files:uploads'),
            'publish'            => $this->app['users']->isAllowed('contenttype:' . $contenttypeSlug . ':publish:' . $content->getId()),
            'depublish'          => $this->app['users']->isAllowed('contenttype:' . $contenttypeSlug . ':depublish:' . $content->getId()),
            'change_ownership'   => $this->app['users']->isAllowed('contenttype:' . $contenttypeSlug . ':change-ownership:' . $content->getId()),
        ];
        $contextHas = [
            'incoming_relations' => is_array($content->relation),
            'relations'          => isset($contenttype['relations']),
            'tabs'               => $contenttype['groups'] !== false,
            'taxonomy'           => isset($contenttype['taxonomy']),
            'templatefields'     => $templatefields ? true : false,
        ];
        $contextValues = [
            'datepublish'        => $this->getPublishingDate($content->getDatepublish(), true),
            'datedepublish'      => $this->getPublishingDate($content->getDatedepublish()),
        ];
        $context = [
            'contenttype'        => $contenttype,
            'content'            => $content,
            'allowed_status'     => $allowedStatuses,
            'contentowner'       => $contentowner,
            'fields'             => $this->app['config']->fields->fields(),
            'fieldtemplates'     => $this->getTempateFieldTemplates($contenttype, $content),
            'fieldtypes'         => $this->getUsedFieldtypes($contenttype, $content, $contextHas),
            'groups'             => $this->createGroupTabs($contenttype, $contextHas),
            'can'                => $contextCan,
            'has'                => $contextHas,
            'values'             => $contextValues,
            'relations_list'     => $this->getRelationsList($contenttype),
        ];

        return $context;
    }

    /**
     * Convert POST relationship values to an array of Entity objects keyed by
     * ContentType.
     *
     * @param array $contenttype
     *
     * @return array
     */
    private function getRelationsList(array $contenttype)
    {
        $list = [];
        if (!isset($contenttype['relations']) || !is_array($contenttype['relations'])) {
            return $list;
        }

        foreach ($contenttype['relations'] as $contentType => $relation) {
            $repo = $this->app['storage']->getRepository($contentType);
            $list[$contentType] = $repo->getSelectList($contentType, $relation['order']);
        }

        return $list;
    }

    /**
     * Test write access for uploadable fields.
     *
     * @param array $fields
     *
     * @return array
     */
    private function setCanUpload($fields)
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
     * Determine which templates will result in templatefields.
     *
     * @param array   $contenttype
     * @param Content $content
     *
     * @return array
     */
    private function getTempateFieldTemplates(array $contenttype, Content $content)
    {
        $templateFieldTemplates = [];
        $templateFieldsConfig = $this->app['config']->get('theme/templatefields');

        if ($templateFieldsConfig) {
            $templateFieldTemplates = array_keys($templateFieldsConfig);
            // Special case for default template
            $toRepair = [];
            foreach ($contenttype['fields'] as $name => $field) {
                if ($field['type'] === 'templateselect' && !empty($content->values[$name])) {
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
     * Converts database publishing/depublishing dates to values to be used in Twig.
     *
     * @param string $date
     * @param bool   $setNowOnEmpty
     *
     * @return array
     */
    private function getPublishingDate($date, $setNowOnEmpty = false)
    {
        if ($setNowOnEmpty and $date === '') {
            return date('Y-m-d H:i:s');
        } elseif ($date === '1900-01-01 00:00:00') {
            return '';
        } else {
            return $date;
        }
    }

    /**
     * Generate tab groups.
     *
     * @param array $contenttype
     * @param array $has
     *
     * @return array
     */
    private function createGroupTabs(array $contenttype, array $has)
    {
        $groups = [];
        $groupIds = [];

        $addGroup = function ($group, $label) use (&$groups, &$groupIds) {
            $nr = count($groups) + 1;
            $id = rtrim('tab-' . Slugify::create()->slugify($group), '-');
            if (isset($groupIds[$id]) || $id === 'tab') {
                $id .= '-' . $nr;
            }
            $groups[$group] = [
                'label'     => $label,
                'id'        => $id,
                'is_active' => $nr === 1,
                'fields'    => [],
            ];
            $groupIds[$id] = 1;
        };

        foreach ($contenttype['groups'] ? $contenttype['groups'] : ['ungrouped'] as $group) {
            if ($group === 'ungrouped') {
                $addGroup($group, Trans::__('contenttypes.generic.group.ungrouped'));
            } elseif ($group !== 'meta' && $group !== 'relations' && $group !== 'taxonomy') {
                $default = ['DEFAULT' => ucfirst($group)];
                $key = ['contenttypes', $contenttype['slug'], 'group', $group];
                $addGroup($group, Trans::__($key, $default));
            }
        }

        if ($has['relations'] || $has['incoming_relations']) {
            $addGroup('relations', Trans::__('contenttypes.generic.group.relations'));
            $groups['relations']['fields'][] = '*relations';
        }

        if ($has['taxonomy'] || (is_array($contenttype['groups']) && in_array('taxonomy', $contenttype['groups']))) {
            $addGroup('taxonomy', Trans::__('contenttypes.generic.group.taxonomy'));
            $groups['taxonomy']['fields'][] = '*taxonomy';
        }

        if ($has['templatefields'] || (is_array($contenttype['groups']) && in_array('template', $contenttype['groups']))) {
            $addGroup('template', Trans::__('Template'));
            $groups['template']['fields'][] = '*template';
        }

        $addGroup('meta', Trans::__('contenttypes.generic.group.meta'));
        $groups['meta']['fields'][] = '*meta';

        // References fields in tab group data.
        foreach ($contenttype['fields'] as $fieldname => $field) {
            $groups[$field['group']]['fields'][] = $fieldname;
        }

        return $groups;
    }

    /**
     * Create a list of fields types used in regular, template and virtual fields.
     *
     * @param array   $contenttype
     * @param Content $content
     * @param array   $has
     *
     * @return array
     */
    private function getUsedFieldtypes(array $contenttype, Content $content, array $has)
    {
        $fieldtypes = [
            'meta' => true
        ];

        foreach ([$contenttype['fields'], $content->getTemplatefields() ?: []] as $fields) {
            foreach ($fields as $field) {
                $fieldtypes[$field['type']] = true;
            }
        }

        if ($has['relations'] || $has['incoming_relations']) {
            $fieldtypes['relationship'] = true;
        }

        if ($has['taxonomy'] || (is_array($contenttype['groups']) && in_array('taxonomy', $contenttype['groups']))) {
            $fieldtypes['taxonomy'] = true;
        }

        if ($has['templatefields'] || (is_array($contenttype['groups']) && in_array('template', $contenttype['groups']))) {
            $fieldtypes['template'] = true;
        }

        return array_keys($fieldtypes);
    }

    /**
     * Shortcut for {@see UrlGeneratorInterface::generate}
     *
     * @param string $name          The name of the route
     * @param array  $params        An array of parameters
     * @param bool   $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string
     */
    private function generateUrl($name, $params = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        /** @var UrlGeneratorInterface $generator */
        $generator = $this->app['url_generator'];

        return $generator->generate($name, $params, $referenceType);
    }
}
