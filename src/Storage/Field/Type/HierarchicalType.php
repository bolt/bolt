<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Entity\Hierarchical;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Hierarchy;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Robert Hunt <robertgahunt@gmail.com>
 */
class HierarchicalType extends FieldTypeBase
{

    /**
     * @var Hierarchy
     */
    private $hierarchy;

    /**
     * @var Hierarchical
     */
    private $entity;

    /**
     * Constructor.
     *
     * @param array $mapping
     * @param EntityManager $em
     * @param Hierarchy $hierarchy
     */
    public function __construct(array $mapping, EntityManager $em, Hierarchy $hierarchy)
    {

        parent::__construct($mapping, $em);
        $this->hierarchy = $hierarchy;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {

        return 'hierarchical';
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType()
    {

        return Type::getType('text');
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity)
    {

        if (!$this->entity instanceof Hierarchical) {
            $this->entity = $this->createEntity($entity);
        }

        $this->entity->setContent($entity)
                     ->setHierarchy($this->hierarchy);

        $this->set($entity, $this->entity);
    }

    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        // Do not save to the database!
    }

    /**
     * {@inheritdoc}
     */
    public function set($entity, $value, $rawData = null)
    {

        $key = $this->mapping['fieldname'];

        if (!$value instanceof Hierarchical) {
            $this->entity = $this->createEntity($entity);
        }

        $this->entity->setContent($entity)
                     ->setHierarchy($this->hierarchy);
        $entity->$key = $this->entity;
    }

    private function createEntity($entity)
    {

        $metadata = new ClassMetadata(get_class($entity));

        $builder = $this->em->getEntityBuilder(Hierarchical::class);
        $builder->setClassMetadata($metadata);
        $hierarchicalEntity = $builder->createFromDatabaseValues([
            'hierarchy' => $this->hierarchy,
            'content'   => $this->entity
        ]);

        return $hierarchicalEntity;
    }
}
