<?php
namespace Bolt\Storage\Mapping;

use Bolt\Configuration\ConfigurationValueProxy;
use Bolt\Exception\StorageException;
use Bolt\Storage\CaseTransformTrait;
use Bolt\Storage\Database\Schema\Manager;
use Bolt\Storage\Mapping\ClassMetadata as BoltClassMetadata;
use Bolt\Storage\NamingStrategy;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\DBAL\Schema\Column;
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
    use CaseTransformTrait;

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
        'bolt_authtoken'   => 'Bolt\Storage\Entity\Authtoken',
        'bolt_cron'        => 'Bolt\Storage\Entity\Cron',
        'bolt_field_value' => 'Bolt\Storage\Entity\FieldValue',
        'bolt_log'         => 'Bolt\Storage\Entity\Log',
        'bolt_log_change'  => 'Bolt\Storage\Entity\LogChange',
        'bolt_log_system'  => 'Bolt\Storage\Entity\LogSystem',
        'bolt_relations'   => 'Bolt\Storage\Entity\Relations',
        'bolt_taxonomy'    => 'Bolt\Storage\Entity\Taxonomy',
        'bolt_users'       => 'Bolt\Storage\Entity\Users',
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
     * @param Manager                 $schemaManager
     * @param ConfigurationValueProxy $contenttypes
     * @param ConfigurationValueProxy $taxonomies
     * @param array                   $typemap
     * @param NamingStrategy          $namingStrategy
     */
    public function __construct(Manager $schemaManager, ConfigurationValueProxy $contenttypes, ConfigurationValueProxy $taxonomies, array $typemap, NamingStrategy $namingStrategy = null)
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
        $this->initializeDefaultAliases();
        foreach ($this->schemaManager->getSchemaTables() as $table) {
            $this->loadMetadataForTable($table);
        }
        $this->initialized = true;
    }

    /**
     * Setup some short aliases so non prefixed keys can be used to get metadata
     */
    public function initializeShortAliases()
    {
        foreach ($this->schemaManager->getSchemaTables() as $table) {
            if ($tableName = $table->getName()) {
                $mainAlias = $this->getContentTypeFromAlias($table->getOption('alias'));
                $this->aliases[$mainAlias] = $tableName;
                $slugAlias = $this->getContentTypeFromAlias($table->getOption('alias'), true);
                if ($mainAlias !== $slugAlias) {
                    $this->aliases[$slugAlias] = $tableName;
                }
            }
        }
    }

    /**
     *  This seeds the defaultAliases array with the correctly prefixed mappings
     */
    public function initializeDefaultAliases()
    {
        foreach ($this->aliases as $prefixed) {
            $entity = isset($this->defaultAliases[$prefixed]) ? $this->defaultAliases[$prefixed] : null;
            if ($entity !== null) {
                $this->setDefaultAlias($prefixed, $entity);
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

        $contentKey = $this->getContenttypeFromAlias($table->getOption('alias'));
        $this->metadata[$className] = [];
        $this->metadata[$className]['identifier'] = $table->getPrimaryKey();
        $this->metadata[$className]['table'] = $table->getName();
        $this->metadata[$className]['boltname'] = $contentKey;
        foreach ($table->getColumns() as $colName => $column) {
            $mapping = [
                'fieldname'        => $column->getName(),
                'attribute'        => $this->camelize($column->getName()),
                'type'             => $column->getType()->getName(),
                'fieldtype'        => $this->getFieldTypeFor($contentKey, $column),
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

            if (isset($this->contenttypes[$contentKey]) && isset($this->contenttypes[$contentKey]['fields'][$colName])) {
                $this->metadata[$className]['fields'][$colName]['data'] = $this->contenttypes[$contentKey]['fields'][$colName];
            }
        }

        // This loop checks the contenttypes definition for any non-db fields and adds them.
        if ($contentKey && isset($this->contenttypes[$contentKey])) {
            $this->setRelations($contentKey, $className, $table);
            $this->setIncomingRelations($contentKey, $className);
            $this->setTaxonomies($contentKey, $className, $table);
            $this->setTemplatefields($contentKey, $className, $table);
            $this->setRepeaters($contentKey, $className);
        }

        foreach ($this->getAliases() as $alias => $table) {
            if (array_key_exists($table, $this->metadata)) {
                $this->metadata[$alias] = $this->metadata[$table];
            }
        }
    }

    /**
     * @param string $contentKey
     * @param string $className
     * @param array  $inputData
     *
     * @return array|null
     */
    public function setRepeaters($contentKey, $className, $inputData = null)
    {
        $standalone = false;

        if ($inputData === null && !isset($this->contenttypes[$contentKey])) {
            return null;
        }

        if ($inputData === null) {
            $inputData = $this->contenttypes[$contentKey]['fields'];
        } else {
            $standalone = true;
        }

        foreach ($inputData as $key => $data) {
            $mapping = [
                'fieldname'        => $key,
                'attribute'        => $this->camelize($key),
                'type'             => 'null',
                'fieldtype'        => $this->typemap['repeater'],
                'tables'           => [
                    'field'        => $this->schemaManager->getTableName('field'),
                    'field_value'  => $this->schemaManager->getTableName('field_value'),
                ],
            ];

            if ($data['type'] === 'repeater') {
                foreach ($data['fields'] as $rkey => &$value) {
                    $value['fieldname'] = $rkey;

                    if ($value['type'] === 'select' && isset($value['multiple']) && $value['multiple'] === true) {
                        $value['type'] = 'selectmultiple';
                    }

                    if (isset($this->typemap[$value['type']])) {
                        $value['fieldtype'] = $this->typemap[$value['type']];
                    } else {
                        $value['fieldtype'] = $this->typemap['text'];
                    }
                }

                if ($standalone) {
                    return $data;
                }

                $this->metadata[$className]['fields'][$key] = $mapping;
                $this->metadata[$className]['fields'][$key]['data'] = $data;
            }
        }
    }

    /**
     * This is a helper method to get a correct mapping from an array config. It's designed to take raw array config
     * to generate a correct format mapping for repeaters.
     *
     * @param array $config
     *
     * @return array
     */
    public function getRepeaterMapping(array $config)
    {
        $mapping = ['data' => null];
        $mapping['data'] = $this->setRepeaters(null, null, $config);

        return $mapping;
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
     * @param $contentKey
     * @param $className
     */
    public function setIncomingRelations($contentKey, $className)
    {
        if (!isset($this->contenttypes[$contentKey])) {
            return;
        }
        $mapping = [
            'fieldname' => 'incomingrelation',
            'type'      => 'null',
            'fieldtype' => $this->typemap['incomingrelation'],
            'target'    => $this->schemaManager->getTableName('relations'),
        ];

        $this->metadata[$className]['fields']['incomingrelation'] = $mapping;
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

    public function setContentFields($contentKey, $className, $table)
    {
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

        $className = $this->normalizeClassName($className);

        if (array_key_exists($className, $this->metadata)) {
            $data = $this->metadata[$className];
            $metadata->setTableName($data['table']);
            $metadata->setIdentifier($data['identifier']);
            $metadata->setFieldMappings($data['fields']);
            $metadata->setBoltName($data['boltname']);

            return $metadata;
        } else {
            throw new StorageException("Attempted to load mapping data for unmapped class $className");
        }
    }

    public function loadMetadataForFields(array $fields)
    {
        foreach ($fields as $name => &$field) {
            $type = $field['type'];
            if (isset($this->typemap[$type])) {
                $type = new $this->typemap[$type]();
            } else {
                $type = new $this->typemap['text']();
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
     * @param null                         $field  Optional field value for repeaters/array based columns
     *
     * @return string
     */
    public function getFieldTypeFor($name, $column, $field = null)
    {
        if ($column instanceof Column) {
            if ($column->getType()) {
                $type = get_class($column->getType());
            }
            $column = $column->getName();
        }
        if ($field !== null) {
            if (isset($this->contenttypes[$name]) && isset($this->contenttypes[$name]['fields'][$column]['fields'][$field])) {
                $type = $this->contenttypes[$name]['fields'][$column]['fields'][$field]['type'];
            }
        } elseif (isset($this->contenttypes[$name]) && isset($this->contenttypes[$name]['fields'][$column])) {
            $type = $this->contenttypes[$name]['fields'][$column]['type'];
        }

        if ($column === 'slug') {
            $type = 'slug';
        }

        if ($type === 'select' && isset($this->contenttypes[$name]['fields'][$column]['multiple']) && $this->contenttypes[$name]['fields'][$column]['multiple'] === true) {
            $type = 'selectmultiple';
        }

        if ($type && isset($this->typemap[$type])) {
            $type = $this->typemap[$type];
        } else {
            $type = $this->typemap['text'];
        }

        return $type;
    }

    public function getFieldMetadata($contenttype, $column, $field = null)
    {
        if ($field !== null) {
            if (isset($this->metadata[$contenttype]['fields'][$column]['data']['fields'][$field])) {
                $metadata = $this->metadata[$contenttype]['fields'][$column]['data']['fields'][$field];
            }
        } elseif (isset($this->metadata[$contenttype]['fields'][$column])) {
            $metadata = $this->metadata[$contenttype]['fields'][$column];
        }

        return $metadata;
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
     * Performs basic normalisation on a searched for class name to make sure it
     * conforms to lookup format.
     *
     * @param $className
     *
     * @return string
     */
    protected function normalizeClassName($className)
    {
        $className = str_replace('-', '_', $className);

        return $className;
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

    /**
     * @return array
     */
    public function getTaxonomyConfig()
    {
        return $this->taxonomies;
    }

    /**
     * Given a tablename or slug get the correct Bolt keyname from the config
     *
     * @param $alias
     * @param bool $forceSlug
     *
     * @return string $key
     */
    public function getContentTypeFromAlias($alias, $forceSlug = false)
    {
        foreach ($this->contenttypes->getData() as $key => $contenttype) {
            if ($forceSlug) {
                if (isset($contenttype['slug']) && ($contenttype['slug'] == $alias || $contenttype['tablename'] == $alias)) {
                    return $contenttype['slug'];
                }
            }
            if (isset($contenttype['tablename']) && $contenttype['tablename'] == $alias) {
                return $key;
            }

            if (isset($contenttype['slug']) && $contenttype['slug'] == $alias) {
                return $key;
            }
        }

        return $alias;
    }
}
