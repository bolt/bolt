<?php

namespace Bolt\Storage\Repository;

/**
 * A Repository class that handles storage operations for the system log table.
 */
class LogSystemRepository extends BaseLogRepository
{
    /**
     * Get a count of system log entries.
     *
     * @return integer
     */
    public function countSystemLog()
    {
        $query = $this->countSystemLogQuery();

        return $this->getCount($query->execute()->fetch());
    }

    /**
     * Build the query to get a count of system log entries.
     *
     * @return QueryBuilder
     */
    public function countSystemLogQuery()
    {
        $qb = $this->createQueryBuilder();
        $qb->select('COUNT(id) as count');

        return $qb;
    }
}
