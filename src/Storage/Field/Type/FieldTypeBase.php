<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;

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
     * {@inheritdoc}
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public function query(QueryInterface $query, ClassMetadata $metadata)
    {
        return $query;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function hydrate($data, $entity, EntityManager $em = null)
    {
        $key = $this->mapping['fieldname'];
        $type = $this->getStorageType();
        $val = $data[$key];
        if ($val) {
            $value = $type->convertToPHPValue($val, $em->createQueryBuilder()->getConnection()->getDatabasePlatform());
            $entity->$key = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function present($entity)
    {
    }

    /**
     * {@inheritdoc}
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

    /**
     * Check if a value is a JSON string.
     *
     * @param mixed $value
     *
     * @return boolean
     */
    protected function isJson($value)
    {
        if (!is_string($value)) {
            return false;
        }
        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
