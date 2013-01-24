<?php

namespace Bolt;

use Silex;

/**
 * Simple logging class for Bolt
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 **/
class Log
{

    private $app;
    private $user;
    private $prefix;
    private $tablename;
    private $route;
    private $memorylog;
    private $values;

    public function __construct(Silex\Application $app)
    {

        $this->app = $app;
        $this->user = $app['session']->get('user');

        $this->prefix = isset($app['config']['general']['database']['prefix']) ? $app['config']['general']['database']['prefix'] : "bolt_";

        // Make sure prefix ends in '_'. Prefixes without '_' are lame..
        if ($this->prefix[ strlen($this->prefix)-1 ] != "_") {
            $this->prefix .= "_";
        }

        $this->tablename = $this->prefix . "log";

        $this->route = "";

        $this->memorylog = array();
        $this->values = array();

    }

    public function setRoute($route)
    {
        $this->route = $route;

    }

    public function errorhandler($message, $filename, $line)
    {
        $log = array(
            'date' => date('Y-m-d H:i:s'),
            'message' => $message,
            'requesturi' => !empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "-",
            'route' => $this->route,
            'ip' => !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "-",
            'file' => $filename,
            'line' => $line,
        );

        $this->memorylog[] = $log;

    }

    public function add($message, $level = 1, $content = false, $code = '')
    {

        $backtrace = debug_backtrace();

        $root = dirname($_SERVER['DOCUMENT_ROOT']);
        $filename = str_replace($root, "", $backtrace[0]['file']);

        $this->user = $this->app['session']->get('user');

        $username = isset($this->user['username']) ? $this->user['username'] : "";

        // echo "<pre>\n" . util::var_dump($this->user, true) . "</pre>\n";

        $log = array(
            'username' => $username,
            'level' => $level,
            'date' => date('Y-m-d H:i:s'),
            'message' => $message,
            'requesturi' => $_SERVER['REQUEST_URI'],
            'route' => $this->route,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'file' => $filename,
            'line' => $backtrace[0]['line'],
            'code' => $code,
            'dump' => ""
        );

        if (is_object($content)) {
            $log['contenttype'] = $content->contenttype['slug'];
            $log['content_id'] = intval($content->id);
        } else {
            $log['contenttype'] = "";
            $log['content_id'] = "";
        }

        // echo "<pre>\n" . util::var_dump($log, true) . "</pre>\n";

        $this->memorylog[] = $log;

        // Don't choke if we try to insert into the log, but it's not working.
        try {
            $this->app['db']->insert($this->tablename, $log);
        } catch (\Exception $e) {
            // Nothing..
        }

    }

    public function getMemorylog()
    {
        return $this->memorylog;
    }

    public function getActivity($amount = 10, $minlevel = 1)
    {

        $codes = "'save content', 'login', 'logout', 'fixme', 'user'";

        $page = $this->app['request']->query->get('page');
        if (empty($page)) {
            $page=1;
        }

        $query = sprintf('SELECT * FROM %s WHERE code IN (%s) OR (level >= %s) ORDER BY date DESC LIMIT %s, %s;',
            $this->tablename,
            $codes,
            $minlevel,
            intval(($page-1)*$amount),
            intval($amount)
            );

        // echo "<pre>\n" . util::var_dump($query, true) . "</pre>\n";

        $stmt = $this->app['db']->executeQuery($query);

        $rows = $stmt->fetchAll(2); // 2 = Query::HYDRATE_COLUMN

        // Set up the pager
        $pagerquery = sprintf('SELECT count(*) as count FROM %s WHERE code IN (%s) OR (level >= %s)',
            $this->tablename,
            $codes,
            $minlevel);
        $rowcount = $this->app['db']->executeQuery($pagerquery)->fetch();
        $pager = array(
            'for' => 'activity',
            'count' => $rowcount['count'],
            'totalpages' => ceil($rowcount['count'] / $amount),
            'current' => $page,
            'showing_from' => ($page-1)*$amount + 1,
            'showing_to' => ($page-1)*$amount + count($rows)
        );

        $GLOBALS['pager']['activity'] = $pager;

        return $rows;

    }

    /**
     * Setting a value for later use..
     *
     * @param string $key
     * @param string $value
     */
    public function setValue($key, $value)
    {
        $this->values[$key] = $value;
    }

    /**
     * Getting a previously set value
     *
     * @param string $key
     * @return string
     */
    public function getValue($key)
    {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        } else {
            return false;
        }
    }

    /**
     * Getting all previously set values
     *
     * @param string $key
     * @return array
     */
    public function getValues()
    {

        return $this->values;

    }


    public function trim() {

        $query = sprintf('DELETE FROM %s WHERE level="1";',
            $this->tablename
        );
        $this->app['db']->executeQuery($query);

        $query = sprintf('DELETE FROM %s WHERE level="2" AND date < "%s";',
            $this->tablename,
            date('Y-m-d H:i:s', strtotime('-2 day'))
        );
        $this->app['db']->executeQuery($query);

        $query = sprintf('DELETE FROM %s WHERE date < "%s";',
            $this->tablename,
            date('Y-m-d H:i:s', strtotime('-7 day'))
        );
        $this->app['db']->executeQuery($query);

    }

    public function clear() {

        $configdb = $getDBOptions($this->app);

        if (isset($configdb['driver']) && ( $configdb['driver'] == "pdo_sqlite" || $configdb['driver'] == "sqlite" ) ) {

            // sqlite
            $query = sprintf('DELETE FROM %s; UPDATE SQLITE_SEQUENCE SET seq = 0 WHERE name = %s;',
                $this->tablename,
                $this->tablename
            );

        } else {

            // mysql
            $query = sprintf('TRUNCATE %s;',
                $this->tablename
            );

        }
        // @todo: handle postgres (and other non mysql) database syntax

        $this->app['db']->executeQuery($query);

    }



}
