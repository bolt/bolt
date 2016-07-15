<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Exception\FieldConfigurationException;
use Bolt\Storage\Field\Collection\RepeatingFieldCollection;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class RepeaterType extends FieldTypeBase
{
    /**
     * For repeating fields, the load method adds extra joins and selects to the query that
     * fetches the related records from the field and field value tables in the same query as the content fetch.
     *
     * @param QueryBuilder  $query
     * @param ClassMetadata $metadata
     *
     * @return void
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        $field = $this->mapping['fieldname'];
        $boltname = $metadata->getBoltName();

        $from = $query->getQueryPart('from');

        if (isset($from[0]['alias'])) {
            $alias = $from[0]['alias'];
        } else {
            $alias = $from[0]['table'];
        }

        $dummy = 'f_' . $field;

        $query->addSelect($this->getPlatformGroupConcat($field, $query))
            ->leftJoin(
                $alias,
                $this->mapping['tables']['field_value'],
                $dummy,
                $dummy . ".content_id = $alias.id AND " . $dummy . ".contenttype='$boltname' AND " . $dummy . ".name = '$field'"
            );
    }

    public function persist(QuerySet $queries, $entity)
    {
        $this->normalize($entity);
        $key = $this->mapping['fieldname'];
        $accessor = 'get' . ucfirst($key);
        $proposed = $entity->$accessor();

        $collection = new RepeatingFieldCollection($this->em, $this->mapping);
        $existingFields = $this->getExistingFields($entity) ?: [];
        foreach ($existingFields as $group => $ids) {
            $collection->addFromReferences($ids, $group);
        }

        $toDelete = $collection->update($proposed);
        $repo = $this->em->getRepository('Bolt\Storage\Entity\FieldValue');

        $queries->onResult(
            function ($query, $result, $id) use ($repo, $collection, $toDelete) {
                foreach ($collection as $entity) {
                    $entity->content_id = $id;
                    $repo->save($entity);
                }

                foreach ($toDelete as $entity) {
                    $repo->delete($entity);
                }
            }
        );
    }

    public function hydrate($data, $entity)
    {
        $key = $this->mapping['fieldname'];
        if ($this->isJson($data[$key])) {
            $originalMapping[$key]['fields'] = $this->mapping['fields'];
            $originalMapping[$key]['type'] = 'repeater';
            $mapping = $this->em->getMapper()->getRepeaterMapping($originalMapping);

            $decoded = json_decode($data[$key], true);
            $collection = new RepeatingFieldCollection($this->em, $mapping);
            $collection->setName($key);

            if (isset($decoded) && count($decoded)) {
                foreach ($decoded as $group => $repdata) {
                    $collection->addFromArray($repdata, $group);
                }
            }

            $this->set($entity, $collection);
            return;
        }

        $vals = array_filter(explode(',', $data[$key]));
        $values = [];
        foreach ($vals as $fieldKey) {
            $split = explode('_', $fieldKey);
            $id = array_pop($split);
            $group = array_pop($split);
            $field = join('_', $split);
            $values[$field][$group][] = $id;
        }

        $collection = new RepeatingFieldCollection($this->em, $this->mapping);
        $collection->setName($key);

        if (isset($values[$key]) && count($values[$key])) {
            foreach ($values[$key] as $group => $refs) {
                $collection->addFromReferences($refs, $group);
            }
        }

        $this->set($entity, $collection);
    }

    /**
     * The set method gets called directly by a new entity builder. For this field we never want to allow
     * null values, rather we want an empty collection so this overrides the default and handles that.
     *
     * @param object $entity
     * @param mixed  $val
     */
    public function set($entity, $val)
    {
        if ($val === null) {
            $val = new RepeatingFieldCollection($this->em, $this->mapping);
        }

        return parent::set($entity, $val);
    }

    /**
     * Normalize step ensures that we have correctly hydrated objects at the collection
     * and entity level.
     *
     * @param $entity
     */
    public function normalize($entity)
    {
        $key = $this->mapping['fieldname'];
        $accessor = 'get' . ucfirst($key);

        $outerCollection = $entity->$accessor();
        if (!$outerCollection instanceof RepeatingFieldCollection) {
            $collection = new RepeatingFieldCollection($this->em, $this->mapping);
            $collection->setName($key);

            if (is_array($outerCollection)) {
                foreach ($outerCollection as $group => $fields) {
                    if (is_array($fields)) {
                        $collection->addFromArray($fields, $group, $entity);
                    }
                }
            }

            $setter = 'set' . ucfirst($key);
            $entity->$setter($collection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'repeater';
    }

    /**
     * Get platform specific group_concat token for provided column
     *
     * @param string       $alias
     * @param QueryBuilder $query
     *
     * @return string
     */
    protected function getPlatformGroupConcat($alias, QueryBuilder $query)
    {
        $platform = $query->getConnection()->getDatabasePlatform()->getName();

        $field = $this->mapping['fieldname'];
        $dummy = 'f_' . $field;

        switch ($platform) {
            case 'mysql':
                return "GROUP_CONCAT(DISTINCT CONCAT_WS('_', " . $dummy . '.name, ' . $dummy . '.grouping, ' . $dummy . ".id)) as $alias";
            case 'sqlite':
                return 'GROUP_CONCAT(DISTINCT ' . $dummy . ".name||'_'||" . $dummy . ".grouping||'_'||" . $dummy . ".id) as $alias";
            case 'postgresql':
                return 'string_agg(DISTINCT ' . $dummy . ".name||'_'||" . $dummy . ".grouping||'_'||" . $dummy . ".id, ',') as $alias";
        }
    }

    /**
     * Get existing fields for this record.
     *
     * @param mixed $entity
     *
     * @return array
     */
    protected function getExistingFields($entity)
    {
        $repo = $this->em->getRepository('Bolt\Storage\Entity\FieldValue');

        return $repo->getExistingFields($entity->getId(), $entity->getContenttype(), $this->mapping['fieldname']);
    }

    /**
     * Query to insert new field values.
     *
     * @param QuerySet $queries
     * @param array    $changes
     * @param $entity
     */
    protected function addToInsertQuery(QuerySet $queries, $changes, $entity)
    {
        foreach ($changes as $fieldValue) {
            $repo = $this->em->getRepository(get_class($fieldValue));
            $field = $this->getFieldType($fieldValue->getFieldname());
            $type = $field->getStorageType();
            $typeCol = 'value_' . $type->getName();

            $fieldValue->$typeCol = $fieldValue->getValue();
            $fieldValue->setFieldtype($this->getFieldTypeName($fieldValue->getFieldname()));
            $fieldValue->setContenttype((string) $entity->getContenttype());

            // This takes care of instances where an entity might be inserted, and thus not
            // have an id. This registers a callback to set the id parameter when available.
            $queries->onResult(
                function ($query, $result, $id) use ($repo, $fieldValue) {
                    if ($result === 1 && $id) {
                        $fieldValue->setContent_id($id);
                        $repo->save($fieldValue);
                    }
                }
            );
        }
    }

    /**
     * Query to delete existing field values.
     *
     * @param QuerySet $queries
     * @param $changes
     */
    protected function addToDeleteQuery(QuerySet $queries, $changes)
    {
    }

    /**
     * Query to insert new field values.
     *
     * @param QuerySet $queries
     * @param array    $changes
     * @param $entity
     */
    protected function addToUpdateQuery(QuerySet $queries, $changes, $entity)
    {
        foreach ($changes as $fieldValue) {
            $repo = $this->em->getRepository(get_class($fieldValue));
            $field = $this->getFieldType($fieldValue->getFieldname());
            $type = $field->getStorageType();
            $typeCol = 'value_' . $type->getName();
            $fieldValue->$typeCol = $fieldValue->getValue();

            // This takes care of instances where an entity might be inserted, and thus not
            // have an id. This registers a callback to set the id parameter when available.
            $queries->onResult(
                function ($query, $result, $id) use ($repo, $fieldValue) {
                    if ($result === 1) {
                        $repo->save($fieldValue);
                    }
                }
            );
        }
    }

    /**
     * @param $field
     *
     * @throws FieldConfigurationException
     *
     * @return mixed
     */
    protected function getFieldType($field)
    {
        if (!isset($this->mapping['data']['fields'][$field]['fieldtype'])) {
            throw new FieldConfigurationException('Invalid repeating field configuration for ' . $field);
        }
        $mapping = $this->mapping['data']['fields'][$field];
        $setting = $mapping['fieldtype'];

        return $this->em->getFieldManager()->get($setting, $mapping);
    }

    /**
     * @param $field
     *
     * @throws FieldConfigurationException
     *
     * @return mixed
     */
    protected function getFieldTypeName($field)
    {
        if (!isset($this->mapping['data']['fields'][$field]['type'])) {
            throw new FieldConfigurationException('Invalid repeating field configuration for ' . $field);
        }
        $mapping = $this->mapping['data']['fields'][$field];

        return $mapping['type'];
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType()
    {
        return Type::getType('json_array');
    }
}
