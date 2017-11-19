<?php

namespace Bolt\Storage\Repository;

use Bolt\Common\Deprecated;
use Bolt\Storage\Entity;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A Repository class that handles storage operations for the change log table.
 */
class LogChangeRepository extends BaseLogRepository
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
     * @return \Bolt\Storage\Entity\LogChange[]
     */
    public function getChangeLog(array $options)
    {
        $query = $this->getChangeLogQuery($options);

        return $this->findWith($query);
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

        $this->setLimitOrder($qb, $options);

        return $qb;
    }

    /**
     * Get a count of change log entries.
     *
     * @deprecated since 3.3, will be removed in 4.0
     *
     * @return int
     */
    public function countChangeLog()
    {
        Deprecated::method(3.3, 'Use count() instead.');

        $query = $this->countChangeLogQuery();

        return $this->getCount($query->execute()->fetch());
    }

    /**
     * Build the query to get a count of change log entries.
     *
     * @deprecated since 3.3, will be removed in 4.0
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
     * @param string $contentType ContentType key name
     * @param array  $options     Additional options:
     *                            - 'limit' (integer):     Maximum number of results to return
     *                            - 'order' (string):      Field to order by
     *                            - 'direction' (string):  ASC or DESC
     *                            - 'contentid' (integer): Filter further by content ID
     *                            - 'id' (integer):        Filter by a specific change log entry ID
     *
     * @return \Bolt\Storage\Entity\LogChange[]
     */
    public function getChangeLogByContentType($contentType, array $options = [])
    {
        $query = $this->getChangeLogByContentTypeQuery($contentType, $options);

        return $this->findWith($query);
    }

    /**
     * Build query to get content changelog entries by ContentType.
     *
     * @param string $contentType
     * @param array  $options
     *
     * @return QueryBuilder
     */
    public function getChangeLogByContentTypeQuery($contentType, array $options)
    {
        $alias = $this->getAlias();
        $contentTableName = $this->em->getRepository($contentType)->getTableName();

        $qb = $this->createQueryBuilder();
        $qb->select("$alias.*, $alias.title")
            ->leftJoin($alias, $contentTableName, 'content', "content.id = $alias.contentid");

        // Set required WHERE
        $this->setWhere($qb, $contentType, $options);

        // Set ORDER BY and LIMIT as requested
        $this->setLimitOrder($qb, $options);

        return $qb;
    }

    /**
     * Get a count of change log entries by contenttype.
     *
     * @param string $contentType
     * @param array  $options
     *
     * @return int|false
     */
    public function countChangeLogByContentType($contentType, array $options)
    {
        $query = $this->countChangeLogByContentTypeQuery($contentType, $options);

        return $this->getCount($query->execute()->fetch());
    }

    /**
     * Get a count of change log entries by contenttype.
     *
     * @param string $contentType
     * @param array  $options
     *
     * @return QueryBuilder
     */
    public function countChangeLogByContentTypeQuery($contentType, array $options)
    {
        // Build base query
        $qb = $this->createQueryBuilder();
        $qb->select('COUNT(id) as count');

        // Set any required WHERE
        $this->setWhere($qb, $contentType, $options);

        return $qb;
    }

    /**
     * Get one changelog entry from the database.
     *
     * @param mixed  $contentType ContentType slug
     * @param int    $contentId   Content record ID
     * @param int    $id          The content change log ID
     * @param string $cmpOp       one of '=', '<', '>'; this parameter is used
     *                            to select either the ID itself, or the subsequent
     *                            or preceding entry
     *
     * @throws \InvalidArgumentException
     *
     * @return \Bolt\Storage\Entity\LogChange|false
     */
    public function getChangeLogEntry($contentType, $contentId, $id, $cmpOp)
    {
        if (!in_array($cmpOp, ['=', '<', '>'])) {
            throw new \InvalidArgumentException(sprintf('Invalid comparison operator: %s', $cmpOp));
        }

        $query = $this->getChangeLogEntryQuery($contentType, $contentId, $id, $cmpOp);

        /** @var Entity\LogChange $logChange */
        $logChange = $this->findOneWith($query);

        return $logChange;
    }

    /**
     * Build query to get one changelog entry from the database.
     *
     * @param string $contentType
     * @param int    $contentId
     * @param int    $id
     * @param string $cmpOp
     *
     * @return QueryBuilder
     */
    public function getChangeLogEntryQuery($contentType, $contentId, $id, $cmpOp)
    {
        $alias = $this->getAlias();
        $contentTypeTableName = $this->em->getRepository($contentType)->getTableName();

        // Build base query
        $qb = $this->createQueryBuilder();
        $qb->select("$alias.*")
            ->leftJoin($alias, $contentTypeTableName, 'content', "content.id = $alias.contentid")
            ->where("$alias.id $cmpOp :logid")
            ->andWhere("$alias.contentid = :contentid")
            ->andWhere('contenttype = :contenttype')
            ->setParameters([
                ':logid'       => $id,
                ':contentid'   => $contentId,
                ':contenttype' => $contentType,
            ]);

        // Set ORDER BY
        if ($cmpOp === '<') {
            $qb->orderBy('date', 'DESC');
        } elseif ($cmpOp === '>') {
            $qb->orderBy('date');
        }

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
            $query->orderBy($options['order'], isset($options['direction']) ? $options['direction'] : null);
        }
        if (isset($options['limit'])) {
            $query->setMaxResults((int) ($options['limit']));

            if (isset($options['offset'])) {
                $query->setFirstResult((int) ($options['offset']));
            }
        }
    }

    /**
     * Set any required WHERE clause on a QueryBuilder.
     *
     * @param QueryBuilder $query
     * @param string       $contentType
     * @param array        $options
     */
    protected function setWhere(QueryBuilder $query, $contentType, array $options)
    {
        $where = $query->expr()->andX()
                        ->add($query->expr()->eq('contenttype', ':contenttype'));

        // Set any required WHERE
        if (isset($options['contentid']) || isset($options['id'])) {
            if (isset($options['contentid'])) {
                $where->add($query->expr()->eq('contentid', ':contentid'));
            }

            if (isset($options['id'])) {
                $tableName = $this->getTableName();
                $where->add($query->expr()->eq("$tableName.id", ':logid'));
            }
        }

        $query->where($where)
            ->setParameters([
                ':contenttype' => $contentType,
                ':contentid'   => isset($options['contentid']) ? $options['contentid'] : null,
                ':logid'       => isset($options['id']) ? $options['id'] : null,
            ]);
    }
}
