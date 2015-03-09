<?php
namespace Bolt\Mapping;

use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Bolt\Storage\NamingStrategyInterface;
use Bolt\Storage\NamingStrategy;

/**
 * Base metadata class, used to provide info from mapping configuration to Entity objects.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ClassMetadata implements ClassMetadataInterface
{
    
    /**
     * @var string
     */
    protected $name;
    
    /**
     * @var NamingStrategyInterface
     */
    protected $namingStrategy;
    
    /**
     * @var array
     */
    protected $fieldMappings;
    
    /**
     * Constructor, takes Fully-Qualified Class Name, applies 
     *
     * @return void
     **/
    public function __construct($className, NamingStrategyInterface $namingStrategy)
    {
        $this->name = $className;
        $this->namingStrategy = $namingStrategy ?: new NamingStrategy();
    }
    
    /**
     * Gets the fully-qualified class name of this persistent class.
     *
     * @return string
     */
    public function getName() 
    {
        return $this->name;
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
     * Gets the mapped identifier field name.
     *
     * The returned structure is an array of the identifier field names.
     *
     * @return array
     */
    public function getIdentifier()
    {
        
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
    
    public function hasAssociation($fieldName) {}
    
    public function isSingleValuedAssociation($fieldName) {}
        
    public function isCollectionValuedAssociation($fieldName) {}
    
    public function getAssociationNames() {}
    
    public function getAssociationTargetClass($assocName) {}

    public function isAssociationInverseSide($assocName) {}
        
    public function getAssociationMappedByTargetField($assocName) {}
        
    public function getIdentifierValues($object) {}



    
    

    
}