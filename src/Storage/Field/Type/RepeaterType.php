<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\QueryInterface;
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
     * @param QueryBuilder  $query
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
            ->leftJoin($alias, $this->mapping['tables']['field'], 'f', "f.content_id = $alias.id AND f.contenttype='$boltname'")
            ->leftJoin($alias, $this->mapping['tables']['field_value'], 'fv', "fv.field_id=f.id");
    }

    public function persist(QuerySet $queries, $entity)
    {
        $field = $this->mapping['fieldname'];
        $existingFields = $this->getFieldsQuery();

        $this->addDeleteFieldValuesQuery($queries, $existingFields);
        $this->addDeleteFieldsQuery($queries, $existingFields);
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

        $entityVals = [];
        foreach ((array)$values[$key] as $field) {
            $entityVals[] = new ValuesCollection($field, $this->em);
        }

        $this->set($entity, $entityVals);
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
     * @param string       $column
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
                return "GROUP_CONCAT(CONCAT_WS('_', f.name, f.id, fv.id)) as $alias";
            case 'sqlite':
                return "GROUP_CONCAT(f.name||'_'||f.id||'_'||fv.id) as $alias";
            case 'postgresql':
                return "string_agg(f.name||'_'||f.id||'_'||fv.id) as $alias";
        }
    }

    /**
     * Get existing fields for this record.
     *
     * @param mixed $entity
     *
     * @return array
     */
    protected function getFieldsQuery($entity)
    {
        $query = $this->em->createQueryBuilder()
            ->select('id')
            ->from($this->mapping['tables']['field'])
            ->where('content_id = :id')
            ->andWhere('contenttype = :contenttype')
            ->andWhere('name = :name')
            ->setParameters([
                'id'          => $entity->id,
                'contenttype' => $entity->getContenttype(),
                'name'        => $this->mapping['fieldname'],
            ]);

        $result = $query->execute()->fetchAll();

        return $result ?: [];
    }

    /**
     * Query to delete existing field values.
     *
     * @param mixed $entity
     *
     * @return array
     */
    protected function addDeleteFieldValuesQuery(QuerySet $queries, $ids)
    {
        $query = $this->em->createQueryBuilder()
            ->delete($this->mapping['tables']['field_value'], 'fv')
            ->where('fv.field_id IN(:ids)')
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY);

        echo $query->getSQL(); exit;

        $queries->append($query);
    }
}
