<?php

namespace Bolt\Mapping;

use Bolt\Database\IntegrityChecker;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
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
     * array of metadata mappings
     */
    protected $metadata;
    
    /**
     * @var array
     */
    protected $defaultAliases = array(
        'bolt_authtoken' => 'Bolt\Entity\AuthToken',
        'bolt_cron' => 'Bolt\Entity\Cron',
        'bolt_log' => 'Bolt\Entity\Log',
        'bolt_log_change' => 'Bolt\Entity\LogChange',
        'bolt_log_system' => 'Bolt\Entity\LogSystem',
        'bolt_relations' => 'Bolt\Entity\Relations',
        'bolt_taxonomy' => 'Bolt\Entity\Taxonomy',
        'bolt_users' => 'Bolt\Entity\Users'
    );
    
    /**
     * @var string - a default entity for any table not matched
     */
    protected $fallbackEntity = 'Bolt\Entity\Content';
    
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
        print_r($this->metadata); exit;
    }
    
    protected function loadMetadataForTable(Table $table)
    {
        $tblName = $table->getName();
        
        if (isset($this->defaultAliases[$tblName])) {
            $className = $this->defaultAliases[$tblName];
        } else {
            $className = $tblName;
        }
        
        $this->metadata[$className] = array();
        $this->metadata[$className]['identifier'] = $table->getPrimaryKey();
        $this->metadata[$className]['table'] = $table->getName();
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
            
            $this->metadata[$className]['fields'][$colName] = $mapping;
        }
    }

    /**
     * @param string $className Fully Qualified name
     * @param ClassMetadata $metadata instance of metadata class to load with data
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        if (array_key_exists($className, $this->metadata)) {
            $data = $this->metadata[$className];
        } else {
            throw new \Exception("Attempted to load mapping data for unmapped class $classname");
        }
        
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
     * Adds an alias mapping from an internal name to a Fully Qualified Entity.
     *
     * @param string $alias.
     * @param string $entity.
     *
     * @return void.
     */
    public function setDefaultAlias($alias, $entity)
    {
        $this->defaultAliases[$alias] = $entity;
    }
    
    /**
     * Returns the metadata for a given class name.
     * @param string $className
     *
     * @return The class metadata.
     */
    public function getClassMetadata($className)
    {
        if (!$this->initialized) {
            $this->initialize();
        }
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
