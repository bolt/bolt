<?php
namespace Bolt\Storage;

use Bolt\Field\Type\FieldTypeInterface;
use Bolt\Storage\Mapping\ClassMetadata;

/**
 * This class prepares an entity instance ready to be persisted to the
 * database. It consults handlers first before falling back to native doctrine
 * types.
 */
class Persister
{
    protected $metadata;

    public function __construct(ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @param QuerySet      $queries
     * @param mixed         $entity
     * @param EntityManager $em
     *
     * @return mixed Entity
     */
    public function persist(QuerySet $queries, $entity, EntityManager $em)
    {
        foreach ($this->metadata->getFieldMappings() as $key => $mapping) {
            // First step is to allow each Bolt field to transform the data.
            /** @var FieldTypeInterface $field */
            $field = new $mapping['fieldtype']($mapping);
            $field->persist($queries, $entity, $em);
        }

        return $entity;
    }
}
