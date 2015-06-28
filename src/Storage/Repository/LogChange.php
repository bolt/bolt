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
     * Conditionally add LIMIT and ORDER BY to a QueryBuilder query.
     *
     * @param QueryBuilder $query
     * @param array        $options The following options are supported:
     *                              - 'limit' (int)
     *                              - 'offset' (int)
     *                              - 'order' (string)
     *                              - 'direction' (string)
     *
     * @return QueryBuilder
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

        return $query;
    }
}
