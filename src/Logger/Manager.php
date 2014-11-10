<?php

namespace Bolt\Logger;

use Doctrine\DBAL\Schema\Schema;

use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

use Bolt\Application;
use Bolt\Helpers\String;
use Bolt\Logger\Formatter\System;

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
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

public function trim()
    {
        $query = sprintf(
            "DELETE FROM %s WHERE level='1';",
            $this->tablename
        );
        $this->app['db']->executeQuery($query);

        $query = sprintf(
            "DELETE FROM %s WHERE level='2' AND date < ?;",
            $this->tablename
        );

        $this->app['db']->executeQuery(
            $query,
            array(date('Y-m-d H:i:s', strtotime('-2 day'))),
            array(\PDO::PARAM_STR)
        );

        $query = sprintf(
            "DELETE FROM %s WHERE date < ?;",
            $this->tablename
        );
        $this->app['db']->executeQuery(
            $query,
            array(date('Y-m-d H:i:s', strtotime('-7 day'))),
            array(\PDO::PARAM_STR)
        );
    }

    public function clear()
    {
        $configdb = $this->app['config']->getDBOptions();

        if (isset($configdb['driver']) && ($configdb['driver'] == "pdo_sqlite")) {

            // sqlite
            $query = sprintf(
                "DELETE FROM %s; UPDATE SQLITE_SEQUENCE SET seq = 0 WHERE name = '%s'",
                $this->tablename,
                $this->tablename
            );

        } else {

            // mysql and pgsql the same
            $query = sprintf(
                'TRUNCATE %s;',
                $this->tablename
            );

        }

        $this->app['db']->executeQuery($query);
    }

    public function getActivity($amount = 10, $minlevel = 1)
    {
        $codes = array('save content', 'login', 'logout', 'fixme', 'user');

        $param = Pager::makeParameterId('activity');
        /* @var $query \Symfony\Component\HttpFoundation\ParameterBag */
        $query = $this->app['request']->query;
        $page = ($query) ? $query->get($param, $query->get('page', 1)) : 1;

        $query = sprintf(
            "SELECT * FROM %s WHERE code IN (?) OR (level >= ?) ORDER BY date DESC",
            $this->tablename
        );
        $query = $this->app['db']->getDatabasePlatform()->modifyLimitQuery($query, intval($amount), intval(($page - 1) * $amount));

        $params = array(
            $codes, $minlevel
        );
        $paramTypes = array(
            DoctrineConn::PARAM_STR_ARRAY, \PDO::PARAM_INT
        );

        $stmt = $this->app['db']->executeQuery($query, $params, $paramTypes);

        $rows = $stmt->fetchAll(2); // 2 = Query::HYDRATE_COLUMN

        // Set up the pager
        $pagerQuery = sprintf(
            "SELECT count(*) as count FROM %s WHERE code IN (?) OR (level >= ?)",
            $this->tablename
        );
        $params = array($codes, $minlevel);
        $paramTypes = array(DoctrineConn::PARAM_STR_ARRAY, \PDO::PARAM_INT);
        $rowcount = $this->app['db']->executeQuery($pagerQuery, $params, $paramTypes)->fetch();

        $pager = array(
            'for' => 'activity',
            'count' => $rowcount['count'],
            'totalpages' => ceil($rowcount['count'] / $amount),
            'current' => $page,
            'showing_from' => ($page - 1) * $amount + 1,
            'showing_to' => ($page - 1) * $amount + count($rows)
        );

        $this->app['storage']->setPager('activity', $pager);

        return $rows;
    }
}
