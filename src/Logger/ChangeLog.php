<?php

namespace Bolt\Logger;

use Bolt\Application;
use Bolt\Helpers\String;
use Bolt\Pager;
use Monolog\Logger;

/**
 *
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ChangeLog
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var string
     */
    private $table_change;

    /**
     * @var string
     */
    private $table_system;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $prefix = $app['config']->get('general/database/prefix', "bolt_");
        $this->table_change = sprintf("%s%s", $prefix, 'log_change');
        $this->table_system = sprintf("%s%s", $prefix, 'log_system');
    }


//     private function makeOrderLimitSql($options)
//     {
//         $sql = '';
//         if (isset($options['order'])) {
//             $sql .= sprintf(" ORDER BY %s", $options['order']);
//         }
//         if (isset($options['limit'])) {
//             if (isset($options['offset'])) {
//                 $sql .= sprintf(" LIMIT %s, %s ", intval($options['offset']), intval($options['limit']));
//             } else {
//                 $sql .= sprintf(" LIMIT %d", intval($options['limit']));
//             }
//         }

//         return $sql;
//     }

    /**
     * Get content changelog entries for all content types
     * @param array $options An array with additional options. Currently, the
     *                       following options are supported:
     *                       - 'limit' (int)
     *                       - 'offset' (int)
     *                       - 'order' (string)
     * @return array
     */
    public function getChangelog($options)
    {
        $tablename = $this->getTablename('log_change');
        $sql = "SELECT log.*, log.title " .
               "    FROM $tablename as log ";
        $sql .= $this->makeOrderLimitSql($options);

        $rows = $this->app['db']->fetchAll($sql, array());
        $objs = array();
        foreach ($rows as $row) {
            $objs[] = new ChangeLogItem($this->app, $row);
        }

        return $objs;
    }

    /**
     *
     * @return integer
     */
    public function countChangelog()
    {
        $query = $this->app['db']->createQueryBuilder()
                      ->select('COUNT(id) as count')
                      ->from($this->table_change);

        return $query->execute()->fetchColumn();
    }

//     /**
//      * Get content changelog entries by content type.
//      * @param mixed $contenttype Should be a string content type slug, or an
//      *                           associative array containing a key named
//      *                           'slug'
//      * @param array $options An array with additional options. Currently, the
//      *                       following options are supported:
//      *                       - 'limit' (int)
//      *                       - 'order' (string)
//      *                       - 'contentid' (int), to filter further by content ID
//      *                       - 'id' (int), to filter by a specific changelog entry ID
//      * @return array
//      */
//     public function getChangelogByContentType($contenttype, $options)
//     {
//         if (is_array($contenttype)) {
//             $contenttype = $contenttype['slug'];
//         }
//         $tablename = $this->getTablename('log_change');
//         $contentTablename = $this->getTablename($contenttype);
//         $sql = "SELECT log.*, log.title " .
//                "    FROM $tablename as log " .
//                "    LEFT JOIN " . $contentTablename . " as content " .
//                "    ON content.id = log.contentid " .
//                "    WHERE contenttype = ? ";
//         $params = array($contenttype);
//         if (isset($options['contentid'])) {
//             $sql .= "    AND contentid = ? ";
//             $params[] = intval($options['contentid']);
//         }
//         if (isset($options['id'])) {
//             $sql .= " AND log.id = ? ";
//             $params[] = intval($options['id']);
//         }
//         $sql .= $this->makeOrderLimitSql($options);

//         $rows = $this->app['db']->fetchAll($sql, $params);
//         $objs = array();
//         foreach ($rows as $row) {
//             $objs[] = new ChangeLogItem($this->app, $row);
//         }

//         return $objs;
//     }

//     public function countChangelogByContentType($contenttype, $options)
//     {
//         if (is_array($contenttype)) {
//             $contenttype = $contenttype['slug'];
//         }
//         $tablename = $this->getTablename('log_change');
//         $sql = "SELECT COUNT(1) " .
//                "    FROM $tablename as log " .
//                "    WHERE contenttype = ? ";
//         $params = array($contenttype);
//         if (isset($options['contentid'])) {
//             $sql .= "    AND contentid = ? ";
//             $params[] = intval($options['contentid']);
//         }
//         if (isset($options['id'])) {
//             $sql .= "    AND log.id = ? ";
//             $params[] = intval($options['id']);
//         }

//         return $this->app['db']->fetchColumn($sql, $params);
//     }

//     /**
//      * Get a content changelog entry by ID
//      * @param mixed $contenttype Should be a string content type slug, or an
//      *                           associative array containing a key named
//      *                           'slug'
//      * @param $contentid
//      * @param int $id The content-changelog ID
//      * @return \Bolt\ChangeLogItem|null
//      */
//     public function getChangelogEntry($contenttype, $contentid, $id)
//     {
//         return $this->getOrderedChangelogEntry($contenttype, $contentid, $id, '=');
//     }

//     /**
//      * Get the content changelog entry that follows the given ID.
//      * @param mixed $contenttype Should be a string content type slug, or an
//      *                           associative array containing a key named
//      *                           'slug'
//      * @param $contentid
//      * @param int $id The content-changelog ID
//      * @return \Bolt\ChangeLogItem|null
//      */
//     public function getNextChangelogEntry($contenttype, $contentid, $id)
//     {
//         return $this->getOrderedChangelogEntry($contenttype, $contentid, $id, '>');
//     }

//     /**
//      * Get the content changelog entry that precedes the given ID.
//      * @param mixed $contenttype Should be a string content type slug, or an
//      *                           associative array containing a key named
//      *                           'slug'
//      * @param $contentid
//      * @param int $id The content-changelog ID
//      * @return \Bolt\ChangeLogItem|null
//      */
//     public function getPrevChangelogEntry($contenttype, $contentid, $id)
//     {
//         return $this->getOrderedChangelogEntry($contenttype, $contentid, $id, '<');
//     }

//     /**
//      * Get one changelog entry from the database.
//      *
//      * @param mixed $contenttype Should be a string content type slug, or an
//      *                           associative array containing a key named
//      *                           'slug'
//      * @param $contentid
//      * @param int $id The content-changelog ID
//      * @param string $cmpOp One of '=', '<', '>'; this parameter is used
//      *                       to select either the ID itself, or the subsequent
//      *                       or preceding entry.
//      * @throws \Exception
//      * @return \Bolt\ChangeLogItem|null
//      */
//     private function getOrderedChangelogEntry($contenttype, $contentid, $id, $cmpOp)
//     {
//         if (is_array($contenttype)) {
//             $contenttype = $contenttype['slug'];
//         }
//         switch ($cmpOp) {
//             case '=':
//                 $ordering = ''; // no need to order
//                 break;
//             case '<':
//                 $ordering = " ORDER BY date DESC";
//                 break;
//             case '>':
//                 $ordering = " ORDER BY date ";
//                 break;
//         }
//         $tablename = $this->getTablename('log_change');
//         $contentTablename = $this->getTablename($contenttype);
//         $sql = "SELECT log.* " .
//                "    FROM $tablename as log " .
//                "    LEFT JOIN " . $contentTablename . " as content " .
//                "    ON content.id = log.contentid " .
//                "    WHERE log.id $cmpOp ? " .
//                "    AND log.contentid = ? " .
//                "    AND contenttype = ? " .
//                $ordering .
//                "    LIMIT 1";
//         $params = array($id, $contentid, $contenttype);

//         $row = $this->app['db']->fetchAssoc($sql, $params);
//         if (is_array($row)) {
//             return new ChangeLogItem($this->app, $row);
//         } else {
//             return null;
//         }
//     }
}
