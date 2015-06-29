<?php
namespace Bolt\Storage\Repository;

use Bolt\Logger\ChangeLogItem;
use Bolt\Storage\Repository;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A Repository class that handles storage operations for the change log table.
 */
class LogChange extends BaseLog
{
    /**
     * Get content changelog entries for all content types.
     *
     * @param array $options An array with additional options. Currently, the
     *                       following options are supported:
     *                       - 'limit' (int)
     *                       - 'offset' (int)
     *                       - 'order' (string)
     *                       - 'direction' (string)
     *
     * @return \Bolt\Logger\ChangeLogItem[]
     */
    public function getChangeLog(array $options)
    {
        $query = $this->getChangeLogQuery($options);


        $rows = $this->findAll($query);

        $objs = [];
        foreach ($rows as $row) {
            $objs[] = new ChangeLogItem($this->app, $row);
        }

        return $objs;
    }

    /**
     * Build the query to get content changelog entries for all content types.
     *
     * @param array $options
     *
     * @return QueryBuilder
     */
    public function getChangeLogQuery(array $options)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*');

        $qb = $this->setLimitOrder($qb, $options);

        return $qb;
    }

    /**
     * Conditionally add LIMIT and ORDER BY to a QueryBuilder query.
     *
     * @param QueryBuilder $query
     * @param array        $options The following options are supported:
     *                              - 'limit' (int)
     *                              - 'offset' (int)
     *                              - 'order' (string)
     *                              - 'direction' (string)
     */
    protected function setLimitOrder(QueryBuilder $query, array $options)
    {
        if (isset($options['order'])) {
            $query->orderBy($options['order'], $options['direction']);
        }
        if (isset($options['limit'])) {
            $query->setMaxResults(intval($options['limit']));

            if (isset($options['offset'])) {
                $query->setFirstResult(intval($options['offset']));
            }
        }
    }
}
