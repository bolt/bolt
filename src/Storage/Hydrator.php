<?php
namespace Bolt\Storage;

use Bolt\Storage\Field\Type\FieldTypeInterface;
use Bolt\Storage\Mapping\ClassMetadata;

/**
 * Maps raw sql query data to Bolt Entities.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Hydrator
{
    /** @var string */
    protected $handler;
    /** @var ClassMetadata */
    protected $metadata;
    /** @var FieldFactory */
    protected $fieldFactory;

    /**
     * Constructor.
     *
     * @param ClassMetadata $metadata
     */
    public function __construct(ClassMetadata $metadata, FieldFactory $fieldFactory = null)
    {
        $classHandler = $metadata->getName();
        if (!class_exists($classHandler)) {
            throw new \InvalidArgumentException("Value supplied $classHandler is not a valid class name", 1);
        }
        $this->handler = $classHandler;
        $this->metadata = $metadata;
        $this->fieldFactory = $fieldFactory;
    }

    /**
     * Creates an entity ready for hydration.
     *
     * @return Entity
     */
    public function create()
    {
        $classname = $this->handler;
        $entity = new $classname;
        $entity->setContenttype($this->metadata->getBoltName());

        return $entity;
    }

    /**
     * @param Entity        $entity data
     * @param array         $source data
     * @param EntityManager $em
     *
     * @return Entity
     */
    public function hydrate($entity, array $source, EntityManager $em = null)
    {
        foreach ($this->metadata->getFieldMappings() as $mapping) {
            // First step is to allow each Bolt field to transform the data.
            /** @var FieldTypeInterface $field */
            if ($this->fieldFactory !== null) {
                $field = $this->fieldFactory->get($mapping['fieldtype'], $mapping);
            } else {
                $field = new $mapping['fieldtype']($mapping, $em);
            }

            $field->hydrate($source, $entity);
        }

        return $entity;
    }
}
