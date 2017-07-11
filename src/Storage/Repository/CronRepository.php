<?php

namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A Repository class that handles storage operations for the Cron table.
 */
class CronRepository extends Repository
{
    /**
     * Fetches the next run time for a named interval eg: cron.Hourly, or cron.Daily.
     *
     * @param $interimName
     *
     * @return \Bolt\Storage\Entity\Cron|false
     */
    public function getNextRunTime($interimName)
    {
        $query = $this->queryNextRunTime($interimName);

        return $this->findOneWith($query);
    }

    /**
     * Build the query for a run time.
     *
     * @param string $interimName
     *
     * @return QueryBuilder
     */
    public function queryNextRunTime($interimName)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('id, lastrun, interim')
            ->where('interim = :interim')
            ->orderBy('lastrun', 'DESC')
            ->setParameter('interim', $interimName)
        ;

        return $qb;
    }
}
