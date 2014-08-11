<?php

namespace Authenticate;

use Bolt;
use Silex;

/**
 * The session handles the database storage of session id's
 */
class Session
{
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

    // check if sessions table exists - if not create it

    // list all active sessions
    public function active($visitor_id = null)
    {
        if($visitor_id) {
            $sql = "SELECT * from " . $this->prefix ."visitors_sessions WHERE visitor_id = :visitorid";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue("visitorid", $visitor_id);
        } else {
            $sql = "SELECT * from " . $this->prefix ."visitors_sessions";
            $stmt = $this->db->query($sql);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // login known visitor
    public function login($visitor_id = null)
    {
        $browsertoken = $this->token();
        $visitortoken = $this->token($visitor_id);
        $token = $browsertoken . $visitortoken;

        // set session
        $this->session->set('visitortoken', $token);

        // save session <-> user to storage
        if($visitor_id) {
            // id is set to autoincrement, so let the DB handle it
            $tablename =  $this->prefix ."visitors_sessions";
            $content = array(
                'visitor_id' =>  $visitor_id,
                'lastseen' => date('Y-m-d H:i:s', $_SERVER["REQUEST_TIME"]),
                'sessiontoken' => $token
            );
            $res = $this->db->insert($tablename, $content);
        }

        // This is a good time to clear old logins..
        $this->clear_old();

        // we probably want to know the token on return
        return $token;
    }

    // load visitor session
    public function load($token = null)
    {
        if($token) {
            $table = $this->prefix . 'visitors_sessions';
            $query = "SELECT * from $table WHERE sessiontoken = :token";
            $map = array(':token' => $token);

            $all = $this->db->fetchAll($query, $map);

            return array_shift($all);
        } else {
           return false;
        }
    }

    // update existing visitor session
    public function update($token = null, $visitor_id)
    {
        if($token) {
            $tablename =  $this->prefix . "visitors_sessions";
            $content = array(
                'visitor_id' =>  $visitor_id,
                'lastseen' => date('Y-m-d H:i:s', $_SERVER["REQUEST_TIME"])
            );
            return $this->db->update($tablename, $content, array('sessiontoken' => $token));
        }
    }

    // destroy visitor session (logout)
    public function clear($token = null)
    {
        if($token) {
            // delete current session from storage
            $tablename =  $this->prefix ."visitors_sessions";
            return $this->db->delete($tablename, array('sessiontoken' => $token));
            // reset session token
            $this->session->set('visitortoken', null);
        }
    }

    // destroy all visitor sessions
    public function clear_all($visitor_id = null)
    {
        if($visitor_id) {
            // delete all visitor sessions from storage
            $tablename =  $this->prefix ."visitors_sessions";
            return $this->db->delete($tablename, array('visitor_id' => $visitor_id));
            // reset session token
            $this->session->set('visitortoken', null);
        }
    }

    // destroy all old sessions
    public function clear_old()
    {
        // delete all old sessions from storage
        $sql = "DELETE FROM " . $this->prefix . "visitors_sessions WHERE lastseen <= :toooldtime";
        $stmt = $this->db->prepare($sql);
        $days14 = date('Y-m-d H:i:s', ($_SERVER["REQUEST_TIME"] - (60*60*24*14)));
        $stmt->bindValue("toooldtime", $days14); // 14 days ago
        $stmt->execute();
    }

    // create new session token - should be reasonably unique
    public function token($key = null)
    {
        if(!$key) {
            $seed = $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT'] . $_SERVER["REQUEST_TIME"];
        } else {
            $seed = $_SERVER['REMOTE_ADDR'] . $key . $_SERVER["REQUEST_TIME"];
        }
        return md5($seed);
    }

}
