<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Common\Json;
use Bolt\Exception\FieldConfigurationException;
use Bolt\Storage\Entity;
use Bolt\Storage\Field\Collection\RepeatingFieldCollection;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\Filter;
use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\Query\QueryParameterParser;
use Bolt\Storage\Query\SelectQuery;
use Bolt\Storage\QuerySet;
use Bolt\Storage\Repository\FieldValueRepository;
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
     * Repeater fields can allow filters on the value of a sub-field
     *
     * For example the following queries:
     *     'repeatfield', {'repeatstatus=1'}
     *     'repeatfield', {'repeattitle=%Test%'}.
     *
     * Because the search is actually on the join table, we replace the
     * expression to filter the join side rather than on the main side.
     *
     * @param QueryInterface $query
     * @param ClassMetadata  $metadata
     *
     * @return QueryBuilder|null
     */
    public function query(QueryInterface $query, ClassMetadata $metadata)
    {
        $field = $this->mapping['fieldname'];

        /** @var SelectQuery $query */
        foreach ($query->getFilters() as $filter) {
            /** @var Filter $filter */
            if ($filter->getKey() == $field) {
                $this->rewriteQueryFilterParameters($filter, $query, $field);
            }
        }

        return null;
    }

    /**
     * This method does an in-place modification of a generic ContentType.field
     * query to the format actually used in the raw SQL. For instance a simple
     * query might say `entries.repeater = 'title=%Test%'` but now we are in
     * the context of entries the actual SQL fragment needs to do a sub-select
     * on the bolt_field_value table and only return the content_ids where this
     * query matches so this method rewrites the SQL fragment just before the
     * query gets sent.
     *
     * @param Filter      $filter
     * @param SelectQuery $query
     * @param string      $field
     */
    protected function rewriteQueryFilterParameters(Filter $filter, SelectQuery $query, $field)
    {
        $boltName = $query->getContentType();

        $newExpression = $this->em->createExpressionBuilder();
        $count = 0;
        foreach ($query->getWhereParametersFor($field) as $paramKey => $paramValue) {
            $parameterParts = explode('_', $paramKey);
            array_pop($parameterParts);
            array_shift($parameterParts);
            $subkey = join('_', $parameterParts);

            $parser = new QueryParameterParser($newExpression);
            $parsed = $parser->parseValue($paramValue);
            $placeholder = $paramKey;
            $q = $this->em->createQueryBuilder();
            $q->addSelect('content_id')
                ->from($this->mapping['tables']['field_value'], 'f')
                ->andWhere("f.content_id = _$boltName.id")
                ->andWhere("f.contenttype = '$boltName'")
                ->andWhere("f.name = '$field'")
                ->andWhere("f.fieldname = '" . $subkey . "'")
                ->andWhere(
                    $q->expr()
                        ->orX()
                        ->add(
                            $q->expr()->{$parsed['operator']}('f.value_text', ':' . $placeholder)
                        )
                        ->add(
                            $q->expr()->{$parsed['operator']}('f.value_string', ':' . $placeholder)
                        )
                        ->add(
                            $q->expr()->{$parsed['operator']}('f.value_json_array', ':' . $placeholder)
                        )
                );
            $filter->setParameter($placeholder, $parsed['value']);
            $count++;
        }
        $comp = $newExpression->andX($newExpression->in('_' . $boltName . '.id', $q->getSQL()));
        $filter->setKey('_' . $boltName . '.id');
        $filter->setExpression($comp);
    }

    /**
     * For repeating fields, the load method adds extra joins and selects to
     * the query that fetches the related records from the field and field
     * value tables in the same query as the content fetch.
     *
     * @param QueryBuilder  $query
     * @param ClassMetadata $metadata
     *
     * @return QueryBuilder|null
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        $field = $this->mapping['fieldname'];
        $boltname = $metadata->getBoltName();
        $table = $this->mapping['tables']['field_value'];

        $from = $query->getQueryPart('from');

        if (isset($from[0]['alias'])) {
            $alias = $from[0]['alias'];
        } else {
            $alias = $from[0]['table'];
        }

        $subQuery = '(SELECT ' . $this->getPlatformGroupConcat($query) . " FROM $table f WHERE f.content_id = $alias.id AND f.contenttype='$boltname' AND f.name = '$field') as $field";
        $query->addSelect($subQuery);

        return null;
    }

    /**
     * {@inheritdoc}
     */
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
        $repo = $this->em->getRepository(Entity\FieldValue::class);

        $queries->onResult(
            function ($query, $result, $id) use ($repo, $collection, $toDelete) {
                foreach ($collection as $entity) {
                    $entity->content_id = $id;
                    $repo->save($entity, true);
                }

                foreach ($toDelete as $entity) {
                    $repo->delete($entity);
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity)
    {
        /** @var string $key */
        $key = $this->mapping['fieldname'];
        $collection = new RepeatingFieldCollection($this->em, $this->mapping);
        $collection->setName($key);

        // If there isn't anything set yet then we just return an empty collection
        if (!isset($data[$key])) {
            $this->set($entity, $collection);

            return;
        }
 
        // This block separately handles JSON content for Templatefields
        if (isset($data[$key]) && Json::test($data[$key])) {

            if (isset($this->mapping['fields'])) {
                $originalMapping[$key]['fields'] = $this->mapping['fields'];
                $originalMapping[$key]['type'] = 'repeater';
            } else {
                $originalMapping[$key]['fields'] = $this->mapping['data']['fields'];
                $originalMapping[$key]['type'] = 'block';
            }

            $mapping = $this->em->getMapper()->getRepeaterMapping($originalMapping);

            $decoded = Json::parse($data[$key]);
            $collection = new RepeatingFieldCollection($this->em, $mapping);
            $collection->setName($key);

            if (isset($this->mapping['fields'])) {
                if (isset($decoded) && count($decoded)) {
                    foreach ($decoded as $group => $repdata) {
                        $collection->addFromArray($repdata, $group);
                    }
                }
            } else { 
                if (isset($decoded) && count($decoded)) {
                    foreach ($decoded as $group => $block) {
                        foreach ($block as $blockName => $fields) {
                            $fields = $fields;
                            array_shift($fields);
                            if (is_array($fields)) {
                                $collection->addFromArray($fields, $group, $entity, $blockName);
                            }
                        }
                    }
                }
            }

            $this->set($entity, $collection);

            return;
        }

        // Final block handles values stored in the DB and creates a lazy collection
        $vals = array_filter(explode(',', $data[$key]));
        $values = [];
        foreach ($vals as $fieldKey) {
            $split = explode('_', $fieldKey);
            $id = array_pop($split);
            $group = array_pop($split);
            $field = implode('_', $split);
            $values[$field][$group][] = $id;
        }

        if (isset($values[$key]) && count($values[$key])) {
            ksort($values[$key]);
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
     * @param object $entity
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
     * Get platform specific group_concat token for provided column.
     *
     * @param QueryBuilder $query
     *
     * @return string
     */
    protected function getPlatformGroupConcat(QueryBuilder $query)
    {
        $platform = $query->getConnection()->getDatabasePlatform()->getName();

        switch ($platform) {
            case 'mysql':
                return "GROUP_CONCAT(DISTINCT CONCAT_WS('_', f.name, f.grouping, f.id))";
            case 'sqlite':
                return "GROUP_CONCAT(DISTINCT f.name||'_'||f.grouping||'_'||f.id)";
            case 'postgresql':
                return "string_agg(concat_ws('_', f.name,f.grouping,f.id), ',' ORDER BY f.grouping)";
        }

        throw new \RuntimeException(sprintf('Configured database platform "%s" is not supported.', $platform));
    }

    /**
     * Get existing fields for this record.
     *
     * @param object $entity
     *
     * @return array
     */
    protected function getExistingFields($entity)
    {
        /** @var FieldValueRepository $repo */
        $repo = $this->em->getRepository(Entity\FieldValue::class);

        return $repo->getExistingFields($entity->getId(), $entity->getContenttype(), $this->mapping['fieldname']);
    }

    /**
     * Query to insert new field values.
     *
     * @param QuerySet $queries
     * @param array    $changes
     * @param object   $entity
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
                        $repo->save($fieldValue, true);
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
     */
    protected function addToUpdateQuery(QuerySet $queries, $changes)
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
                        $repo->save($fieldValue, true);
                    }
                }
            );
        }
    }

    /**
     * @param string $field
     *
     * @throws FieldConfigurationException
     *
     * @return FieldTypeBase
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
        return Type::getType('json');
    }
}
