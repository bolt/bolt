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
     * Get a content changelog entry by ID.
     *
     * @param mixed $contenttype Should be a string content type slug, or an
     *                           associative array containing a key named
     *                           'slug'
     * @param integer $contentid The record ID
     * @param integer $id        The content-changelog ID
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
     * @param integer $contentid The record ID
     * @param integer $id        The content-changelog ID
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
     * @param integer $contentid The record ID
     * @param integer $id        The content-changelog ID
     *
     * @return \Bolt\Logger\ChangeLogItem|null
     */
    public function getPrevChangelogEntry($contenttype, $contentid, $id)
    {
        return $this->getOrderedChangelogEntry($contenttype, $contentid, $id, '<');
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
        if (!in_array($cmpOp, ['=', '<', '>'])) {
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
            ->setParameters([
                ':logid'       => $id,
                ':contentid'   => $contentid,
                ':contenttype' => $contenttype
            ])
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
