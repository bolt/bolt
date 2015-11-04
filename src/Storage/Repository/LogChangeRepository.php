<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;
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
     * @return \Bolt\Storage\Entity\LogChange[]
     */
    public function getChangeLogByContentType($contenttype, array $options = [])
    {
        $query = $this->getChangeLogByContentTypeQuery($contenttype, $options);

        return $this->findWith($query);
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
        $tableName = $this->getTableName();
        $contentTableName = $this->em->getRepository($contenttype)->getTableName();

        $qb = $this->createQueryBuilder();
        $qb->select("$tableName.*, $tableName.title")
            ->leftJoin($tableName, $contentTableName, 'content', "content.id = $tableName.contentid");

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
     * Get one changelog entry from the database.
     *
     * @param mixed   $contenttype ContentType slug
     * @param integer $contentid   Content record ID
     * @param integer $id          The content change log ID
     * @param string  $cmpOp       One of '=', '<', '>'; this parameter is used
     *                             to select either the ID itself, or the subsequent
     *                             or preceding entry.
     *
     * @throws \InvalidArgumentException
     *
     * @return \Bolt\Storage\Entity\LogChange
     */
    public function getChangeLogEntry($contenttype, $contentid, $id, $cmpOp)
    {
        if (!in_array($cmpOp, ['=', '<', '>'])) {
            throw new \InvalidArgumentException(sprintf('Invalid comparison operator: %s', $cmpOp));
        }

        $query = $this->getChangeLogEntryQuery($contenttype, $contentid, $id, $cmpOp);

        return $this->findOneWith($query);
    }

    /**
     * Build query to get one changelog entry from the database.
     *
     * @param string  $contenttype
     * @param integer $contentid
     * @param integer $id
     * @param string  $cmpOp
     *
     * @return QueryBuilder
     */
    public function getChangeLogEntryQuery($contenttype, $contentid, $id, $cmpOp)
    {
        $tableName = $this->getTableName();
        $contentTypeTableName = $this->em->getRepository($contenttype)->getTableName();

        // Build base query
        $qb = $this->createQueryBuilder();
        $qb->select("$tableName.*")
            ->leftJoin($tableName, $contentTypeTableName, 'content', "content.id = $tableName.contentid")
            ->where("$tableName.id $cmpOp :logid")
            ->andWhere("$tableName.contentid = :contentid")
            ->andWhere('contenttype = :contenttype')
            ->setParameters([
                ':logid'       => $id,
                ':contentid'   => $contentid,
                ':contenttype' => $contenttype,
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
                $tableName = $this->getTableName();
                $where->add($query->expr()->eq("$tableName.id", ':logid'));
            }
        }

        $query->where($where)
            ->setParameters([
                ':contenttype' => $contenttype,
                ':contentid'   => isset($options['contentid']) ? $options['contentid'] : null,
                ':logid'       => isset($options['id']) ? $options['id'] : null,
            ]);
    }
}
