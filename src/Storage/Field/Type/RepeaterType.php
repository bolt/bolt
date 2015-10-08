<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Exception\FieldConfigurationException;
use Bolt\Storage\Field\Collection\RepeatingFieldCollection;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\QuerySet;
use Bolt\Storage\ValuesCollection;
use Doctrine\DBAL\Query\QueryBuilder;

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
     * @param QueryBuilder $query
     * @param ClassMetadata $metadata
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

        $query->addSelect($this->getPlatformGroupConcat('fields', $query))
            ->leftJoin(
                $alias,
                $this->mapping['tables']['field_value'],
                'f',
                "f.content_id = $alias.id AND f.contenttype='$boltname' AND f.name='$field'"
            );
    }

    public function persist(QuerySet $queries, $entity)
    {
        $this->normalize($entity);
        $key = $this->mapping['fieldname'];
        $accessor = 'get'.ucfirst($key);

        $collection = $entity->$accessor();

        $this->addToInsertQuery($queries, $collection->getNew(), $entity);
        $this->addToUpdateQuery($queries, $collection->getExisting(), $entity);
    }

    public function hydrate($data, $entity)
    {
        $key = $this->mapping['fieldname'];
        $vals = array_filter(explode(',', $data['fields']));
        $values = [];
        foreach($vals as $fieldKey) {
            $split = explode('_', $fieldKey);
            $values[$split[0]][$split[1]][] = $split[2];
        }
        $collection = new RepeatingFieldCollection($this->em);
        $collection->setName($key);

        if (count($values[$key])) {
            foreach ($values[$key] as $group => $refs) {
                $collection->addFromReferences($refs, $group);
            }
        }

        $this->set($entity, $collection);
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
        $accessor = 'get'.ucfirst($key);

        $outerCollection = $entity->$accessor();
        $newVal = [];
        if (!$outerCollection instanceof RepeatingFieldCollection) {
            $collection = new RepeatingFieldCollection($this->em);
            $collection->setName($key);

            if (is_array($outerCollection)) {
                foreach ($outerCollection as $group => $fields) {
                    if (is_array($fields)) {
                        $collection->addFromArray($fields, $group);
                    }
                }
            }

            $setter = 'set'.ucfirst($key);
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

        switch ($platform) {
            case 'mysql':
                return "GROUP_CONCAT(CONCAT_WS('_', f.name, f.grouping, f.id)) as $alias";
            case 'sqlite':
                return "GROUP_CONCAT(f.name||'_'||f.grouping||'_'||f.id) as $alias";
            case 'postgresql':
                return "string_agg(f.name||'_'||f.grouping||'_'||f.id) as $alias";
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
        $query = $this->em->createQueryBuilder()
            ->select('grouping, id', 'name')
            ->from($this->mapping['tables']['field_value'])
            ->where('content_id = :id')
            ->andWhere('contenttype = :contenttype')
            ->andWhere('name = :name')
            ->setParameters([
                'id'          => $entity->id,
                'contenttype' => $entity->getContenttype(),
                'name'        => $this->mapping['fieldname'],
            ]);

        $results = $query->execute()->fetchAll();

        $fields = [];

        if (!$results) {
            return $fields;
        }

        foreach ($results as $result) {
            $fields[$result['grouping']][$result['name']] = $result['id'];
        }

        return $fields;
    }

    protected function getQueryStatuses($entity)
    {
        $persisted = $this->getExistingFields($entity);

        $key = $this->mapping['fieldname'];
        $accessor = 'get'.ucfirst($key);

        $index = [];

        if (!$proposed instanceof RepeatingFields) {
            $this->set($entity, $proposed);
            $proposed = $entity->$accessor();
        }

        var_dump($proposed); exit;

        foreach ($proposed as $group => $fields) {
            foreach ($fields as $key=> $fieldValue) {
                if(isset($persisted[$group][$key])) {
                    $index['update'][$group][] = $key;
                } else {
                    $index['insert'][$group][] = $key;
                }

            }
        }

        foreach ($persisted as $group => $fields) {
            foreach ($fields as $key=>$fieldValue) {
                if (!isset($proposed[$group][$key])) {
                    $index['delete'][$group][] = $key;
                }
            }

        }

        return $index;
    }


    /**
     * Query to insert new field values.
     *
     * @param QuerySet $queries
     * @param array $changes
     * @param $entity
     */
    protected function addToInsertQuery(QuerySet $queries, $changes, $entity)
    {
        foreach ($changes as $fieldValue) {
            $repo = $this->em->getRepository(get_class($fieldValue));
            $field = $this->getFieldType($fieldValue->getFieldname());
            $type = $field->getStorageType();
            $typeCol = 'value_'.$type->getName();

            $fieldValue->$typeCol = $fieldValue->getValue();
            $fieldValue->setFieldtype($type->getName());
            $fieldValue->setContenttype((string)$entity->getContenttype());

            // This takes care of instances where an entity might be inserted, and thus not
            // have an id. This registers a callback to set the id parameter when available.
            $queries->onResult(function ($query, $result, $id) use ($repo, $fieldValue) {
                if ($result === 1 && $id) {
                    $fieldValue->setContent_id($id);
                    $repo->save($fieldValue);
                }
            });

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
     * @param array $changes
     * @param $entity
     */
    protected function addToUpdateQuery(QuerySet $queries, $changes, $entity)
    {
        foreach ($changes as $fieldValue) {
            $repo = $this->em->getRepository(get_class($fieldValue));
            $field = $this->getFieldType($fieldValue->getName());
            $type = $field->getStorageType();
            $typeCol = 'value_'.$type->getName();
            $fieldValue->$typeCol = $fieldValue->getValue();

            var_dump($fieldValue); exit;
            // This takes care of instances where an entity might be inserted, and thus not
            // have an id. This registers a callback to set the id parameter when available.
            $queries->onResult(function ($query, $result, $id) use ($repo, $fieldValue) {
                if ($result === 1) {
                    $repo->save($fieldValue);
                }
            });

        }

    }

    /**
     * @param $field
     * @return mixed
     * @throws FieldConfigurationException
     */
    protected function getFieldType($field)
    {
        if (!isset($this->mapping['data']['fields'][$field]['fieldtype'])) {
            throw new FieldConfigurationException('Invalid repeating field configuration for '.$field);
        }
        $mapping = $this->mapping['data']['fields'][$field];
        $setting = $mapping['fieldtype'];

        return $this->em->getFieldManager()->get($setting, $mapping);
    }
}
