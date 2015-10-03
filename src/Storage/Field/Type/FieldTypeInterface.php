<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Query\QueryBuilder;

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
     * @param QueryBuilder  $query
     * @param ClassMetadata $metadata
     *
     * @return QueryBuilder|null
     */
    public function load(QueryBuilder $query, ClassMetadata $metadata);

    /**
     * Handle or ignore the query event.
     *
     * @param QueryInterface $query
     * @param ClassMetadata  $metadata
     *
     * @return QueryBuilder|null
     */
    public function query(QueryInterface $query, ClassMetadata $metadata);

    /**
     * Handle or ignore the persist event.
     *
     * @param QuerySet $queries
     * @param mixed    $entity
     */
    public function persist(QuerySet $queries, $entity);

    /**
     * Handle or ignore the hydrate event.
     *
     * @param $data
     * @param $entity
     */
    public function hydrate($data, $entity);

    /**
     * Handle transforms on a field set.
     *
     * @param $entity
     * @param $value
     */
    public function set($entity, $value);

    /**
     * Handle or ignore the present event.
     *
     * @param $entity
     */
    public function present($entity);

    /**
     * Returns the name of the type.
     *
     * @return string The field name
     */
    public function getName();
}
