<?php

namespace Bolt\Mapping;

use Bolt\Database\IntegrityChecker;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\DBAL\Schema\Table;

/**
 * This is a Bolt specific metadata driver that provides mapping information
 * for the internal and user-defined schemas. To do this it takes in the constructor,
 * an instance of IntegrityChecker and uses this to read in the schema.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class MetadataDriver implements MappingDriver
{
    
    /**
     * IntegrityChecker object
     */
    protected $integrityChecker;
    
    /**
     * array of ClassMetadata objects
     */
    protected $metadata;
    
    /**
     * @var boolean
     */
    protected $initialized = false;

    /**
     * @param IntegrityChecker $integrityChecker
     */
    public function __construct(IntegrityChecker $integrityChecker)
    {
        $this->integrityChecker = $integrityChecker;
    }
    
    /**
     * Reads the schema from IntegrityChecker and creates mapping data
     * 
     * @return void
     */
    public function initialize()
    {
        foreach ($this->integrityChecker->getTablesSchema() as $table) {
            $this->loadMetadataForTable($table);
        }
        $this->initialized = true;
    }
    
    protected function loadMetadataForTable(Table $table)
    {
        $this->metadata[$table->getName()] = array();
        foreach ($table->getColumns() as $colName=>$column) {
            
            $mapping['fieldname'] = $colName;
            $mapping['type'] = $column->getType();
            $mapping['length'] = $column->getLength();
            $mapping['nullable'] = $column->getNotnull();
            $mapping['platformOptions'] = $column->getPlatformOptions();
            $mapping['precision'] = $column->getPrecision();
            $mapping['scale'] = $column->getScale();
            $mapping['default'] = $column->getDefault();
            $mapping['columnDefinition'] = $column->getColumnDefinition();
            $mapping['autoincrement'] = $column->getAutoincrement();

            
            $this->metadata[$table->getName()][$colName] = $mapping;
        }
        
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
        $this->loadMappingFile($this->locator->findMappingFile($className));
    }

    /**
     * Gets the names of all mapped classes known to this driver.
     *
     * @return array The names of all mapped classes known to this driver.
     */
    public function getAllClassNames()
    {
        
    }
    
    /**
     * Returns the metadata for a given class name.
     * @param string $className
     *
     * @return The class metadata.
     */
    public function getClassMetadata($className)
    {
        if (array_key_exists($className, $this->metadata)) {
            return $this->metadata[$className];
        }
        
        return false;
    }

    /**
     * Not implemented, always returns false.
     *
     * @param string $className
     *
     * @return boolean
     */
    public function isTransient($className)
    {
        return false;
    }
}
