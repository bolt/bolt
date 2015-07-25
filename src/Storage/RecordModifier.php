<?php

namespace Bolt\Storage;

use Bolt\Application;
use Bolt\Content;
use Bolt\Translation\Translator as Trans;
use Cocur\Slugify\Slugify;
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

        // If we have an ID now, this is an existing record
        if ($id) {
            $content = $this->app['storage']->getContent($contenttypeslug, ['id' => $id, 'status' => '!undefined']);
            $oldStatus = $content['status'];
        } else {
            $content = $this->app['storage']->getContentObject($contenttypeslug);
            $oldStatus = '';
        }

        // Don't allow spoofing the $id.
        if (!empty($content['id']) && $id != $content['id']) {
            $this->app['logger.flash']->error("Don't try to spoof the id!");

            return new RedirectResponse($this->generateUrl('dashboard'));
        }

        // Ensure all fields have valid values
        $requestAll = $this->setSuccessfulControlValues($formValues, $contenttype['fields']);

        // To check whether the status is allowed, we act as if a status
        // *transition* were requested.
        $content->setFromPost($requestAll, $contenttype);
        $newStatus = $content['status'];

        $statusOK = $this->app['users']->isContentStatusTransitionAllowed($oldStatus, $newStatus, $contenttypeslug, $id);
        if ($statusOK) {
            // Get the associated record change comment
            $comment = isset($formValues['changelog-comment']) ? $formValues['changelog-comment'] : '';

            // Save the record
            return $this->saveContentRecord($content, $contenttype, $new, $comment, $returnTo, $editReferrer);
        } else {
            $this->app['logger.flash']->error(Trans::__('contenttypes.generic.error-saving', ['%contenttype%' => $contenttypeslug]));
            $this->app['logger.system']->error('Save error: ' . $content->getTitle(), ['event' => 'content']);
        }
    }

    /**
     * Commit the record to the database.
     *
     * @param Content $content
     * @param array   $contenttype
     * @param boolean $new
     * @param string  $comment
     * @param string  $returnTo
     * @param string  $editReferrer
     *
     * @return Response
     */
    private function saveContentRecord(Content $content, array $contenttype, $new, $comment, $returnTo, $editReferrer)
    {
        // Save the record
        $id = $this->app['storage']->saveContent($content, $comment);

        // Log the change
        if ($new) {
            $this->app['logger.flash']->success(Trans::__('contenttypes.generic.saved-new', ['%contenttype%' => $contenttype['slug']]));
            $this->app['logger.system']->info('Created: ' . $content->getTitle(), ['event' => 'content']);
        } else {
            $this->app['logger.flash']->success(Trans::__('contenttypes.generic.saved-changes', ['%contenttype%' => $contenttype['slug']]));
            $this->app['logger.system']->info('Saved: ' . $content->getTitle(), ['event' => 'content']);
        }

        /*
         * We now only get a returnto parameter if we are saving a new
         * record and staying on the same page, i.e. "Save {contenttype}"
         */
        if ($returnTo) {
            if ($returnTo === 'new') {
                return new RedirectResponse($this->generateUrl('editcontent', [
                    'contenttypeslug' => $contenttype['slug'],
                    'id'              => $id,
                    '#'               => $returnTo,
                ]));
            } elseif ($returnTo === 'saveandnew') {
                return new RedirectResponse($this->generateUrl('editcontent', [
                    'contenttypeslug' => $contenttype['slug'],
                    '#'               => $returnTo,
                ]));
            } elseif ($returnTo === 'ajax') {
                return $this->createJsonUpdate($contenttype, $id, true);
            } elseif ($returnTo === 'test') {
                return $this->createJsonUpdate($contenttype, $id, false);
            }
        }

        // No returnto, so we go back to the 'overview' for this contenttype.
        // check if a pager was set in the referrer - if yes go back there
        if ($editReferrer) {
            return new RedirectResponse($editReferrer);
        } else {
            return new RedirectResponse($this->generateUrl('overview', ['contenttypeslug' => $contenttype['slug']]));
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
                            $formValues[$key] = [];
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
     * @param boolean $flush
     *
     * @return JsonResponse
     */
    private function createJsonUpdate($contenttype, $id, $flush)
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

        // Get our record after POST_SAVE hooks are dealt with and return the JSON
        $content = $this->app['storage']->getContent($contenttype['slug'], ['id' => $id, 'returnsingle' => true, 'status' => '!undefined']);

        $val = [];

        foreach ($content->values as $key => $value) {
            // Some values are returned as \Twig_Markup and JSON can't deal with that
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    if (gettype($subvalue) === 'object' && get_class($subvalue) === 'Twig_Markup') {
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
        $this->app['logger.flash']->clear();

        return new JsonResponse($val);
    }

    /**
     * Do the edit form for a record.
     *
     * @param Content $content     A content record
     * @param array   $contenttype The contenttype data
     * @param integer $id          The record ID
     * @param boolean $new         If TRUE this is a new record
     * @param boolean $duplicate   If TRUE create a duplicate record
     *
     * @return array
     */
    public function handleEditRequest($content, array $contenttype, $id, $new, $duplicate)
    {
        $contenttypeslug = $contenttype['slug'];

        $oldStatus = $content['status'];
        $allStatuses = ['published', 'held', 'draft', 'timed'];
        $allowedStatuses = [];
        foreach ($allStatuses as $status) {
            if ($this->app['users']->isContentStatusTransitionAllowed($oldStatus, $status, $contenttypeslug, $id)) {
                $allowedStatuses[] = $status;
            }
        }

        // For duplicating a record, clear base field values.
        if ($duplicate) {
            $content->setValues([
                'id'            => '',
                'slug'          => '',
                'datecreated'   => '',
                'datepublish'   => '',
                'datedepublish' => null,
                'datechanged'   => '',
                'username'      => '',
                'ownerid'       => '',
            ]);

            $this->app['logger.flash']->info(Trans::__('contenttypes.generic.duplicated-finalize', ['%contenttype%' => $contenttypeslug]));
        }

        // Set the users and the current owner of this content.
        if ($new || $duplicate) {
            // For brand-new and duplicated items, the creator becomes the owner.
            $contentowner = $this->app['users']->getCurrentUser();
        } else {
            // For existing items, we'll just keep the current owner.
            $contentowner = $this->app['users']->getUser($content['ownerid']);
        }

        // Test write access for uploadable fields.
        $contenttype['fields'] = $this->setCanUpload($contenttype['fields']);
        if ((!empty($content['templatefields'])) && (!empty($content['templatefields']->contenttype['fields']))) {
            $content['templatefields']->contenttype['fields'] = $this->setCanUpload($content['templatefields']->contenttype['fields']);
        }

        // Build context for Twig.
        $contextCan = [
            'upload'             => $this->app['users']->isAllowed('files:uploads'),
            'publish'            => $this->app['users']->isAllowed('contenttype:' . $contenttypeslug . ':publish:' . $content['id']),
            'depublish'          => $this->app['users']->isAllowed('contenttype:' . $contenttypeslug . ':depublish:' . $content['id']),
            'change_ownership'   => $this->app['users']->isAllowed('contenttype:' . $contenttypeslug . ':change-ownership:' . $content['id']),
        ];
        $contextHas = [
            'incoming_relations' => is_array($content->relation),
            'relations'          => isset($contenttype['relations']),
            'tabs'               => $contenttype['groups'] !== false,
            'taxonomy'           => isset($contenttype['taxonomy']),
            'templatefields'     => $content->hasTemplateFields(),
        ];
        $contextValues = [
            'datepublish'        => $this->getPublishingDate($content['datepublish'], true),
            'datedepublish'      => $this->getPublishingDate($content['datedepublish']),
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
        ];

        return $context;
    }

    /**
     * Test write access for uploadable fields.
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

        foreach ([$contenttype['fields'], $content->get('templatefields')->contenttype['fields'] ?: []] as $fields) {
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
