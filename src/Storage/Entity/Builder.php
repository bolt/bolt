<?php
namespace Bolt\Storage\Entity;

use Bolt\Storage\FieldFactory;
use Bolt\Storage\Hydrator;
use Bolt\Storage\Mapping\MetadataDriver;

/**
 * Builder class to create entity objects and populate with data.
 */
class Builder
{
    /**
     *
     * The class to use for new instances.
     *
     * @var string
     *
     */
    protected $class = 'Bolt\Storage\Entity\Content';
    
    protected $metadata;
    protected $fieldFactory;
    protected $transformers = [];
    
    public function __construct(MetadataDriver $metadata, FieldFactory $fieldFactory)
    {
        $this->metadata = $metadata;
        $this->fieldFactory = $fieldFactory;
    }
    
    public function setClass($class)
    {
        $this->class = $class;
    }
    
    public function setTransformer($fieldTypeClass, callable $handler)
    {
        $this->transformers[$fieldTypeClass] = $handler;
    }
    
    
    /**
     *
     * Creates a new entity object.
     *
     * @param array|object $data Data to load into the entity.
     *
     * @return GenericEntity
     *
     */
    public function create($data)
    {
        $class = $this->class;
        $classMetadata = $this->metadata->loadMetadataForClass($class);
        
        $entity = new $class;
        
        // set fields
        foreach ($this->metadata->getFieldMappings() as $key => $mapping) {
            if (array_key_exists($key, $data)) {
                $fieldType = $this->fieldFactory->get($mapping['fieldtype'], $mapping);
                
                // If we have a transformer setup then this takes precedence
                $handler = $this->handlers[$mapping['fieldtype']];
                
                if ($handler) {
                    $value = call_user_func_array($handler, [$entity, $data]);
                } else {
                    $value = call_user_func_array([$fieldType, 'set'], [$entity, $data]);
                }
                
            }
        }
        
        return $entity;
    }
    
    
}
