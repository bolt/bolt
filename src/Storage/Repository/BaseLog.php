<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A Repository class that handles storage operations for the log tables.
 */
abstract class BaseLog extends Repository
{
    /**
     * Creates a query builder instance namespaced to this repository
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null)
    {
        return $this->em->createQueryBuilder()
            ->from($this->getTableName());
    }
}
