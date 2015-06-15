<?php
namespace Bolt\Storage;

use Bolt\Storage\Field\Type\FieldTypeInterface;
use Bolt\Storage\Mapping\ClassMetadata;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Maps raw sql query data to Bolt Entities
 */
class Hydrator
{
    /** @var string */
    protected $handler;
    /** @var ClassMetadata */
    protected $metadata;

    /**
     * Constructor.
     *
     * @param ClassMetadata $metadata
     */
    public function __construct(ClassMetadata $metadata)
    {
        $classHandler = $metadata->getName();
        if (!class_exists($classHandler)) {
            throw new \InvalidArgumentException("Value supplied $classHandler is not a valid class name", 1);
        }
        $this->handler = $classHandler;
        $this->metadata = $metadata;
    }

    /**
     * @param array         $source data
     * @param QueryBuilder  $qb
     * @param EntityManager $em
     *
     * @return mixed Entity
     */
    public function hydrate(array $source, QueryBuilder $qb, EntityManager $em = null)
    {
        $classname = $this->handler;
        $entity = new $classname;
        $entity->setContenttype($this->metadata->getBoltName());

        foreach ($this->metadata->getFieldMappings() as $key => $mapping) {
            // First step is to allow each Bolt field to transform the data.
            /** @var FieldTypeInterface $field */
            $field = new $mapping['fieldtype']($mapping);
            $field->hydrate($source, $entity, $em);
        }

        return $entity;
    }
}
