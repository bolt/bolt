<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;

/**
 * A Repository class that handles storage operations for the Cron table.
 */
class CronRepository extends Repository
{
    /**
     * Fetches the next run time for a named interval eg: cron.Hourly | cron.Daily
     *
     * @param $interimName
     *
     * @return Bolt\Entity\Cron
     **/
    public function getNextRunTimes($interimName)
    {
        $query = $this->queryNextRunTimes($interimName);
        return $this->findOneWith($query);
    }
    
    public function queryNextRunTimes($interimName)
    {
        $oldname = strtolower(str_replace('cron.', '', $interimName));
        $qb = $this->createQueryBuilder();
        $qb->select('id, lastrun, interim')
            ->where('(interim = :interim OR interim = :oldname)')
            ->orderBy('lastrun', 'DESC')
            ->setParameter('interim', $interimName)
            ->setParameter('oldname', $oldname);
        return $qb;
    }
    
    public function createQueryBuilder($alias = null)
    {
        return $this->em->createQueryBuilder()
            ->from($this->getTableName());
    }
}
