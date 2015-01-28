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
class Manager
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var boolean
     */
    private $initialized = false;

    /**
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function trim($log)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if ($log == 'system') {
            $table = $this->table_system;
        } elseif ($log == 'change') {
            $table = $this->table_change;
        } else {
            throw new \Exception("Invalid log type requested: $log");
        }

        $query = $this->app['db']->createQueryBuilder()
                                 ->delete($table)
                                 ->where('date < :date')
                                 ->setParameter(':date', date('Y-m-d H:i:s', strtotime('-7 day')));

        $query->execute();
    }

    public function clear($log)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if ($log == 'system') {
            $table = $this->table_system;
        } elseif ($log == 'change') {
            $table = $this->table_change;
        } else {
            throw new \Exception("Invalid log type requested: $log");
        }

        // Get the platform specific truncate SQL
        $query = $this->app['db']->getDatabasePlatform()->getTruncateTableSql($table);

        $this->app['db']->executeQuery($query);
    }

    /**
     * Get a specific activity log
     *
     * @param  string            $log    The log to query.  Either 'change' or 'system'
     * @param  integer           $amount Number of results to return
     * @throws LowlevelException
     */
    public function getActivity($log, $amount = 10, $level = null, $context = null)
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if ($log == 'system') {
            $table = $this->table_system;
        } elseif ($log == 'change') {
            $table = $this->table_change;
        } else {
            throw new \Exception("Invalid log type requested: $log");
        }

        try {
            /** @var $query \Symfony\Component\HttpFoundation\ParameterBag */
            $reqquery = $this->app['request']->query;

            // Test/get page number
            $param = Pager::makeParameterId('activity');
            $page = ($reqquery) ? $reqquery->get($param, $reqquery->get('page', 1)) : 1;

            // Build the base query
            $query = $this->app['db']->createQueryBuilder()
                          ->select('*')
                          ->from($table)
                          ->orderBy('id', 'DESC')
                          ->setMaxResults(intval($amount))
                          ->setFirstResult(intval(($page - 1) * $amount));

            // Set up optional WHERE clause(s)
            $query = $this->setWhere($query, $level, $context);

            // Get the rows from the database
            $rows = $query->execute()->fetchAll();

            // Find out how many entries we're paging form
            $query = $this->app['db']->createQueryBuilder()
                          ->select('COUNT(id) as count')
                          ->from($table);

            // Set up optional WHERE clause(s)
            $query = $this->setWhere($query, $level, $context);

            $rowcount = $query->execute()->fetch();

            // Set up the pager
            $pager = array(
                    'for' => 'activity',
                    'count' => $rowcount['count'],
                    'totalpages' => ceil($rowcount['count'] / $amount),
                    'current' => $page,
                    'showing_from' => ($page - 1) * $amount + 1,
                    'showing_to' => ($page - 1) * $amount + count($rows)
            );

            $this->app['storage']->setPager('activity', $pager);
        } catch (\Doctrine\DBAL\DBALException $e) {
            // Oops. User will get a warning on the dashboard about tables that need to be repaired.
            $rows = array();
        }

        if ($log == 'system') {
            return $rows;
        } elseif ($log == 'change') {
            return $this->decodeChangeLog($rows);
        }
    }

    /**
     * Set any required WHERE clause on a QueryBuilder
     *
     * @param  Doctrine\DBAL\Query\QueryBuilder $query
     * @param  integer                          $level
     * @param  string                           $context
     * @return Doctrine\DBAL\Query\QueryBuilder
     */
    private function setWhere($query, $level = null, $context = null)
    {
        if ($level || $context) {
            $where = $query->expr()->andX();

            if ($level) {
                $where->add($query->expr()->eq('level', ':level'));
            }

            if ($context) {
                $where->add($query->expr()->eq('context', ':context'));
            }
            $query->where($where)
                  ->setParameters(array(
                      ':level'   => $level,
                      ':context' => $context
            ));
        }

        return $query;
    }

    /**
     * Decode JSON in change log fields
     *
     * @param  array $rows
     * @return array
     */
    private function decodeChangeLog($rows)
    {
        if (!is_array($rows)) {
            return $rows;
        }

        foreach ($rows as $key => $row) {
            $rows[$key]['diff'] = json_decode($row['diff'], true);
        }

        return $rows;
    }

    /**
     * Initialize
     */
    private function initialize()
    {
        $prefix = $this->app['config']->get('general/database/prefix', "bolt_");
        $this->table_change = sprintf("%s%s", $prefix, 'log_change');
        $this->table_system = sprintf("%s%s", $prefix, 'log_system');
        $this->initialized = true;
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

//     /**
//      * Get content changelog entries for all content types
//      * @param array $options An array with additional options. Currently, the
//      *                       following options are supported:
//      *                       - 'limit' (int)
//      *                       - 'offset' (int)
//      *                       - 'order' (string)
//      * @return array
//      */
//     public function getChangelog($options)
//     {
//         $tablename = $this->getTablename('log_change');
//         $sql = "SELECT log.*, log.title " .
//                "    FROM $tablename as log ";
//         $sql .= $this->makeOrderLimitSql($options);

//         $rows = $this->app['db']->fetchAll($sql, array());
//         $objs = array();
//         foreach ($rows as $row) {
//             $objs[] = new ChangelogItem($this->app, $row);
//         }

//         return $objs;
//     }

//     public function countChangelog()
//     {
//         $tablename = $this->getTablename('log_change');
//         $sql = "SELECT COUNT(1) " .
//                "    FROM $tablename as log ";

//         return $this->app['db']->fetchColumn($sql, array());
//     }

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
//             $objs[] = new ChangelogItem($this->app, $row);
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
//      * @return \Bolt\ChangelogItem|null
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
//      * @return \Bolt\ChangelogItem|null
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
//      * @return \Bolt\ChangelogItem|null
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
//      * @return \Bolt\ChangelogItem|null
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
//             return new ChangelogItem($this->app, $row);
//         } else {
//             return null;
//         }
//     }
}
