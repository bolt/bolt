<?php

namespace Authenticate;

use Bolt;
use Silex;

/**
 * The visitor handles the database storage of known visitors
 */
class Visitor
{
    var $visitor;
    private $provider;
    private $profile;
    private $db;
    private $config;
    private $prefix;
    private $session;

    public function __construct(Silex\Application $app)
    {
        $this->config = $app['config'];
        $this->db = $app['db'];
        $this->session = $app['session'];
        $this->prefix = $this->config->get('general/database/prefix', "bolt_");
        // Make sure prefix ends in '_'. Prefixes without '_' are lame..
        if ($this->prefix[ strlen($this->prefix)-1 ] != "_") {
            $this->prefix .= "_";
        }
    }

    public function setProvider($provider) {
        $this->provider = $provider;
    }

    public function setProfile($profile) {
        $this->profile = $profile;
    }

    private function loadVisitor($visitor_raw) {
        if (!$visitor_raw) {
            return false;
        }
        $this->visitor = $visitor_raw;
        $this->profile = unserialize($this->visitor['providerdata']);
        return $this->visitor;
    }

    public function checkByAppToken($username, $apptoken) {
        $visitor_raw = $this->get_one_by(array(
                            array('username', '=', $username),
                        ));
        if (!$visitor_raw) {
            return false;
        }
        if ($visitor_raw['apptoken'] !== $apptoken) {
            return false;
        }
        return $this->loadVisitor($visitor_raw);
    }

    public function checkExisting() {
        if (!$this->profile->displayName) {
            return false;
        }
        $visitor_raw = $this->get_one_by(
            array(
                array('username', '=', $this->profile->displayName),
                array('provider', '=', $this->provider),
            ));
        return $this->loadVisitor($visitor_raw);
    }

    public function get_table_name() {
        return $this->prefix . "visitors";
    }

    private function get_stmt_by($filters) {
        $where = array();
        $params = array();
        foreach ($filters as $filter) {
            list($column, $operator, $value) = $filter;
            $where[] = "$column $operator :$column";
            $params[$column] = $value;
        }
        $sql = "SELECT * FROM " . $this->get_table_name();
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt;
    }

    private function get_by($filters) {
        $stmt = $this->get_stmt_by($filters);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function get_one_by($filters) {

        try {
            $stmt = $this->get_stmt_by($filters);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch(\Exception $e) {
            $result = null;
        }
        
        return $result;
    }

    public function load_by_id($visitor_id) {
        $this->visitor = $this->get_one_by(array(array('id', '=', $visitor_id)));
        // FIXME! - unserialize borkt, als in 'providerdata' een niet wester-europees karakter zit!
        // \util::var_dump($this->visitor['providerdata']);
        $this->profile = unserialize($this->visitor['providerdata']);

        return $this->visitor;
    }

    // save new visitor
    public function save() {
        $serialized = serialize($this->profile);
        // id is set to autoincrement, so let the DB handle it
        $content = array(
            'username' => $this->profile->displayName,
            'provider' => $this->provider,
            'providerdata' => $serialized
        );
        $res = $this->db->insert($this->get_table_name(), $content);
        $id = $this->db->lastInsertId();
        return $id;
    }

    // update existing visitor
    public function update() {
        $serialized = serialize($this->profile);
        $content = array(
            'username' => $this->visitor['username'],
            'provider' => $this->provider,
            'providerdata' => $serialized
        );
        return $this->db->update($this->get_table_name(), $content, array('id' => $this->visitor['id']));
    }

    public static function generate_token() {
        // We'll avoid characters that look too much alike, specifically
        // O and 0, 1 and I.
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $count = strlen($chars) - 1;
        $length = 8;
        $str = '';
        for ($i = 0; $i < $length; ++$i) {
            $cix = mt_rand(0, $count);
            $str .= $chars[$cix];
        }
        return $str;
    }

    public function reset_app_token() {
        $token = self::generate_token();
        $content = array('apptoken' => $token);
        return $this->db->update($this->get_table_name(), $content, array('id' => $this->visitor['id']));
        return $token;
    }

    public function check_app_token() {
        error_log("check_app_token");
        if (!$this->visitor) {
            error_log("no visitor");
            return false;
        }
        if (empty($this->visitor['apptoken'])) {
            error_log("empty token, generating a new one");
            $this->visitor['apptoken'] = $this->reset_app_token();
        }
        error_log($this->visitor['apptoken']);
        return $this->visitor['apptoken'];
    }

    // delete visitor
    // TODO: fix this if needed
    public function delete($visitor_id = null) {
        //$this->db->delete($this->visitor);
    }

}
