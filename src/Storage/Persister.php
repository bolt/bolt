<?php
namespace Bolt\Storage;

use Bolt\Storage\Field\Type\FieldTypeInterface;
use Bolt\Storage\Mapping\ClassMetadata;

/**
 * This class prepares an entity instance ready to be persisted to the
 * database. It consults handlers first before falling back to native doctrine
 * types.
 */
class Persister
{
    protected $metadata;
    
    protected $disabledFields = [];

    public function __construct(ClassMetadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @param QuerySet      $queries
     * @param mixed         $entity
     * @param EntityManager $em
     *
     * @return mixed Entity
     */
    public function persist(QuerySet $queries, $entity, EntityManager $em)
    {
        foreach ($this->getFields() as $key => $mapping) {
            // First step is to allow each Bolt field to transform the data.
            /** @var FieldTypeInterface $field */
            $field = new $mapping['fieldtype']($mapping);
            $field->persist($queries, $entity, $em);
        }

        return $entity;
    }
    
    /**
     * Marks a field to be excluded from persistence
     * 
     * @param string $field
     *
     * @return void
     */
    public function disableField($field)
    {
        if (!in_array($field, $this->disabledFields)) {
            $this->disabledFields[] = $field; 
        }
    }
    
    /**
     * Marks a previously excluded field to be included on persistence
     * 
     * @param string $field
     *
     * @return void
     */
    public function enableField($field)
    {
        if (in_array($field, $this->disabledFields)) {
            unset($this->disabledFields[$field]); 
        }
    }
    
    /**
     * Fetch the fields that will be persisted
     *
     * @return void
     * @author 
     **/
    protected function getFields()
    {
        $mappings = $this->metadata->getFieldMappings();
        
        foreach ($this->disabledFields as $field) {
            if (in_array($field, $mappings)) {
                unset($mappings[$field]);
            }
        }
        
        return $mappings;
    }
}
