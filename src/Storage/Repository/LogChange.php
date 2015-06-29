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
     * Get a count of change log entries.
     *
     * @return integer
     */
    public function countChangeLog()
    {
        $query = $this->countChangeLogQuery();

        return $this->getCount($query->execute()->fetch());
    }

    /**
     * Build the query to get a count of change log entries.
     *
     * @return QueryBuilder
     */
    public function countChangeLogQuery()
    {
        $qb = $this->createQueryBuilder();
        $qb->select('COUNT(id) as count');

        return $qb;
    }

    /**
     * Get content changelog entries by content type.
     *
     * @param string $contenttype Content type slug
     * @param array  $options     Additional options:
     *                            - 'limit' (integer):     Maximum number of results to return
     *                            - 'order' (string):      Field to order by
     *                            - 'direction' (string):  ASC or DESC
     *                            - 'contentid' (integer): Filter further by content ID
     *                            - 'id' (integer):        Filter by a specific change log entry ID
     *
     * @return \Bolt\Logger\ChangeLogItem[]
     */
    public function getChangeLogByContentType($contenttype, array $options = [])
    {
        $query = $this->getChangeLogByContentTypeQuery($contenttype, $options);
        $rows = $this->findAll($query);

        $objs = [];
        foreach ($rows as $row) {
            $objs[] = new ChangeLogItem($this->app, $row);
        }

        return $objs;
    }

    /**
     * Build query to get content changelog entries by ContentType.
     *
     * @param string $contenttype
     * @param array  $options
     *
     * @return QueryBuilder
     */
    public function getChangeLogByContentTypeQuery($contenttype, array $options)
    {
        $contentTypeRepo = $this->em->getRepository($contenttype);

        $qb = $this->createQueryBuilder();
        $qb->select('log.*, log.title')
            ->from($this->getTableName(), 'log')
            ->leftJoin('log', $contentTypeRepo->getTableName(), 'content', 'content.id = log.contentid');

        // Set required WHERE
        $this->setWhere($qb, $contenttype, $options);

        // Set ORDERBY and LIMIT as requested
        $this->setLimitOrder($qb, $options);

        return $qb;
    }

    /**
     * Get a count of change log entries by contenttype.
     *
     * @param string $contenttype
     * @param array  $options
     *
     * @return integer|false
     */
    public function countChangeLogByContentType($contenttype, array $options)
    {
        $query = $this->countChangeLogByContentTypeQuery($contenttype, $options);

        return $this->getCount($query->execute()->fetch());
    }

    /**
     * Get a count of change log entries by contenttype.
     *
     * @param mixed $contenttype
     * @param array $options
     *
     * @return QueryBuilder
     */
    public function countChangeLogByContentTypeQuery($contenttype, array $options)
    {
        // Build base query
        $qb = $this->createQueryBuilder();
        $qb->select('COUNT(id) as count');

        // Set any required WHERE
        $this->setWhere($qb, $contenttype, $options);

        return $qb;
    }

    /**
     * Conditionally add LIMIT and ORDER BY to a QueryBuilder query.
     *
     * @param QueryBuilder $query
     * @param array        $options Additional options:
     *                              - 'limit' (integer):     Maximum number of results to return
     *                              - 'order' (string):      Field to order by
     *                              - 'direction' (string):  ASC or DESC
     *                              - 'contentid' (integer): Filter further by content ID
     *                              - 'id' (integer):        Filter by a specific change log entry ID
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

    /**
     * Set any required WHERE clause on a QueryBuilder.
     *
     * @param QueryBuilder $query
     * @param string       $contenttype
     * @param array        $options
     */
    protected function setWhere(QueryBuilder $query, $contenttype, array $options)
    {
        $where = $query->expr()->andX()
                        ->add($query->expr()->eq('contenttype', ':contenttype'));

        // Set any required WHERE
        if (isset($options['contentid']) || isset($options['id'])) {
            if (isset($options['contentid'])) {
                $where->add($query->expr()->eq('contentid', ':contentid'));
            }

            if (isset($options['id'])) {
                $where->add($query->expr()->eq('log.id', ':logid'));
            }
        }

        $query->where($where)
            ->setParameters([
                ':contenttype' => $contenttype,
                ':contentid'   => isset($options['contentid']) ? $options['contentid'] : null,
                ':logid'       => isset($options['id']) ? $options['id'] : null
            ]);
    }
}
