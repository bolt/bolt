<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Entity;
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
     * Clear the log table.
     *
     * @return boolean
     */
    public function clearLog()
    {
        $qb = $this->createQueryBuilder();
        $query = $qb->getConnection()
            ->getDatabasePlatform()
            ->getTruncateTableSql($this->getTableName());

        return $qb->getConnection()->executeQuery($query)->execute();
    }

    /**
     * Get a specific activity log's entries.
     *
     * @param integer      $page
     * @param integer      $amount
     * @param integer      $level
     * @param string|array $context
     *
     * @return Entity\LogChange[]|Entity\LogSystem[]
     */
    public function getActivity($page = 1, $amount = 10, $level = null, $context = null)
    {
        $query = $this->getActivityQuery($page, $amount, $level, $context);

        return $this->findAll($query);
    }

    /**
     * Build the query to get the log entries.
     *
     * @param integer      $page
     * @param integer      $amount
     * @param integer      $level
     * @param string|array $context
     *
     * @return QueryBuilder
     */
    public function getActivityQuery($page, $amount, $level, $context)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->orderBy('id', 'DESC')
            ->setMaxResults(intval($amount))
            ->setFirstResult(intval(($page - 1) * $amount));

        $this->addWhereActivityLevel($qb, $level);
        $this->addWhereActivityContext($qb, $context);

        return $qb;
    }

    /**
     * Get the total amount of log entries, optionally limited to a given level
     * and/or context.
     *
     * @param integer      $level
     * @param string|array $context
     *
     * @return integer|false
     */
    public function getActivityCount($level = null, $context = null)
    {
        $query = $this->getActivityCountQuery($level, $context);

        $count = $query->execute()->fetch();
        if ($count !== false && isset($count['count'])) {
            return $count['count'];
        }

        return false;
    }

    /**
     * Build the query for the entry count.
     *
     * @param integer      $level
     * @param string|array $context
     *
     * @return QueryBuilder
     */
    public function getActivityCountQuery($level, $context)
    {
        // Find out how many entries we're paging form
        $qb = $this->createQueryBuilder();
        $qb->select('COUNT(id) as count');

        $this->addWhereActivityLevel($qb, $level);
        $this->addWhereActivityContext($qb, $context);

        return $qb;
    }

    /**
     * Add required level to the WHERE parameters.
     *
     * @param QueryBuilder $qb
     * @param integer      $level
     */
    protected function addWhereActivityLevel(QueryBuilder $qb, $level)
    {
        if ($level !== null) {
            $qb->andWhere('level = :level')
                ->setParameter('level', $level);
        }
    }

    /**
     * Add required context(s) to the WHERE parameters.
     *
     * NOTE: Multiple contexts are ORed against each other.
     *
     * @param QueryBuilder $qb
     * @param string|array $context
     */
    protected function addWhereActivityContext(QueryBuilder $qb, $context)
    {
        if (is_string($context)) {
            $qb->andWhere('context = :context')
                ->setParameter('context', $context);
        } elseif (is_array($context)) {
            $orX = $qb->expr()->orX();
            foreach ($context as $k => $v) {
                $orX->add("context = :$k");
                $qb->setParameter($k, $v);
            }
            $qb->andWhere($orX);
        }
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
