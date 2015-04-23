<?php

namespace Bolt\Logger;

use Bolt\Pager;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Silex\Application;

/**
 * Bolt's logger service class.
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

    /**
     * Trim the log.
     *
     * @param string $log
     *
     * @throws \Exception
     */
    public function trim($log)
    {
        if ($log == 'system') {
            $table = $this->table_system;
        } elseif ($log == 'change') {
            $table = $this->table_change;
        } else {
            throw new \Exception("Invalid log type requested: $log");
        }

        /** @var \Doctrine\DBAL\Query\QueryBuilder $query */
        $query = $this->app['db']->createQueryBuilder()
                                 ->delete($table)
                                 ->where('date < :date')
                                 ->setParameter(':date', date('Y-m-d H:i:s', strtotime('-7 day')));

        $query->execute();
    }

    /**
     * Clear a log.
     *
     * @param string $log
     *
     * @throws \Exception
     */
    public function clear($log)
    {
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
     * Get a specific activity log.
     *
     * @param string  $log     The log to query.  Either 'change' or 'system'
     * @param integer $amount  Number of results to return
     * @param integer $level
     * @param string  $context
     *
     * @throws \Exception
     *
     * @return array
     */
    public function getActivity($log, $amount = 10, $level = null, $context = null)
    {
        if ($log == 'system') {
            $table = $this->table_system;
        } elseif ($log == 'change') {
            $table = $this->table_change;
        } else {
            throw new \Exception("Invalid log type requested: $log");
        }

        try {
            /** @var $reqquery \Symfony\Component\HttpFoundation\ParameterBag */
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
                    'for'          => 'activity',
                    'count'        => $rowcount['count'],
                    'totalpages'   => ceil($rowcount['count'] / $amount),
                    'current'      => $page,
                    'showing_from' => ($page - 1) * $amount + 1,
                    'showing_to'   => ($page - 1) * $amount + count($rows)
            );

            $this->app['storage']->setPager('activity', $pager);
        } catch (DBALException $e) {
            // Oops. User will get a warning on the dashboard about tables that need to be repaired.
            $rows = array();
        }

        if ($log == 'change') {
            return $this->decodeChangeLog($rows);
        }

        return $rows;
    }

    /**
     * Set any required WHERE clause on a QueryBuilder.
     *
     * @param QueryBuilder $query
     * @param integer      $level
     * @param string       $context
     *
     * @return QueryBuilder
     */
    private function setWhere(QueryBuilder $query, $level = null, $context = null)
    {
        if ($level || $context) {
            $where = $query->expr()->andX();

            if ($level) {
                $where->add($query->expr()->eq('level', ':level'));
            }

            if ($context) {
                $where->add($query->expr()->eq('context', ':context'));
            }
            $query
                ->where($where)
                ->setParameters(array(
                    ':level'   => $level,
                    ':context' => $context
                ));
        }

        return $query;
    }

    /**
     * Decode JSON in change log fields.
     *
     * @param array $rows
     *
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
}
