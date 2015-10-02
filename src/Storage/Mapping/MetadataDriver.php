<?php
namespace Bolt\Storage\Mapping;

use Bolt\Storage\Database\Schema\Manager;
use Bolt\Storage\Mapping\ClassMetadata as BoltClassMetadata;
use Bolt\Storage\NamingStrategy;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\DBAL\Schema\Table;

/**
 * This is a Bolt specific metadata driver that provides mapping information
 * for the internal and user-defined schemas. To do this it takes in the
 * constructor, an instance of Bolt\Storage\Database\Schema\Manager and uses
 * this to read in the schema.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class MetadataDriver implements MappingDriver
{
    /** @var \Bolt\Storage\Database\Schema\Manager */
    protected $schemaManager;
    /** @var array */
    protected $contenttypes;
    /** @var array taxonomy configuration */
    protected $taxonomies;
    /** @var array metadata mappings */
    protected $metadata;

    /** @var array */
    protected $defaultAliases = [
        'bolt_authtoken'  => 'Bolt\Storage\Entity\Authtoken',
        'bolt_cron'       => 'Bolt\Storage\Entity\Cron',
        'bolt_log'        => 'Bolt\Storage\Entity\Log',
        'bolt_log_change' => 'Bolt\Storage\Entity\LogChange',
        'bolt_log_system' => 'Bolt\Storage\Entity\LogSystem',
        'bolt_relations'  => 'Bolt\Storage\Entity\Relations',
        'bolt_taxonomy'   => 'Bolt\Storage\Entity\Taxonomy',
        'bolt_users'      => 'Bolt\Storage\Entity\Users'
    ];

    /** @var array */
    protected $typemap;
    /** @var NamingStrategy */
    protected $namingStrategy;
    /** @var array */
    protected $aliases = [];

    /**
     * Keeps a reference of which metadata is not mapped to
     * a specific entity.
     *
     * @var array $unmapped
     */
    protected $unmapped;

    /** @var string A default entity for any table not matched */
    protected $fallbackEntity = 'Bolt\Storage\Entity\Content';
    /** @var boolean */
    protected $initialized = false;

    /**
     * Constructor.
     *
     * @param Manager $schemaManager
     * @param array   $contenttypes
     * @param array   $taxonomies
     * @param array   $typemap
     */
    public function __construct(Manager $schemaManager, array $contenttypes, array $taxonomies, array $typemap, NamingStrategy $namingStrategy = null)
    {
        $this->schemaManager = $schemaManager;
        $this->contenttypes = $contenttypes;
        $this->taxonomies = $taxonomies;
        $this->typemap = $typemap;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * Reads the schema from Bolt\Storage\Database\Schema\Manager and creates mapping data
     */
    public function initialize()
    {
        $this->initializeShortAliases();
        foreach ($this->schemaManager->getTablesSchema() as $table) {
            $this->loadMetadataForTable($table);
        }
        $this->initialized = true;
    }

    /**
     * Setup some short aliases so non prefixed keys can be used to get metadata
     */
    public function initializeShortAliases()
    {
        foreach ($this->schemaManager->getTablesSchema() as $table) {
            if ($tableName = $table->getName()) {
                $this->aliases[$this->schemaManager->getKeyForTable($table->getName())] = $tableName;
            }
        }
    }

    /**
     * Getter for aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Method will try to find an entity class name to handle data,
     * alternatively falling back to $this->fallbackEntity
     *
     * @param string $alias
     *
     * @return string Fully Qualified Class Name
     */
    public function resolveClassName($alias)
    {
        if (class_exists($alias)) {
            return $alias;
        }

        if (array_key_exists($alias, $this->aliases)) {
            $class = $this->aliases[$alias];
            if (class_exists($class)) {
                return $class;
            }
        }

        return $this->fallbackEntity;
    }

    /**
     * Load the metadata for a table.
     *
     * @param Table $table
     */
    protected function loadMetadataForTable(Table $table)
    {
        $tblName = $table->getName();

        if (isset($this->defaultAliases[$tblName])) {
            $className = $this->defaultAliases[$tblName];
        } else {
            $className = $tblName;
            $this->unmapped[] = $tblName;
        }

        $contentKey = $this->schemaManager->getKeyForTable($tblName);

        $this->metadata[$className] = [];
        $this->metadata[$className]['identifier'] = $table->getPrimaryKey();
        $this->metadata[$className]['table'] = $table->getName();
        $this->metadata[$className]['boltname'] = $contentKey;
        foreach ($table->getColumns() as $colName => $column) {
            $mapping = [
                'fieldname'        => $colName,
                'type'             => $column->getType()->getName(),
                'fieldtype'        => $this->getFieldTypeFor($table->getName(), $column),
                'length'           => $column->getLength(),
                'nullable'         => $column->getNotnull(),
                'platformOptions'  => $column->getPlatformOptions(),
                'precision'        => $column->getPrecision(),
                'scale'            => $column->getScale(),
                'default'          => $column->getDefault(),
                'columnDefinition' => $column->getColumnDefinition(),
                'autoincrement'    => $column->getAutoincrement(),
            ];

            $this->metadata[$className]['fields'][$colName] = $mapping;

            if (isset($this->contenttypes[$contentKey]['fields'][$colName])) {
                $this->metadata[$className]['fields'][$colName]['data'] = $this->contenttypes[$contentKey]['fields'][$colName];
            }
        }

        // This loop checks the contenttypes definition for any non-db fields and adds them.
        if ($contentKey) {
            $this->setRelations($contentKey, $className, $table);
            $this->setTaxonomies($contentKey, $className, $table);
            $this->setTemplatefields($contentKey, $className, $table);
        }

        foreach ($this->getAliases() as $alias => $table) {
            if (array_key_exists($table, $this->metadata)) {
                $this->metadata[$alias] = $this->metadata[$table];
            }
        }
    }

    /**
     * Set the relationship.
     *
     * @param string $contentKey
     * @param string $className
     * @param Table  $table
     */
    public function setRelations($contentKey, $className, $table)
    {
        if (!isset($this->contenttypes[$contentKey]['relations'])) {
            return;
        }
        foreach ($this->contenttypes[$contentKey]['relations'] as $key => $data) {
            if (isset($data['alias'])) {
                $relationKey = $data['alias'];
            } else {
                $relationKey = $key;
            }

            $mapping = [
                'fieldname' => $relationKey,
                'type'      => 'null',
                'fieldtype' => $this->typemap['relation'],
                'entity'    => $this->resolveClassName($relationKey),
                'target'    => $this->schemaManager->getTableName('relations'),
            ];

            $this->metadata[$className]['fields'][$relationKey] = $mapping;
            $this->metadata[$className]['fields'][$relationKey]['data'] = $data;
        }
    }

    /**
     * Set the taxonomy.
     *
     * @param string $contentKey
     * @param string $className
     * @param Table  $table
     */
    public function setTaxonomies($contentKey, $className, $table)
    {
        if (!isset($this->contenttypes[$contentKey]['taxonomy'])) {
            return;
        }

        foreach ($this->contenttypes[$contentKey]['taxonomy'] as $taxonomytype) {
            $taxonomyConfig = $this->taxonomies[$taxonomytype];

            if (isset($taxonomyConfig['alias'])) {
                $taxonomy = $taxonomyConfig['alias'];
            } else {
                $taxonomy = $taxonomytype;
            }

            $mapping = [
                'fieldname' => $taxonomy,
                'type'      => 'null',
                'fieldtype' => $this->typemap['taxonomy'],
                'target'    => $this->schemaManager->getTableName('taxonomy'),
            ];

            $this->metadata[$className]['fields'][$taxonomy] = $mapping;
            $this->metadata[$className]['fields'][$taxonomy]['data'] = $taxonomyConfig;
        }
    }

    /**
     * Setup a templatefields field if needed.
     *
     * @param string $contentKey
     * @param string $className
     * @param Table  $table
     */
    public function setTemplatefields($contentKey, $className, $table)
    {
        if (!isset($this->contenttypes[$contentKey]['templatefields'])) {
            return;
        }

        $config = $this->contenttypes[$contentKey]['templatefields'];

        $mapping = [
            'fieldname' => 'templatefields',
            'type'      => 'json_array',
            'fieldtype' => $this->typemap['templatefields'],
            'config'    => $config,
        ];

        $this->metadata[$className]['fields']['templatefields'] = $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata = null)
    {
        if (null === $metadata) {
            $fullClassName = $this->resolveClassName($className);
            $metadata = new BoltClassMetadata($fullClassName, $this->namingStrategy);
        }
        if (!$this->initialized) {
            $this->initialize();
        }
        if (array_key_exists($className, $this->metadata)) {
            $data = $this->metadata[$className];
            $metadata->setTableName($data['table']);
            $metadata->setIdentifier($data['identifier']);
            $metadata->setFieldMappings($data['fields']);
            $metadata->setBoltName($data['boltname']);
            return $metadata;
        } else {
            throw new \Exception("Attempted to load mapping data for unmapped class $className");
        }
    }

    public function loadMetadataForFields(array $fields)
    {
        foreach ($fields as $name => &$field) {
            $type = $field['type'];
            if (isset($this->typemap[$type])) {
                $type = new $this->typemap[$type];
            } else {
                $type = new $this->typemap['text'];
            }
            $field['fieldtype'] = $type;
            $field['fieldname'] = $name;
        }

        return $fields;
    }

    /**
     * Get the field type for a given column.
     *
     * @param string                       $name
     * @param \Doctrine\DBAL\Schema\Column $column
     */
    protected function getFieldTypeFor($name, $column)
    {
        $contentKey = $this->schemaManager->getKeyForTable($name);
        if ($contentKey && isset($this->contenttypes[$contentKey]['fields'][$column->getName()])) {
            $type = $this->contenttypes[$contentKey]['fields'][$column->getName()]['type'];
        } elseif ($column->getType()) {
            $type = get_class($column->getType());
        }

        if ($type === 'select' && isset($this->contenttypes[$contentKey]['fields'][$column->getName()]['multiple']) && $this->contenttypes[$contentKey]['fields'][$column->getName()]['multiple'] === true) {
            $type = 'selectmultiple';
        }

        if (isset($this->typemap[$type])) {
            $type = $this->typemap[$type];
        } else {
            $type = $this->typemap['text'];
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllClassNames()
    {
        return array_keys($this->metadata);
    }

    /**
     * Gets a list of tables that are not mapped to specific entities.
     *
     * @return array
     */
    public function getUnmapped()
    {
        return $this->unmapped;
    }

    /**
     * Adds an alias mapping from an internal name to a Fully Qualified Entity.
     *
     * @param string $alias
     * @param string $entity
     *
     * @return void
     */
    public function setDefaultAlias($alias, $entity)
    {
        $this->defaultAliases[$alias] = $entity;
    }

    /**
     * Returns the metadata for a given class name.
     *
     * @param string $className
     *
     * @return ClassMetadata|false The class metadata.
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
