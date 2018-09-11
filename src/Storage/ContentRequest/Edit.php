<?php

namespace Bolt\Storage\ContentRequest;

use Bolt\Config;
use Bolt\Filesystem\Manager;
use Bolt\Form\Resolver;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Entity\Relations;
use Bolt\Storage\Entity\TemplateFields;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ContentType;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Repository;
use Bolt\Translation\Translator as Trans;
use Bolt\Users;
use Cocur\Slugify\Slugify;
use Psr\Log\LoggerInterface;

/**
 * Helper class for ContentType record editor edits.
 *
 * Prior to v3.0 this functionality existed in \Bolt\Controllers\Backend::editcontent().
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Edit
{
    /** @var EntityManager */
    protected $em;
    /** @var Query */
    private $query;
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
     * @internal DO NOT USE
     *
     * @deprecated Temporary and to be removed circa 3.5.
     *
     * @param Query $query
     */
    public function setQueryHandler(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Do the edit form for a record.
     *
     * @param Content     $content     A content record
     * @param ContentType $contentType The ContentType data
     * @param bool        $duplicate   If TRUE create a duplicate record
     *
     * @return array
     */
    public function action(Content $content, ContentType $contentType, $duplicate)
    {
        $contentTypeSlug = $contentType['slug'];
        $new = $content->getId() === null ?: false;
        $oldStatus = $content->getStatus();
        $allStatuses = ['published', 'held', 'draft', 'timed'];
        $allowedStatuses = [];

        foreach ($allStatuses as $status) {
            if ($this->users->isContentStatusTransitionAllowed($oldStatus, $status, $contentTypeSlug, $content->getId())) {
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

            $this->loggerFlash->info(Trans::__('contenttypes.generic.duplicated-finalize', ['%contenttype%' => $contentTypeSlug]));
        }

        // Set the users and the current owner of this content.
        if ($new || $duplicate) {
            // For brand-new and duplicated items, the creator becomes the owner.
            $contentowner = $this->users->getCurrentUser();
        } else {
            // For existing items, we'll just keep the current owner.
            $contentowner = $this->users->getUser($content->getOwnerid());
        }

        // Build list of incoming non inverted related records.
        $incomingNotInverted = [];
        $count = 0;
        $limit = $this->config->get('general/compatibility/incoming_relations_limit', false);
        foreach ($content->getRelation()->incoming($content) as $relation) {
            /** @var Relations $relation */
            if ($relation->isInverted()) {
                continue;
            }
            $fromContentType = $relation->getFromContenttype();

            if ($this->query) {
                $this->em->setQueryService($this->query);
            }
            $record = $this->em->getContent($fromContentType . '/' . $relation->getFromId());

            if ($record) {
                $incomingNotInverted[$fromContentType][] = $record;
            }

            // Do not try to load more than X records or db will fail
            ++$count;
            if ($limit && $count > $limit) {
                break;
            }
        }

        /** @var Content $templateFieldsEntity */
        $templateFieldsEntity = $content->getTemplatefields();
        $templateFields = [];
        if ($templateFieldsEntity instanceof TemplateFields) {
            /** @var ContentType $templateFieldsContentType */
            $templateFieldsContentType = $templateFieldsEntity->getContenttype();
            $templateFields = $templateFieldsContentType->getFields();
            $templateFieldsContentType['fields'] = $templateFields;
        }

        // Temporary choice option resolver. Will be removed with Forms work circa Bolt 3.5.
        $choiceResolver = new Resolver\Choice($this->query);

        // Intersect the set taxonomies with actually existing ones, because bogus ones are just confusing.
        $existingTaxonomies = array_intersect(array_keys($this->config->get('taxonomy')), (array) $contentType['taxonomy']);

        // Build context for Twig.
        $contextCan = [
            'upload'             => $this->users->isAllowed('files:uploads'),
            'publish'            => $this->users->isAllowed('contenttype:' . $contentTypeSlug . ':publish:' . $content->getId()),
            'depublish'          => $this->users->isAllowed('contenttype:' . $contentTypeSlug . ':depublish:' . $content->getId()),
            'change_ownership'   => $this->users->isAllowed('contenttype:' . $contentTypeSlug . ':change-ownership:' . $content->getId()),
        ];
        $contextHas = [
            'incoming_relations' => count($incomingNotInverted) > 0,
            'relations'          => isset($contentType['relations']),
            'tabs'               => $contentType['groups'] !== [],
            'taxonomy'           => $existingTaxonomies !== [],
            'templatefields'     => count($templateFields) > 0,
        ];
        $contextValues = [
            'datepublish'        => $this->getPublishingDate($content->getDatepublish(), true),
            'datedepublish'      => $this->getPublishingDate($content->getDatedepublish()),
            'select_choices'     => $choiceResolver->get($contentType, (array) $templateFields),
        ];
        $context = [
            'incoming_not_inv' => $incomingNotInverted,
            'contenttype'      => $contentType,
            'content'          => $content,
            'allowed_status'   => $allowedStatuses,
            'contentowner'     => $contentowner,
            'duplicate'        => $duplicate,
            'fields'           => $this->config->fields->fields(),
            'fieldtemplates'   => $this->getTemplateFieldTemplates($contentType, $content),
            'fieldtypes'       => $this->getUsedFieldtypes($contentType, $content, $contextHas),
            'templatefields'   => $templateFields,
            'groups'           => $this->createGroupTabs($contentType, $contextHas),
            'can'              => $contextCan,
            'has'              => $contextHas,
            'values'           => $contextValues,
            'relations_list'   => $this->getRelationsList($contentType),
        ];

        return $context;
    }

    /**
     * Convert POST relationship values to an array of Entity objects keyed by
     * ContentType.
     *
     * @param ContentType $contentType
     *
     * @return array
     */
    private function getRelationsList(ContentType $contentType)
    {
        $list = [];
        if (!isset($contentType['relations']) || !is_array($contentType['relations'])) {
            return $list;
        }

        foreach ($contentType['relations'] as $relationName => $relationValues) {
            /** @var Repository\ContentRepository $repo */
            $repo = $this->em->getRepository($relationName);
            $relationConfig = $this->config->get('contenttypes/' . $relationName, []);
            $neededFields = $this->neededFields($relationValues, $relationConfig);
            $order = isset($relationValues['order']) ? $relationValues['order'] : null;

            $list[$relationName] = $repo->getSelectList($relationConfig, $order, $neededFields);
        }

        return $list;
    }

    /**
     * Get an array of fields mentioned in the 'format:' for a relationship in contenttypes.
     *
     * @param array $relationValues
     * @param array $relationConfig
     *
     * @return array
     */
    private function neededFields($relationValues, $relationConfig)
    {
        $fields = [];

        // Get the field names from the ContentType, including 'status'.
        $fieldNames = array_merge(array_keys($relationConfig['fields']), ['status']);

        // Regex the 'format' for things that look like 'item.foo', and intersect with the field names.
        if (!empty($relationValues['format'])) {
            preg_match_all('/\bitem\.([a-z0-9_]+)\b/i', $relationValues['format'], $matches);
            $fields = array_intersect($matches[1], $fieldNames);
        }

        return $fields;
    }

    /**
     * Determine which templates will result in templatefields.
     *
     * @param ContentType $contentType
     * @param Content     $content
     *
     * @return array
     */
    private function getTemplateFieldTemplates(ContentType $contentType, Content $content)
    {
        $templateFieldTemplates = [];
        $templateFieldsConfig = $this->config->get('theme/templatefields');

        if ($templateFieldsConfig) {
            $templateFieldTemplates = array_keys($templateFieldsConfig);
            // Special case for default template
            $toRepair = [];
            foreach ($contentType['fields'] as $name => $field) {
                if ($field['type'] === 'templateselect' && !empty($content->values[$name])) {
                    $toRepair[$name] = $content->values[$name];
                    $content->set($name, '');
                }
            }
            if ($content->hasTemplateFields()) {
                $templateFieldTemplates[] = '';
            }

            foreach ($toRepair as $name => $value) {
                $content->set($name, $value);
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
        if ($setNowOnEmpty && $date === '') {
            return date('Y-m-d H:i:s');
        } elseif ($date === '1900-01-01 00:00:00') {
            return '';
        }

        return $date;
    }

    /**
     * Generate tab groups.
     *
     * @param ContentType $contentType
     * @param array       $has
     *
     * @return array
     */
    private function createGroupTabs(ContentType $contentType, array $has)
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

        foreach ($contentType['groups'] ?: ['ungrouped'] as $group) {
            if ($group === 'ungrouped') {
                $addGroup($group, Trans::__('contenttypes.generic.group.ungrouped'));
            } elseif ($group !== 'meta' && $group !== 'relations' && $group !== 'taxonomy') {
                $default = ['DEFAULT' => ucfirst($group)];
                $key = ['contenttypes', $contentType['slug'], 'group', $group];
                $addGroup($group, Trans::__($key, $default));
            }
        }

        if ($has['relations'] || $has['incoming_relations']) {
            $addGroup('relations', Trans::__('contenttypes.generic.group.relations'));
            $groups['relations']['fields'][] = '*relations';
        }

        if ($has['taxonomy'] || (is_array($contentType['groups']) && in_array('taxonomy', $contentType['groups']))) {
            $addGroup('taxonomy', Trans::__('contenttypes.generic.group.taxonomy'));
            $groups['taxonomy']['fields'][] = '*taxonomy';
        }

        if ($has['templatefields'] || (is_array($contentType['groups']) && in_array('template', $contentType['groups']))) {
            $addGroup('template', Trans::__('contenttypes.generic.group.template'));
            $groups['template']['fields'][] = '*template';
        }

        $addGroup('meta', Trans::__('contenttypes.generic.group.meta'));
        $groups['meta']['fields'][] = '*meta';

        // References fields in tab group data.
        foreach ($contentType['fields'] as $fieldName => $field) {
            $groups[$field['group']]['fields'][] = $fieldName;
        }

        return $groups;
    }

    /**
     * Create a list of fields types used in regular, template and virtual fields.
     *
     * @param ContentType $contentType
     * @param Content     $content
     * @param array       $has
     *
     * @return array
     */
    private function getUsedFieldtypes(ContentType $contentType, Content $content, array $has)
    {
        $fieldtypes = [
            'meta' => true,
        ];

        if ($content->getTemplatefields() instanceof TemplateFields) {
            $templateFields = $content->getTemplatefields()->getContenttype()->getFields() ?: [];
        } else {
            $templateFields = [];
        }

        foreach ([$contentType['fields'], $templateFields] as $fields) {
            foreach ($fields as $field) {
                $fieldtypes[$field['type']] = true;
                if ($field['type'] === 'repeater') {
                    foreach ($field['fields'] as $rfield) {
                        $fieldtypes[$rfield['type']] = true;
                    }
                }
                if ($field['type'] === 'block') {
                    foreach ($field['fields'] as $block) {
                        foreach ($block['fields'] as $rfield) {
                            $fieldtypes[$rfield['type']] = true;
                        }
                    }
                }
            }
        }

        if ($has['relations'] || $has['incoming_relations']) {
            $fieldtypes['relationship'] = true;
        }

        if ($has['taxonomy'] || (is_array($contentType['groups']) && in_array('taxonomy', $contentType['groups']))) {
            $fieldtypes['taxonomy'] = true;
        }

        if ($has['templatefields'] || (is_array($contentType['groups']) && in_array('template', $contentType['groups']))) {
            $fieldtypes['template'] = true;
        }

        return array_keys($fieldtypes);
    }
}
