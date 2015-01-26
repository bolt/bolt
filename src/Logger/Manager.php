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
     * @var string
     */
    private $tablename;

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

        $query = sprintf(
            "DELETE FROM %s WHERE date < ?;",
            $table
        );
        $this->app['db']->executeQuery(
            $query,
            array(date('Y-m-d H:i:s', strtotime('-7 day'))),
            array(\PDO::PARAM_STR)
        );
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

        if ($this->app['db']->getDriver()->getName() == 'pdo_sqlite') {
            // Sqlite
            $query = sprintf(
                "DELETE FROM %s; UPDATE SQLITE_SEQUENCE SET seq = 0 WHERE name = '%s'",
                $table,
                $table
            );
        } else {
            // MySQL and PostgreSQL the same
            $query = sprintf(
                'TRUNCATE %s;',
                $table
            );
        }

        $this->app['db']->executeQuery($query);
    }

    /**
     * Get a specific activity log
     *
     * @param  string            $log    The log to query.  Either 'change' or 'system'
     * @param  integer           $amount Number of results to return
     * @throws LowlevelException
     */
    public function getActivity($log, $amount = 10)
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
            $query = sprintf(
                    "SELECT * FROM %s ORDER BY id DESC",
                    $table
            );

            // Modify limit query for the pager
            $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, intval($amount), intval(($page - 1) * $amount));

            /** @var $stmt \Doctrine\DBAL\Driver\Statement */
            $stmt = $this->app['db']->executeQuery($query);

            // 2 == Query::HYDRATE_COLUMN
            $rows = $stmt->fetchAll(2);

            // Find out how many entries we're paging form
            $pagerQuery = sprintf(
                "SELECT count(*) as count FROM %s",
                $table
            );
            $rowcount = $this->app['db']->executeQuery($pagerQuery)->fetch();

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
}
