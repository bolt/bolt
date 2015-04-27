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
interface FieldTypeInterface
{
    
    /**
     * Handle or ignore the load event.
     * 
     * @param QueryBuilder $query
     *
     * @return void
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata);
    
    /**
     * Handle or ignore the persist event.
     *
     * @return void
     */
    public function persist(QueryBuilder $query, EntityManager $em);
    
    /**
     * Handle or ignore the hydrate event.
     *
     * @return void
     */
    public function hydrate($data, $entity);
    
    /**
     * Handle or ignore the present event.
     *
     * @return void
     */
    public function present($entity);
    
    /**
     * Returns the name of the type.
     *
     * @return string The field name
     */
    public function getName();

    
}
