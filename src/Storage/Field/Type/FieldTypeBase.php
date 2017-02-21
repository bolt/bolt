<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\CaseTransformTrait;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Field\FieldInterface;
use Bolt\Storage\Field\Sanitiser\SanitiserAwareInterface;
use Bolt\Storage\Field\Sanitiser\WysiwygAwareInterface;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\Filter;
use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Type;
use ReflectionProperty;
use Traversable;

/**
 * This is an abstract class for a field type that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
abstract class FieldTypeBase implements FieldTypeInterface, FieldInterface
{
    use CaseTransformTrait;

    /** @var string[] */
    public $mapping;

    /** @var EntityManager */
    protected $em;
    /** @var AbstractPlatform */
    protected $platform;

    /**
     * Constructor.
     *
     * @param array              $mapping
     * @param EntityManager|null $em
     */
    public function __construct(array $mapping = [], EntityManager $em = null)
    {
        $this->mapping = $mapping;
        $this->em = $em;
        if ($em) {
            $this->setPlatform($em->createQueryBuilder()->getConnection()->getDatabasePlatform());
        }
    }

    /**
     * Returns the platform
     *
     * @return AbstractPlatform
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Sets the current platform to an instance of AbstractPlatform
     *
     * @param AbstractPlatform $platform
     */
    public function setPlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function query(QueryInterface $query, ClassMetadata $metadata)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        $attribute = $this->getMappingAttribute();
        $key = $this->mapping['fieldname'];

        $qb = &$queries[0];
        $valueMethod = 'serialize' . ucfirst($this->camelize($attribute));
        $value = $entity->$valueMethod();

        if ($this instanceof SanitiserAwareInterface && is_string($value)) {
            $isWysiwyg = $this instanceof WysiwygAwareInterface;
            $value = $this->getSanitiser()->sanitise($value, $isWysiwyg);
        }

        $type = $this->getStorageType();

        if (null !== $value) {
            $value = $type->convertToDatabaseValue($value, $this->getPlatform());
        } elseif (isset($this->mapping['default'])) {
            $value = $this->mapping['default'];
        }
        $qb->setValue($key, ':' . $key);
        $qb->set($key, ':' . $key);
        $qb->setParameter($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate($data, $entity)
    {
        $key = $this->mapping['fieldname'];
        $type = $this->getStorageType();
        $val = isset($data[$key]) ? $data[$key] : null;
        if ($val !== null) {
            $value = $type->convertToPHPValue($val, $this->getPlatform());
            $this->set($entity, $value);
        }
    }

    /**
     * The set method takes a raw php value and performs the conversion to the entity value.
     * Normally this is as simple as $entity->$key = $value although more complicated transforms
     * can happen should a field type choose to override this method.
     *
     * Note too that this will also be the default method called for an entity builder which is
     * designed to receive raw data to initialise an entity.
     *
     * @param object $entity
     * @param mixed  $value
     */
    public function set($entity, $value)
    {
        $key = $this->mapping['fieldname'];
        if ($value === null && isset($this->mapping['data']['default'])) {
            $value = $this->mapping['data']['default'];
        }
        $entity->$key = $value;
    }

    /**
     * Reads the current value of the field from an entity and returns value
     *
     * @param $entity
     *
     * @return mixed
     */
    public function get($entity)
    {
        $key = $this->mapping['fieldname'];
        $valueMethod = 'get' . ucfirst($key);
        $value = $entity->$valueMethod();

        return $value;
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
     * @deprecated
     * Here to maintain compatibility with the old interface
     */
    public function getStorageOptions()
    {
        @trigger_error(sprintf('%s is deprecated and will be removed in version 4.0.', __METHOD__), E_USER_DEPRECATED);

        return [];
    }

    /**
     * Gets the entity attribute name to be used for reading / persisting
     *
     * @return string
     */
    public function getMappingAttribute()
    {
        if (isset($this->mapping['attribute'])) {
            return $this->mapping['attribute'];
        }

        return $this->mapping['fieldname'];
    }

    /**
     * Provides a template that is able to render the field
     *
     * @deprecated
     */
    public function getTemplate()
    {
        @trigger_error(sprintf('%s is deprecated and will be removed in version 4.0.', __METHOD__), E_USER_DEPRECATED);

        return '@bolt/editcontent/fields/_' . $this->getName() . '.twig';
    }

    /**
     * Check if a value is a JSON string.
     *
     * @param string $value
     *
     * @return boolean
     */
    protected function isJson($value)
    {
        if (!is_string($value)) {
            return false;
        }

        // This handles an inconsistency in the result from the JSON parser across 5.x and 7.x of PHP
        if ($value === '') {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }


}
