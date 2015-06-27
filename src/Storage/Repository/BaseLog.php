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
     * Trim logs older that provided date.
     *
     * @param \DateTime $period
     *
     * @return boolean
     */
    public function trimLog($period)
    {
        $query = $this->queryWriteLog($period);

        return $query->execute();
    }

    /**
     * Build the query for a log trim.
     *
     * @param \DateTime $period
     *
     * @return QueryBuilder
     */
    public function queryTrimLog($period)
    {
        $qb = $this->createQueryBuilder();
        $qb->delete($this->getTableName())
             ->where('date < :date')
             ->setParameter(':date', $period, \Doctrine\DBAL\Types\Type::DATETIME);

        return $qb;
    }

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
