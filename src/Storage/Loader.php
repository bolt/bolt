<?php
namespace Bolt\Storage;

use Bolt\Storage\Field\Type\FieldTypeInterface;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\QueryInterface;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This class works on an instance of QueryBuilder transforming the query to the
 * needs of the field handlers.
 */
class Loader
{
    public $handlers = [];
    protected $fieldFactory;
    
    public function __construct(FieldFactory $fieldFactory = null)
    {
        $this->fieldFactory = $fieldFactory;
    }

    /**
     * @param QueryBuilder  $qb
     * @param ClassMetadata $metadata
     *
     * @return QueryBuilder
     */
    public function load(QueryBuilder $qb, ClassMetadata $metadata)
    {
        foreach ($metadata->getFieldMappings() as $field) {
            /** @var FieldTypeInterface $fieldtype */
            if ($this->fieldFactory !== null) {
                $fieldtype = $this->fieldFactory->get($field['fieldtype'], $field);
            } else {
                $fieldtype = new $field['fieldtype']($field);
            }
            
            $fieldtype->load($qb, $metadata);
        }

        return $qb;
    }

    /**
     * @param QueryInterface $query
     * @param ClassMetadata  $metadata
     *
     * @return QueryInterface
     */
    public function query(QueryInterface $query, ClassMetadata $metadata)
    {
        foreach ($metadata->getFieldMappings() as $field) {
            /** @var FieldTypeInterface $fieldtype */
            $fieldtype = new $field['fieldtype']($field);
            $fieldtype->query($query, $metadata);
        }

        return $query;
    }
}
