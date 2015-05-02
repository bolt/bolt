<?php

namespace Bolt\Logger;

use Doctrine\DBAL\Query\QueryBuilder;
use Silex\Application;

/**
 * Bolt change log interface class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ChangeLog
{
    /** @var Application */
    private $app;

    /** @var string */
    private $table_change;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $prefix = $app['config']->get('general/database/prefix');
        $this->table_change = sprintf("%s%s", $prefix, 'log_change');
    }

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
     * @return array
     */
    public function getChangelog(array $options)
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $query */
        $query = $this->app['db']->createQueryBuilder()
                        ->select('*')
                        ->from($this->table_change);

        $query = $this->setLimitOrder($query, $options);
        $rows = $query->execute()->fetchAll();

        $objs = array();
        foreach ($rows as $row) {
            $objs[] = new ChangeLogItem($this->app, $row);
        }

        return $objs;
    }

    /**
     * Get a count of change log entries.
     *
     * @return integer
     */
    public function countChangelog()
    {
        /** @var \Doctrine\DBAL\Query\QueryBuilder $query */
        $query = $this->app['db']->createQueryBuilder()
                        ->select('COUNT(id) as count')
                        ->from($this->table_change);

        return $query->execute()->fetchColumn();
    }

    /**
     * Get content changelog entries by content type.
     *
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param array $options     An array with additional options. Currently, the
     *                           following options are supported:
     *                           - 'limit' (int)
     *                           - 'order' (string)
     *                           - 'direction' (string)
     *                           - 'contentid' (int), to filter further by content ID
     *                           - 'id' (int), to filter by a specific changelog entry ID
     *
     * @return array
     */
    public function getChangelogByContentType($contenttype, array $options)
    {
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        // Build base query
        $contentTablename = $this->app['storage']->getContenttypeTablename($contenttype);
        $query = $this->app['db']->createQueryBuilder()
                        ->select('log.*, log.title')
                        ->from($this->table_change, 'log')
                        ->leftJoin('log', $contentTablename, 'content', 'content.id = log.contentid');

        // Set required WHERE
        $query = $this->setWhere($query, $contenttype, $options);

        // Set ORDERBY and LIMIT as requested
        $query = $this->setLimitOrder($query, $options);

        $rows = $query->execute()->fetchAll();

        $objs = array();

        foreach ($rows as $row) {
            $objs[] = new ChangeLogItem($this->app, $row);
        }

        return $objs;
    }

    /**
     * Get a count of change log entries by contenttype.
     *
     * @param mixed $contenttype
     * @param array $options
     *
     * @return integer
     */
    public function countChangelogByContentType($contenttype, array $options)
    {
        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        // Build base query
        $query = $this->app['db']->createQueryBuilder()
                        ->select('COUNT(id) as count')
                        ->from($this->table_change, 'log');

        // Set any required WHERE
        $query = $this->setWhere($query, $contenttype, $options);

        return $query->execute()->fetchColumn();
    }

    /**
     * Get a content changelog entry by ID.
     *
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param  $contentid
     * @param int   $id          The content-changelog ID
     *
     * @return \Bolt\Logger\ChangeLogItem|null
     */
    public function getChangelogEntry($contenttype, $contentid, $id)
    {
        return $this->getOrderedChangelogEntry($contenttype, $contentid, $id, '=');
    }

    /**
     * Get the content changelog entry that follows the given ID.
     *
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param  $contentid
     * @param int   $id          The content-changelog ID
     *
     * @return \Bolt\Logger\ChangeLogItem|null
     */
    public function getNextChangelogEntry($contenttype, $contentid, $id)
    {
        return $this->getOrderedChangelogEntry($contenttype, $contentid, $id, '>');
    }

    /**
     * Get the content changelog entry that precedes the given ID.
     *
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param $contentid
     * @param int   $id          The content-changelog ID
     *
     * @return \Bolt\Logger\ChangeLogItem|null
     */
    public function getPrevChangelogEntry($contenttype, $contentid, $id)
    {
        return $this->getOrderedChangelogEntry($contenttype, $contentid, $id, '<');
    }

    /**
     * Set any required WHERE clause on a QueryBuilder.
     *
     * @param QueryBuilder $query
     * @param string       $contenttype
     * @param array        $options
     *
     * @return QueryBuilder
     */
    private function setWhere(QueryBuilder $query, $contenttype, array $options)
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
            ->setParameters(array(
                ':contenttype' => $contenttype,
                ':contentid'   => isset($options['contentid']) ? $options['contentid'] : null,
                ':logid'       => isset($options['id']) ? $options['id'] : null
            ));

        return $query;
    }

    /**
     * Conditionally add LIMIT and ORDERBY to a QueryBuilder query.
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
    private function setLimitOrder(QueryBuilder $query, array $options)
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

    /**
     * Get one changelog entry from the database.
     *
     * @param mixed   $contenttype Should be a string content type slug, or an
     *                             associative array containing a key named
     *                             'slug'
     * @param integer $contentid
     * @param integer $id          The content-changelog ID
     * @param string  $cmpOp       One of '=', '<', '>'; this parameter is used
     *                             to select either the ID itself, or the subsequent
     *                             or preceding entry.
     *
     * @throws \Exception
     *
     * @return \Bolt\Logger\ChangeLogItem|null
     */
    private function getOrderedChangelogEntry($contenttype, $contentid, $id, $cmpOp)
    {
        if (!in_array($cmpOp, array('=', '<', '>'))) {
            throw new \InvalidArgumentException(sprintf('Invalid comparison operator: %s', $cmpOp));
        }

        if (is_array($contenttype)) {
            $contenttype = $contenttype['slug'];
        }

        // Build base query
        $contentTablename = $this->app['storage']->getTablename($contenttype);
        /** @var \Doctrine\DBAL\Query\QueryBuilder $query */
        $query = $this->app['db']->createQueryBuilder()
            ->select('log.*')
            ->from($this->table_change, 'log')
            ->leftJoin('log', $contentTablename, 'content', 'content.id = log.contentid')
            ->where("log.id $cmpOp :logid")
            ->andWhere('log.contentid = :contentid')
            ->andWhere('contenttype = :contenttype')
            ->setParameters(array(
                ':logid'       => $id,
                ':contentid'   => $contentid,
                ':contenttype' => $contenttype))
            ->setMaxResults(1);

        // Set ORDER BY
        if ($cmpOp == '<') {
            $query->orderBy('date', 'DESC');
        } elseif ($cmpOp == '>') {
            $query->orderBy('date');
        }

        $row = $query->execute()->fetch(\PDO::FETCH_ASSOC);

        if (is_array($row)) {
            return new ChangeLogItem($this->app, $row);
        } else {
            return null;
        }
    }
}
