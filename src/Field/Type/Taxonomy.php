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
        $field = $this->mapping['fieldname'];
        $boltname = $metadata->getBoltName();
        
        if ($this->mapping['data']['has_sortorder']) {
            $order = "$field.sortorder";
        } else {
            $order = "$field.id";
        }
        
        $query->addSelect($this->getPlatformGroupConcat("$field.slug", $order ,$field, $query))
            ->leftJoin('content', 'bolt_taxonomy', $field, "content.id = $field.content_id AND $field.contenttype='$boltname' AND $field.taxonomytype='$field'")
            ->addGroupBy("content.id");    
    }
    
    /**
     * Handle the hydrate event.
     *
     */
    public function hydrate($data, $entity, EntityManager $em = null)
    {
        $field = $this->mapping['fieldname'];
        $taxonomies = array_filter(explode(',', $data[$field]));
        $entity->$field = $taxonomies;
        
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
    
    
    /**
     * Get platform specific group_concat token for provided column
     *
     * @param string $column
     * 
     * @return string
     **/
    protected function getPlatformGroupConcat($column, $order, $alias, QueryBuilder $query)
    {
        $platform = $query->getConnection()->getDatabasePlatform()->getName();
        
        switch ($platform) {
            case 'mysql':
                return "GROUP_CONCAT(DISTINCT $column ORDER BY $order ASC) as $alias";
            case 'sqlite':
                return "GROUP_CONCAT(DISTINCT $column) as $alias";
            case 'postgresql':
                return "string_agg(distinct $column, ',' ORDER BY $order) as $alias";
        }
        
        
    }

    
}
