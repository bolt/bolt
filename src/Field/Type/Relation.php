<?php
namespace Bolt\Field\Type;

use Doctrine\DBAL\Query\QueryBuilder;
use Bolt\Storage\EntityManager;
use Bolt\Mapping\ClassMetadata;


/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Relation extends FieldTypeBase
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
        $boltname = $metadata->getBoltName();
        $query->addSelect('rel.*');
        $query->leftJoin('content', 'bolt_relations', 'rel', "content.id = rel.from_id AND rel.from_contenttype='$boltname'");
    }
    
    /**
     * Handle the hydrate event.
     *
     */
    public function hydrate($data, $entity)
    {
        $field = $this->mapping['fieldname'];
        $entity->$field = array('pretend relation');
    }
    /**
     * Returns the name of the field type.
     *
     * @return string The field name
     */
    public function getName()
    {
        return 'relation';
    }

    
}
