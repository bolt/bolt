<?php
namespace Bolt\Storage;

use Bolt\Entity\Content;

/**
 * A Repository class that handles dynamically created content tables.
 */
class ContentRepository extends Repository
{
    public $em;
    public $_class;
    public $entityName;
    public $namingStrategy;
    
    
    /**
     * Creates a new QueryBuilder instance that is prepopulated for this entity name.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null, $indexBy = null)
    {
        if (null === $alias) {
            $alias = $this->getAlias();
        }
        return $this->em->createQueryBuilder()
            ->select($alias.".*")
            ->from($this->getTableName(), $alias);
    }
    
    /**
     * Creates a new Content entity and passes the supplied data to the constructor.
     *
     * @param string $alias
     * @param string $indexBy The index for the from.
     *
     * @return QueryBuilder
     */
    public function create($params = null)
    {
        return new Content($params);
    }
}
