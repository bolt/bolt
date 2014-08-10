<?php

namespace Authenticate;

use Bolt;
use Silex;

/**
 * The visitor handles the database storage of known visitors
 */
class Visitor
{
    public $visitor;
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

    public function setProvider($provider)
    {
        $this->provider = $provider;
    }

    public function setProfile($profile)
    {
        $this->profile = $profile;
    }

    private function loadVisitor($visitor_raw)
    {
        if (!$visitor_raw) {
            return false;
        }
        $this->visitor = $visitor_raw;
        $this->profile = unserialize($this->visitor['providerdata']);
        return $this->visitor;
    }

    public function checkByAppToken($username, $apptoken)
    {
        $visitor_raw = $this->get_visitor_record(
            array(
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

    public function checkExisting()
    {
        if (!$this->profile->displayName) {
            return false;
        }
        $visitor_raw = $this->get_visitor_record(
            array(
                array('username', '=', $this->profile->displayName),
                array('provider', '=', $this->provider),
            ));
        return $this->loadVisitor($visitor_raw);
    }

    public function get_table_name()
    {
        return $this->prefix . "visitors";
    }

    private function get_visitor_record($filters)
    {
        $query = "SELECT * FROM " . $this->get_table_name();
        $where = array();
        $map = array();

        // Separate filters and their paramters
        foreach ($filters as $filter) {
            list($column, $operator, $value) = $filter;
            $where[] = "$column $operator :$column";
            $map[":$column"] = $value;
        }

        // Add query manipulation parameters
        if (!empty($where)) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        $result = $this->db->fetchAll($query, $map);
        $result = array_shift($result);

        if (isset($result['providerdata'])) {
            // Catch old PHP serialized data
            if ($this->is_serialized($result['providerdata'])) {
                $result['providerdata'] = unserialize($result['providerdata']);
            } else {
                $result['providerdata'] = json_decode($result['providerdata']);
            }
        }

        return $result;
    }

    public function load_by_id($visitor_id)
    {
        $this->visitor = $this->get_visitor_record(array(array('id', '=', $visitor_id)));
        $this->profile = $this->visitor['providerdata'];

        return $this->visitor;
    }

    // save new visitor
    public function save()
    {
        $json = json_encode($this->profile);
        // id is set to autoincrement, so let the DB handle it
        $content = array(
            'username' => $this->profile->displayName,
            'provider' => $this->provider,
            'providerdata' => $json
        );
        $res = $this->db->insert($this->get_table_name(), $content);
        $id = $this->db->lastInsertId();
        return $id;
    }

    // update existing visitor
    public function update()
    {
        $json = json_encode($this->profile);
        $content = array(
            'username' => $this->visitor['username'],
            'provider' => $this->provider,
            'providerdata' => $json
        );
        return $this->db->update($this->get_table_name(), $content, array('id' => $this->visitor['id']));
    }

    public static function generate_token()
    {
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

    public function reset_app_token()
    {
        $token = self::generate_token();
        $content = array('apptoken' => $token);
        return $this->db->update($this->get_table_name(), $content, array('id' => $this->visitor['id']));
        return $token;
    }

    public function check_app_token()
    {
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
    public function delete($visitor_id = null)
    {
        //$this->db->delete($this->visitor);
    }
	
	
    /**
     * Check value to find if it was serialized.
     *
     * If $data is not an string, then returned value will always be false.
     * Serialized data is always a string.
     *
     * @param  mixed $data Value to check to see if was serialized
     * @return boolean
     */
    public static function is_serialized( $data )
    {
        // If it isn't a string, it isn't serialized
        if ( ! is_string( $data ) ) {
            return FALSE;
        }

        $data = trim( $data );

        if ( 'N;' == $data ) {
            return TRUE;
        }

        $length = strlen( $data );

        if ( $length < 4 ) {
            return FALSE;
        }

        if ( ':' !== $data[1] ) {
            return FALSE;
        }

        $lastc = $data[$length - 1];

        if ( ';' !== $lastc && '}' !== $lastc ) {
            return FALSE;
        }

        $token = $data[0];

        switch ( $token ) {
            case 's' :
                if ( '"' !== $data[$length-2] ) {
                    return FALSE;
                }
            case 'a' :
            case 'O' :
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
            case 'b' :
            case 'i' :
            case 'd' :
                return (bool) preg_match( "/^{$token}:[0-9.E-]+;\$/", $data );
        }

        return FALSE;
    }

}
