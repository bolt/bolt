<?php
namespace Bolt\Storage;

use Bolt\Storage\Field\Type\FieldTypeInterface;
use Bolt\Storage\Mapping\ClassMetadata;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * This class works on an instance of QueryBuilder transforming the query to the
 * needs of the field handlers.
 */
class Loader
{
    public $handlers = [];

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
            $fieldtype = new $field['fieldtype']($field);
            $fieldtype->load($qb, $metadata);
        }

        return $qb;
    }
}
