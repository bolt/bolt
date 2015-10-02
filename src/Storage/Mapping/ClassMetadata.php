<?php
namespace Bolt\Storage\Mapping;

use Bolt\Storage\NamingStrategy;
use Bolt\Storage\NamingStrategyInterface;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;

/**
 * Base metadata class, used to provide info from mapping configuration to Entity objects.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ClassMetadata implements ClassMetadataInterface
{
    /** @var string */
    protected $name;
    /** @var string */
    protected $boltname;
    /** @var string */
    protected $tableName;
    /** @var array */
    protected $identifier;
    /** @var NamingStrategyInterface */
    protected $namingStrategy;
    /** @var array */
    protected $fieldMappings;

    /**
     * Constructor.
     *
     * @param string                  $className      Fully-qualified class name
     * @param NamingStrategyInterface $namingStrategy Naming strategy
     */
    public function __construct($className, NamingStrategyInterface $namingStrategy = null)
    {
        if (!class_exists($className)) {
            throw new \InvalidArgumentException('Value passed must be a valid class name', 1);
        }

        $this->name = $className;
        $this->namingStrategy = $namingStrategy ?: new NamingStrategy();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the fully-qualified class name of this persistent class.
     *
     * @return string
     */
    public function getTableName()
    {
        if ($this->tableName) {
            return $this->tableName;
        }
        return $this->namingStrategy->classToTableName($this->name);
    }

    /**
     * Sets the table name of this persistent class.
     *
     * @param $tableName
     *
     * @return string
     */
    public function setTableName($tableName)
    {
        return $this->tableName = $tableName;
    }

    /**
     * Gets the bolt name of this class (normally table name without prefix).
     *
     * @return string
     */
    public function getBoltName()
    {
        return $this->boltname;
    }

    /**
     * Sets the bolt name of this class (normally table name without prefix).
     *
     * @param $name
     *
     * @return string
     */
    public function setBoltName($name)
    {
        return $this->boltname = $name;
    }

    /**
     * Gets the internal alias using the naming strategy.
     *
     * @return string
     */
    public function getAliasName()
    {
        return $this->namingStrategy->classToAlias($this->name);
    }

    /**
     * @param $fieldName
     *
     * @return bool
     */
    public function getFieldMapping($fieldName)
    {
        if (! isset($this->fieldMappings[$fieldName])) {
            return false;
        }
        return $this->fieldMappings[$fieldName];
    }

    /**
     * Sets the fieldMappings array with metadata.
     *
     * @param array $fieldMappings
     *
     * @return void
     */
    public function setFieldMappings($fieldMappings)
    {
        $this->fieldMappings = $fieldMappings;
    }

    /**
     * Gets the fieldMappings array.
     *
     *
     * @return array $fieldMappings
     */
    public function getFieldMappings()
    {
        return (array) $this->fieldMappings;
    }

    /**
     * Gets the mapped identifier field name.
     *
     * The returned structure is an array of the identifier field names.
     *
     * @return array
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the mapped identifier field name.
     *
     * @param array $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Gets the ReflectionClass instance for this mapped class.
     *
     * @return \ReflectionClass
     */
    public function getReflectionClass()
    {
        return new \ReflectionClass($this->getName());
    }

    /**
     * Checks if the given field name is a mapped identifier for this class.
     *
     * @param string $fieldName
     *
     * @return boolean
     */
    public function isIdentifier($fieldName)
    {
    }

    /**
     * Checks if the given field is a mapped property for this class.
     *
     * @param string $fieldName
     *
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * A numerically indexed list of field names of this persistent class.
     *
     * This array includes identifier fields if present on this class.
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array_keys($this->fieldMappings);
    }

    /**
     * Returns a type name of this field.
     *
     * This type names can be implementation specific but should at least include the php types:
     * integer, string, boolean, float/double, datetime.
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function getTypeOfField($fieldName)
    {
    }

    /**
     * Returns an array of identifier field names numerically indexed.
     *
     * @return array
     */
    public function getIdentifierFieldNames()
    {
    }

    /**** Following methods for interface compatibility, not yet used within Bolt ******/

    public function hasAssociation($fieldName)
    {
    }

    public function isSingleValuedAssociation($fieldName)
    {
    }

    public function isCollectionValuedAssociation($fieldName)
    {
    }

    public function getAssociationNames()
    {
    }

    public function getAssociationTargetClass($assocName)
    {
    }

    public function isAssociationInverseSide($assocName)
    {
    }

    public function getAssociationMappedByTargetField($assocName)
    {
    }

    public function getIdentifierValues($object)
    {
    }
}
