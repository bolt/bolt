<?php
namespace Bolt\Field\Type;

use Doctrine\DBAL\Query\QueryBuilder;
use Bolt\Mapping\ClassMetadata;
use Bolt\Storage\EntityManager;


/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Taxonomy extends FieldTypeBase
{
    
     /**
     * Handle the load event.
     * 
     * @param QueryBuilder $query
     *
     * @return void
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        print_r($this->mapping); exit;
        $field = $this->mapping['fieldname'];
        $boltname = $metadata->getBoltName();
        $query->addSelect($this->getPlatformGroupConcat("$field.to_id", $field, $query))
            ->leftJoin('content', 'bolt_relations', $field, "content.id = $field.from_id AND $field.from_contenttype='$boltname' AND $field.to_contenttype='$field'")
            ->addGroupBy("content.id");    
    }
    
    /**
     * Handle the hydrate event.
     *
     */
    public function hydrate($data, $entity, EntityManager $em = null)
    {
        $field = $this->mapping['fieldname'];
        
    }
    
    /**
     * Returns the name of the field type.
     *
     * @return string The field name
     */
    public function getName()
    {
        return 'taxonomy';
    }

    
}
