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
}
