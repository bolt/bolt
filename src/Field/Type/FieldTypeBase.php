<?php
namespace Bolt\Field\Type;

use Doctrine\DBAL\Query\QueryBuilder;
use Bolt\Storage\EntityManager;
use Bolt\Mapping\ClassMetadata;

/**
 * This is an abstract class for a field type that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
abstract class FieldTypeBase implements FieldTypeInterface
{
    
    public $mapping;
    
    public function __construct(array $mapping = array())
    {
        $this->mapping = $mapping;
    }
    
    /**
     * Handle or ignore the load event.
     * 
     * @param QueryBuilder $query
     *
     * @return void
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata)
    {
        return $query;
    }
    
    /**
     * Handle or ignore the persist event.
     *
     * @return void
     */
    public function persist(QueryBuilder $query, EntityManager $em)
    {
        
    }
    
    /**
     * Handle or ignore the hydrate event.
     *
     * @return void
     */
    public function hydrate($data, $entity, EntityManager $em = null)
    {
        
    }
    
    /**
     * Handle or ignore the present event.
     *
     * @return void
     */
    public function present($entity)
    {
        
    }
    
    /**
     * Returns the name of the hydrator.
     *
     * @return string The field name
     */
    public function getName()
    {
        return 'text';
    }
    
    
    /**
     * Get platform specific group_concat token for provided column
     *
     * @param string $column
     * 
     * @return string
     **/
    protected function getPlatformGroupConcat($column, $alias, QueryBuilder $query)
    {
        $platform = $query->getConnection()->getDatabasePlatform()->getName();
        
        switch ($platform) {
            case 'mysql':
                return "GROUP_CONCAT(DISTINCT $column) as $alias";
            case 'sqlite':
                return "GROUP_CONCAT(DISTINCT $column) as $alias";
            case 'postgresql':
                return "string_agg(distinct $column, ',') as $alias";
        }
        
        
    }


    
}
