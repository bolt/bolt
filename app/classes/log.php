<?php


/**
 * Simple logging class for Bolt
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 **/
class Log {




    public function __construct($app)
    {
        $this->db = $app['db'];
        $this->user = $app['session']->get('user');

        $this->prefix = isset($app['config']['general']['database']['prefix']) ? $app['config']['general']['database']['prefix'] : "bolt_";

        // Make sure prefix ends in '_'. Prefixes without '_' are lame..
        if ($this->prefix[ strlen($this->prefix)-1 ] != "_") {
            $this->prefix .= "_";
        }

        $this->tablename = $this->prefix . "log";

        $this->route = "";

        $this->memorylog = array();

    }

    // TODO: Do we need this?
    public function setUser($user) {

        $this->user = $app['session']->get('user');

    }

    public function setRoute($route) {

        $this->route = $route;

    }

    public function add($message, $level=1, $content=false, $code='') {
        global $app;

        $backtrace = debug_backtrace();

        $root = dirname($_SERVER['DOCUMENT_ROOT']);
        $filename = str_replace($root, "", $backtrace[0]['file']);

        $this->user = $app['session']->get('user');

        // echo "<pre>\n" . util::var_dump($this->user, true) . "</pre>\n";

        $log = array(
            'username' => $this->user['username'],
            'level' => $level,
            'date' => date('Y-m-d H:i:s'),
            'message' => $message,
            'requesturi' => $_SERVER['REQUEST_URI'],
            'route' => $this->route,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'file' => $filename,
            'line' => $backtrace[0]['line'],
            'code' => $code
        );

        if (is_object($content)) {
            $log['contenttype'] = $content->contenttype['slug'];
            $log['content_id'] = intval($content->id);
        }

        // echo "<pre>\n" . util::var_dump($log, true) . "</pre>\n";

        $this->memorylog[] = $log;

        $this->db->insert($this->tablename, $log);

    }

    public function getMemorylog() {

        return $this->memorylog;

    }

    public function getActivity($amount = 10) {

        $codes = "'save content', 'login', 'logout'";

        $stmt = $this->db->executeQuery('SELECT * FROM '.$this->tablename.' WHERE code IN ('.$codes.') ORDER BY date DESC;');

        $rows = $stmt->fetchAll(2); // 2 = Query::HYDRATE_COLUMN

        return $rows;

    }


}

