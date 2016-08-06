<?php
namespace Bolt\Storage\Repository;

use Bolt\Helpers\Arr;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A Repository class that handles storage operations for the log tables.
 */
abstract class BaseLogRepository extends Repository
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
        $query = $this->queryTrimLog($period);

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
             ->setParameter('date', $period, \Doctrine\DBAL\Types\Type::DATETIME);

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
            ->getTruncateTableSQL($this->getTableName());

        return $qb->getConnection()->executeQuery($query)->execute();
    }
    /**
     * Get content log's activity entries.
     *
     * @param integer $page
     * @param integer $amount
     * @param array   $options
     *
     * @return Entity\LogChange[]
     */
    public function getActivity($page = 1, $amount = 10, array $options = [])
    {
        $query = $this->getActivityQuery($page, $amount, $options);

        return $this->findWith($query);
    }

    /**
     * Build the query to get the log entries.
     *
     * @param integer $page
     * @param integer $amount
     * @param array   $options
     *
     * @return QueryBuilder
     */
    public function getActivityQuery($page, $amount, array $options)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->orderBy('id', 'DESC')
            ->setMaxResults(intval($amount))
            ->setFirstResult(intval(($page - 1) * $amount));

        $this->addWhereActivity($qb, $options);

        return $qb;
    }

    /**
     * Get the total amount of log entries, optionally limited to a given level
     * and/or context.
     *
     * @param array $options
     *
     * @return integer|false
     */
    public function getActivityCount(array $options = [])
    {
        $query = $this->getActivityCountQuery($options);

        return $this->getCount($query->execute()->fetch());
    }

    /**
     * Build the query for the entry count.
     *
     * @param array $options
     *
     * @return QueryBuilder
     */
    public function getActivityCountQuery(array $options)
    {
        // Find out how many entries we're paging form
        $qb = $this->createQueryBuilder();
        $qb->select('COUNT(id) as count');

        $this->addWhereActivity($qb, $options);

        return $qb;
    }

    /**
     * Add required WHERE parameters.
     *
     * @param QueryBuilder $qb
     * @param array        $options
     */
    protected function addWhereActivity(QueryBuilder $qb, $options)
    {
        if (empty($options)) {
            return;
        }

        foreach ($options as $columnName => $option) {
            if (is_array($options[$columnName])) {
                $qb->andWhere($this->buildWhereOr($qb, $columnName, $option));
            } elseif (!empty($options[$columnName])) {
                $qb->andWhere("$columnName = :$columnName")
                    ->setParameter($columnName, $option);
            }
        }
    }

    /**
     * Build an OR group that is added to the AND.
     *
     * @param QueryBuilder $qb
     * @param string       $parentColumnName
     * @param array        $options
     *
     * @return CompositeExpression
     */
    protected function buildWhereOr(QueryBuilder $qb, $parentColumnName, array $options)
    {
        $orX = $qb->expr()->orX();
        foreach ($options as $columnName => $option) {
            if (empty($options[$columnName])) {
                continue;
            } elseif (Arr::isIndexedArray($options)) {
                $key = $parentColumnName . '_' . $columnName;
                $orX->add("$parentColumnName = :$key");
                $qb->setParameter($key, $option);
            } else {
                $orX->add("$columnName = :$columnName");
                $qb->setParameter($columnName, $option);
            }
        }

        return $orX;
    }

    /**
     * Get a column count query result.
     *
     * @param array|false $result
     *
     * @return integer|false
     */
    protected function getCount($result)
    {
        if ($result !== false && isset($result['count'])) {
            return $result['count'];
        }

        return false;
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
