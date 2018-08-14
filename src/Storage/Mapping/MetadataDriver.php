<?php

namespace Bolt\Storage\Mapping;

use Bolt\Collection\Arr;
use Bolt\Config;
use Bolt\Configuration\ConfigurationValueProxy;
use Bolt\Exception\StorageException;
use Bolt\Filesystem\Handler\Image;
use Bolt\Storage\CaseTransformTrait;
use Bolt\Storage\Database\Schema\Manager;
use Bolt\Storage\Entity;
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
        'bolt_authtoken'   => Entity\Authtoken::class,
        'bolt_cron'        => Entity\Cron::class,
        'bolt_field_value' => Entity\FieldValue::class,
        'bolt_log'         => Entity\Log::class,
        'bolt_log_change'  => Entity\LogChange::class,
        'bolt_log_system'  => Entity\LogSystem::class,
        'bolt_relations'   => Entity\Relations::class,
        'bolt_taxonomy'    => Entity\Taxonomy::class,
        'bolt_users'       => Entity\Users::class,
    ];

    /** @var array */
    protected $typemap;
    /** @var NamingStrategy */
    protected $namingStrategy;
    /** @var array */
    protected $aliases = [];
    /** @var array */
    protected $generalConfig;

    /**
     * Keeps a reference of which metadata is not mapped to
     * a specific entity.
     *
     * @var array
     */
    protected $unmapped;

    /** @var string A default entity for any table not matched */
    protected $fallbackEntity = Entity\Content::class;
    /** @var bool */
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
    public function __construct(
        Manager $schemaManager,
        ConfigurationValueProxy $contenttypes,
        ConfigurationValueProxy $taxonomies,
        array $typemap,
        NamingStrategy $namingStrategy = null
    ) {
        $this->schemaManager = $schemaManager;
        $this->contenttypes = $contenttypes;
        $this->taxonomies = $taxonomies;
        $this->typemap = $typemap;
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * Reads the schema from Bolt\Storage\Database\Schema\Manager and creates mapping data.
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
     * Setup some short aliases so non prefixed keys can be used to get metadata.
     */
    public function initializeShortAliases()
    {
        foreach ($this->schemaManager->getSchemaTables() as $table) {
            if ($tableName = $table->getName()) {
                $mainAlias = $this->getContentTypeFromAlias($table->getOption('alias'));
                $this->aliases[$mainAlias] = $tableName;
                $slugAlias = $this->normalizeClassName($this->getContentTypeFromAlias($table->getOption('alias'), true));
                $singularAlias = $this->normalizeClassName($this->getContentTypeFromAlias($table->getOption('alias'), 'singular'));

                if ($mainAlias !== $slugAlias) {
                    $this->aliases[$slugAlias] = $tableName;
                }
                if ($mainAlias !== $singularAlias) {
                    $this->aliases[$singularAlias] = $tableName;
                }
            }
        }
    }

    /**
     *  This seeds the defaultAliases array with the correctly prefixed mappings.
     */
    public function initializeDefaultAliases()
    {
        foreach ($this->defaultAliases as $def => $entityClass) {
            $table = $this->namingStrategy->classToTableName($entityClass);
            $this->defaultAliases[$table] = $entityClass;
        }

        foreach ($this->aliases as $prefixed) {
            $entity = isset($this->defaultAliases[$prefixed]) ? $this->defaultAliases[$prefixed] : null;
            if ($entity !== null) {
                $this->setDefaultAlias($prefixed, $entity);
            }
        }
    }

    /**
     * Getter for aliases.
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->aliases;
    }

    /**
     * Method will try to find an entity class name to handle data,
     * alternatively falling back to $this->fallbackEntity.
     *
     * @param string|ContentType $alias
     *
     * @return string Fully Qualified Class Name
     */
    public function resolveClassName($alias)
    {
        // Make sure we have string, type-cast Contenttype if needed.
        $alias = (string) $alias;

        if (class_exists($alias)) {
            return $alias;
        }

        if (array_key_exists($alias, $this->aliases)) {
            $class = $this->aliases[$alias];
            if (class_exists($class)) {
                return $class;
            }
        }
        if (array_key_exists($alias, $this->defaultAliases)) {
            $class = $this->defaultAliases[$alias];
            if (class_exists($class)) {
                return $class;
            }
        }
        if (array_key_exists($alias, $this->defaultAliases)) {
            $class = $this->defaultAliases[$alias];
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

        $contentKey = $this->getContentTypeFromAlias($table->getOption('alias'));
        $this->metadata[$className] = [];
        $this->metadata[$className]['identifier'] = $table->getPrimaryKey();
        $this->metadata[$className]['table'] = $table->getName();
        $this->metadata[$className]['boltname'] = $contentKey;
        if (isset($this->contenttypes[$contentKey]['class'])) {
            $this->metadata[$className]['class'] = $this->contenttypes[$contentKey]['class'];
        }
        foreach ($table->getColumns() as $colName => $column) {
            $mapping = [
                'fieldname'        => $column->getName(),
                'attribute'        => $this->camelize($column->getName()),
                'type'             => $column->getType()->getName(),
                'fieldtype'        => $this->getFieldTypeFor($contentKey, $column),
                'length'           => $column->getLength(),
                'nullable'         => !$column->getNotnull(),
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
        if ($contentKey && isset($this->contenttypes[$contentKey])) {
            $this->setRelations($contentKey, $className);
            $this->setIncomingRelations($contentKey, $className);
            $this->setTaxonomies($contentKey, $className);
            $this->setTemplatefields($contentKey, $className);
            $this->setRepeaters($contentKey, $className);
        }

        foreach ($this->getAliases() as $alias => $tablename) {
            if (array_key_exists($tablename, $this->metadata)) {
                $this->metadata[$alias] = $this->metadata[$tablename];
            } elseif (
                array_key_exists($tablename, $this->defaultAliases) &&
                array_key_exists($this->defaultAliases[$tablename], $this->metadata)
            ) {
                $this->metadata[$alias] = $this->metadata[$this->defaultAliases[$tablename]];
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

            if (in_array($data['type'], ['repeater', 'block'])) {
                if ($data['type'] === 'repeater') {
                    $this->normalizeFieldTypes($data['fields']);
                }
                if ($data['type'] === 'block') {
                    foreach ($data['fields'] as $block => &$fields) {
                        $this->normalizeFieldTypes($fields['fields']);
                    }
                    $mapping['fieldtype'] = $this->typemap['block'];
                }

                if ($standalone) {
                    return $data;
                }

                $this->metadata[$className]['fields'][$key] = $mapping;
                foreach ((array) $data['fields'] as &$field) {
                    $this->postProcessField($field);
                }

                $this->metadata[$className]['fields'][$key]['data'] = $data;
            }
        }

        return null;
    }

    /**
     * This is a patch method that reproduces some of the setup that happens for standard fields in Bolt/Config
     * in future versions this will be handled by the individual mapping classes but remains here until they are able
     * to take over completely.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param array $field
     */
    protected function postProcessField(array $field)
    {
        // We can only do this post-processing if the General Config has been setup and passed in.
        if (!$this->generalConfig instanceof Config) {
            return;
        }

        // If the field doesn't have a type set, we're also not interested.
        if (!isset($field['type'])) {
            return;
        }

        $acceptableFileTypes = $this->generalConfig->get('general/accept_file_types');

        // If field is a "file" type, make sure the 'extensions' are set, and it's an array.
        if ($field['type'] === 'file' || $field['type'] === 'filelist') {
            if (empty($field['extensions'])) {
                $field['extensions'] = $acceptableFileTypes;
            }

            $field['extensions'] = (array) $field['extensions'];
        }

        // If field is an "image" type, make sure the 'extensions' are set, and it's an array.
        if ($field['type'] === 'image' || $field['type'] === 'imagelist') {
            if (empty($field['extensions'])) {
                $field['extensions'] = array_intersect(
                    Image\Type::getExtensions(),
                    $acceptableFileTypes
                );
            }

            $field['extensions'] = (array) $field['extensions'];
        }

        // Make indexed arrays into associative for select fields
        // e.g.: [ 'yes', 'no' ] => { 'yes': 'yes', 'no': 'no' }
        if ($field['type'] === 'select' && isset($field['values']) && Arr::isIndexed($field['values'])) {
            $field['values'] = array_combine($field['values'], $field['values']);
        }
    }

    /**
     * This is a patch method that allows the general app config to be injected into this class. It is only to be used
     * for providing Backwards Compatibility and will be removed once the general mapping config is ready to take over.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param $config
     */
    public function setGeneralConfig($config)
    {
        $this->generalConfig = $config;
    }

    /**
     * Internal method to fix or patch any field mappings
     *
     * @param array $fields
     */
    protected function normalizeFieldTypes(array &$fields)
    {
        foreach ($fields as $rkey => &$value) {
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
     */
    public function setRelations($contentKey, $className)
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
     * @param string $contentKey
     * @param string $className
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
     */
    public function setTaxonomies($contentKey, $className)
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
     */
    public function setTemplatefields($contentKey, $className)
    {
        if (!isset($this->contenttypes[$contentKey]['templatefields'])) {
            return;
        }

        $config = $this->contenttypes[$contentKey]['templatefields'];

        foreach ($config as &$template) {
            foreach ($template['fields'] as &$field) {
                $this->postProcessField($field);
            }
        }
        $mapping = [
            'fieldname' => 'templatefields',
            'type'      => 'json',
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
     *
     * @throws StorageException
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata = null)
    {
        $fullClassName = null;
        if ($metadata === null) {
            $fullClassName = $this->resolveClassName($className);
            $metadata = new BoltClassMetadata($fullClassName, $this->namingStrategy);
        }
        if (!$this->initialized) {
            $this->initialize();
        }

        $className = $this->normalizeClassName($className);
        $resolvedClassName = $fullClassName && array_key_exists($fullClassName, $this->metadata)
            ? $fullClassName
            : $className
        ;

        if (array_key_exists($resolvedClassName, $this->metadata)) {
            $data = $this->metadata[$resolvedClassName];
            $metadata->setTableName($data['table']);
            $metadata->setIdentifier($data['identifier']);
            $metadata->setFieldMappings($data['fields']);
            $metadata->setBoltName($data['boltname']);
            if (isset($data['entity'])) {
                $metadata->setName($data['entity']);
            } elseif (isset($data['class'])) {
                $metadata->setName($data['class']);
            }

            return $metadata;
        }

        throw new StorageException("Attempted to load mapping data for unmapped class $className");
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
        $type = null;
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

    /**
     * @param string $contentType
     * @param string $column
     * @param string $field
     *
     * @return array
     */
    public function getFieldMetadata($contentType, $column, $field = null)
    {
        if ($field !== null) {
            if (isset($this->metadata[$contentType]['fields'][$column]['data']['fields'][$field])) {
                $metadata = $this->metadata[$contentType]['fields'][$column]['data']['fields'][$field];
            } else {
                throw new \RuntimeException(sprintf('No metadata set for field type %s', $field));
            }
        } elseif (isset($this->metadata[$contentType]['fields'][$column])) {
            $metadata = $this->metadata[$contentType]['fields'][$column];
        } else {
            throw new \RuntimeException(sprintf('%s metadata does not contain a definition for the field %s', $contentType, $column));
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
     * @return ClassMetadata|false the class metadata
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
     * @return bool
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
     * Given a tablename or slug get the correct Bolt keyname from the config.
     *
     * @param $alias
     * @param bool $forceSlug
     *
     * @return string $key
     */
    public function getContentTypeFromAlias($alias, $forceSlug = false)
    {
        foreach ($this->contenttypes->getData() as $key => $contenttype) {
            if ($forceSlug && $forceSlug === 'singular') {
                if (isset($contenttype['singular_slug']) && ($contenttype['slug'] === $alias || $contenttype['tablename'] === $alias)) {
                    return $contenttype['singular_slug'];
                }
            } elseif ($forceSlug) {
                if (isset($contenttype['slug']) && ($contenttype['slug'] === $alias || $contenttype['tablename'] === $alias)) {
                    return $contenttype['slug'];
                }
            }
            if (isset($contenttype['tablename']) && $contenttype['tablename'] === $alias) {
                return $key;
            }

            if (isset($contenttype['slug']) && $contenttype['slug'] === $alias) {
                return $key;
            }
        }

        return $alias;
    }

    /**
     * @param $alias
     *
     * @throws StorageException
     *
     * @return array|ConfigurationValueProxy|mixed|null
     */
    public function createContentType($alias)
    {
        $ct = $this->normalizeBoltName($alias);

        return
            isset($this->contenttypes[$ct])
            ? new ContentType($ct, $this->contenttypes[$ct])
            : null;
    }

    /**
     * This helper function takes all of the potential alias names for a contenttype and resolves it to the
     * standardized Bolt name.
     *
     * @param $alias
     *
     * @throws StorageException
     *
     * @return null|string
     */
    protected function normalizeBoltName($alias)
    {
        $metadata = $this->loadMetadataForClass($alias);

        return $metadata ? $metadata->getBoltName() : null;
    }
}
