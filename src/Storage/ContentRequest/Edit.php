<?php

namespace Bolt\Storage\ContentRequest;

use Bolt\Config;
use Bolt\Filesystem\Manager;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Bolt\Translation\Translator as Trans;
use Bolt\Users;
use Cocur\Slugify\Slugify;
use Psr\Log\LoggerInterface;

/**
 * Helper class for ContentType record editor edits.
 *
 * Prior to v2.3 this functionality existed in \Bolt\Controllers\Backend::editcontent().
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Edit
{
    /** @var EntityManager */
    protected $em;
    /** @var Config */
    protected $config;
    /** @var Users */
    protected $users;
    /** @var Manager */
    protected $filesystem;

    /** @var LoggerInterface */
    protected $loggerSystem;
    /** @var FlashLoggerInterface */
    protected $loggerFlash;

    /**
     * Constructor function.
     *
     * @param EntityManager        $em
     * @param Config               $config
     * @param Users                $users
     * @param Manager              $filesystem
     * @param LoggerInterface      $loggerSystem
     * @param FlashLoggerInterface $loggerFlash
     */
    public function __construct(
        EntityManager $em,
        Config $config,
        Users $users,
        Manager $filesystem,
        LoggerInterface $loggerSystem,
        FlashLoggerInterface $loggerFlash
        ) {
        $this->em = $em;
        $this->config = $config;
        $this->users = $users;
        $this->filesystem = $filesystem;
        $this->loggerSystem = $loggerSystem;
        $this->loggerFlash = $loggerFlash;
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
    public function action(Content $content, array $contenttype, $duplicate)
    {
        $contenttypeSlug = $contenttype['slug'];
        $new = $content->getId() === null ?: false;
        $oldStatus = $content->getStatus();
        $allStatuses = ['published', 'held', 'draft', 'timed'];
        $allowedStatuses = [];

        foreach ($allStatuses as $status) {
            if ($this->users->isContentStatusTransitionAllowed($oldStatus, $status, $contenttypeSlug, $content->getId())) {
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

            $this->loggerFlash->info(Trans::__('contenttypes.generic.duplicated-finalize', ['%contenttype%' => $contenttypeSlug]));
        }

        // Set the users and the current owner of this content.
        if ($new || $duplicate) {
            // For brand-new and duplicated items, the creator becomes the owner.
            $contentowner = $this->users->getCurrentUser();
        } else {
            // For existing items, we'll just keep the current owner.
            $contentowner = $this->users->getUser($content->getOwnerid());
        }

        // Test write access for uploadable fields.
        $contenttype['fields'] = $this->setCanUpload($contenttype['fields']);
        $templateFields = $content->getTemplatefields();
        if ($templateFields && $templateFieldsData = $templateFields->getContenttype()->getFields()) {
            $this->setCanUpload($templateFields->getContenttype());
        }

        // Build context for Twig.
        $contextCan = [
            'upload'             => $this->users->isAllowed('files:uploads'),
            'publish'            => $this->users->isAllowed('contenttype:' . $contenttypeSlug . ':publish:' . $content->getId()),
            'depublish'          => $this->users->isAllowed('contenttype:' . $contenttypeSlug . ':depublish:' . $content->getId()),
            'change_ownership'   => $this->users->isAllowed('contenttype:' . $contenttypeSlug . ':change-ownership:' . $content->getId()),
        ];
        $contextHas = [
            'incoming_relations' => is_array($content->relation),
            'relations'          => isset($contenttype['relations']),
            'tabs'               => $contenttype['groups'] !== false,
            'taxonomy'           => isset($contenttype['taxonomy']),
            'templatefields'     => empty($templateFieldsData) ? false : true,
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
            'fields'             => $this->config->fields->fields(),
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
            $repo = $this->em->getRepository($contentType);
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
        $filesystem = $this->filesystem->getFilesystem();

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
        $templateFieldsConfig = $this->config->get('theme/templatefields');

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
     * @return string
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
            $addGroup('template', Trans::__('contenttypes.generic.group.template'));
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
}
