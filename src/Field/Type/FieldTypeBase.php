<?php
namespace Bolt\Field\Type;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use Bolt\Mapping\ClassMetadata;
use Bolt\Storage\EntityManager;
use Bolt\Storage\QuerySet;

/**
 * This is an abstract class for a field type that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
abstract class FieldTypeBase implements FieldTypeInterface
{

    public $mapping;

    public function __construct(array $mapping = [])
    {
        $this->mapping = $mapping;
    }

    /**
     * Handle or ignore the load event.
     *
     * @param QueryBuilder $query
     *
     * @return void
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        return $query;
    }

    /**
     * Handle the persistentce event.
     *
     * @return void
     */
    public function persist(QuerySet $queries, $entity, EntityManager $em = null)
    {
        $key = $this->mapping['fieldname'];
        $qb = &$queries[0];
        $valueMethod = 'serialize'.ucfirst($key);
        $value = $entity->$valueMethod();

        $type = $this->getStorageType();

        if (null !== $value) {
            $value = $type->convertToDatabaseValue($value, $qb->getConnection()->getDatabasePlatform());
        } else {
            $value = $this->mapping['default'];
        }
        $qb->setValue($key, ":".$key);
        $qb->set($key, ":".$key);
        $qb->setParameter($key, $value);
    }

    /**
     * Handle  the hydrate event.
     *
     * @return void
     */
    public function hydrate($data, $entity, EntityManager $em = null)
    {
        $key = $this->mapping['fieldname'];
        $type = $this->getStorageType();
        $val = $data[$key];
        $value = $type->convertToPHPValue($val, $em->createQueryBuilder()->getConnection()->getDatabasePlatform());
        $entity->$key = $value;
    }

    /**
     * Handle or ignore the present event.
     *
     * @return void
     */
    public function present($entity)
    {

    }

    /**
     * Returns the name of the hydrator.
     *
     * @return string The field name
     */
    public function getName()
    {
        return 'text';
    }

    /**
     * Returns the name of the Doctrine storage type to use for a field.
     *
     * @return Type
     */
    public function getStorageType()
    {
        return Type::getType($this->mapping['type']);
    }



}
