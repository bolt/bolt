<?php

namespace Bolt;

use Doctrine\Common\Cache\FilesystemCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class for our config object. Implemented as an extension of RecursiveArrayAccess
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 */
class Config
{

    private $app;
    private $reservedfieldnames;
    private $data;

    function __construct(\Bolt\Application $app) {

        $this->app = $app;

        $this->reservedfieldnames = array('id', 'slug', 'datecreated', 'datechanged', 'datepublish', 'datedepublish', 'username', 'status');

        if (!$this->loadCache()) {
            $this->getConfig();
            $this->saveCache();

            // if we have to reload the config, we will also want to make sure the DB integrity is checked.
            $this->app['session']->set('database_checked', 0);
        }

        $this->setTwigPath();

    }

    private function parseConfigYaml($basename, $default = array())
    {
        static $yamlparser = false;

        if ($yamlparser === false) {
            $yamlparser = new \Symfony\Component\Yaml\Parser();
        }

        $filename = BOLT_CONFIG_DIR . '/' . $basename;
        if (is_readable($filename)) {
            return $yamlparser->parse(file_get_contents($filename) . "\n");
        }

        return $default;
    }

    /**
     * Set a config value, using a path. For example:
     *
     * $app['config']->set('general/branding/name', 'Bolt');
     *
     * @param string $path
     * @param mixed $value
     * @return bool
     */
    public function set($path, $value)
    {
        $path = explode("/", $path);

        // Only do something if we get at least one key.
        if (empty($path[0])) {
            $logline = "Config: can't set empty path to '" . (string)$value ."'";
            $this->app['log']->add($logline, 3, '', 'config');
            return false;
        }

        // Set the base config, or initialize it.
        if (isset($this->data[ $path[0] ])) {
            $tempdata = $this->data[ $path[0] ];
        } else {
            $tempdata = array();
        }

        // Set the correct value.
        if (count($path)==2) {
            $this->data[ $path[0] ][ $path[1] ] = $value;
        } else if (count($path)==3) {
            $this->data[ $path[0] ][ $path[1] ][ $path[2] ] = $value;
        } else if (count($path)==4) {
            $this->data[ $path[0] ][ $path[1] ][ $path[2] ][ $path[3] ] = $value;
        } else if (count($path)==5) {
            $this->data[ $path[0] ][ $path[1] ][ $path[2] ][ $path[3] ][ $path[4] ] = $value;
        } else {
            $logline = "Config: can't set path " . implode("/", $path) . " to '" . (string)$value ."'";
            $this->app['log']->add($logline, 3, '', 'config');
            return false;
        }

        return true;

    }

    /**
     * Get a config value, using a path. For example:
     *
     * $var = $config->get('general/wysiwyg/ck/contentsCss');
     *
     * @param string $path
     * @param string $default
     * @return mixed
     */
    function get($path, $default = null)
    {

        $path = explode("/", $path);

        // Only do something if we get at least one key.
        if (empty($path[0]) || !isset($this->data[ $path[0] ]) ) {
            return false;
        }

        $value = null;

        // Get the correct value.
        if (count($path)==1 && isset($this->data[ $path[0] ])) {
            $value = $this->data[ $path[0] ];
        } else if (count($path)==2 && isset($this->data[ $path[0] ][ $path[1] ])) {
            $value = $this->data[ $path[0] ][ $path[1] ];
        } else if (count($path)==3 && isset($this->data[ $path[0] ][ $path[1] ][ $path[2] ])) {
            $value = $this->data[ $path[0] ][ $path[1] ][ $path[2] ];
        } else if (count($path)==4 && isset($this->data[ $path[0] ][ $path[1] ][ $path[2] ][ $path[3] ])) {
            $value = $this->data[ $path[0] ][ $path[1] ][ $path[2] ][ $path[3] ];
        } else if (count($path)==5 && isset($this->data[ $path[0] ][ $path[1] ][ $path[2] ][ $path[3] ][ $path[4] ])) {
            $value = $this->data[ $path[0] ][ $path[1] ][ $path[2] ][ $path[3] ][ $path[4] ];
        }

        if ($value != null) {
            return $value;
        } else {
            return $default;
        }

    }


    /**
     * Load the configuration from the various YML files.
     */
    function getConfig()
    {
        $config = array();

        // Read the config
        $config['general']    = array_merge($this->parseConfigYaml('config.yml'), $this->parseConfigYaml('config_local.yml'));
        $config['taxonomy']   = $this->parseConfigYaml('taxonomy.yml');
        $tempcontenttypes     = $this->parseConfigYaml('contenttypes.yml');
        $config['menu']       = $this->parseConfigYaml('menu.yml');
        $config['routing']     = $this->parseConfigYaml('routing.yml');
        $config['extensions'] = array();

        // @todo: If no config files can be found, get them from bolt.cm/files/default/

        $this->paths = getPaths($config);
        $this->setDefaults();

        if (isset($config['general']['wysiwyg']['ck']['contentsCss'])) {
            $config['general']['wysiwyg']['ck']['contentsCss'] = array(1 => $config['general']['wysiwyg']['ck']['contentsCss']);
        }
        $config['general'] = array_merge_recursive_distinct($this->defaultconfig, $config['general']);

        // Make sure the cookie_domain for the sessions is set properly.
        if (empty($config['general']['cookies_domain'])) {

            if (isset($_SERVER['HTTP_HOST'])) {
                $hostname = $_SERVER['HTTP_HOST'];
            } elseif (isset($_SERVER['SERVER_NAME'])) {
                $hostname = $_SERVER['SERVER_NAME'];
            } else {
                $hostname = "";
            }

            // Don't set the domain for a cookie on a "TLD" - like 'localhost', or if the server_name is an IP-address
            if ((strpos($hostname, ".") > 0) && preg_match("/[a-z0-9]/i", $hostname) ) {
                if (preg_match("/^www[0-9]*./", $hostname)) {
                    $config['general']['cookies_domain'] = "." . preg_replace("/^www[0-9]*./", "", $hostname);
                } else {
                    $config['general']['cookies_domain'] = "." . $hostname;
                }
                // Make sure we don't have consecutive '.'-s in the cookies_domain..
                $config['general']['cookies_domain'] = str_replace("..", ".", $config['general']['cookies_domain']);
            } else {
                $config['general']['cookies_domain'] = "";
            }
        }

        // Make sure Bolt's mount point is OK:
        $config['general']['branding']['path'] = "/" . safeString($config['general']['branding']['path']);

        // Clean up taxonomies
        foreach ($config['taxonomy'] as $key => $value) {
            if (!isset($config['taxonomy'][$key]['name'])) {
                $config['taxonomy'][$key]['name'] = ucwords($config['taxonomy'][$key]['slug']);
            }
            if (!isset($config['taxonomy'][$key]['singular_name'])) {
                $config['taxonomy'][$key]['singular_name'] = ucwords($config['taxonomy'][$key]['singular_slug']);
            }
            if (!isset($config['taxonomy'][$key]['slug'])) {
                $config['taxonomy'][$key]['slug'] = strtolower(safeString($config['taxonomy'][$key]['name']));
            }
            if (!isset($config['taxonomy'][$key]['singular_slug'])) {
                $config['taxonomy'][$key]['singular_slug'] = strtolower(safeString($config['taxonomy'][$key]['singular_name']));
            }
            if (!isset($config['taxonomy'][$key]['has_sortorder'])) {
                $config['taxonomy'][$key]['has_sortorder'] = false;
            }

            // Make sure the options are $key => $value pairs, and not have implied integers for keys.
            if (!empty($config['taxonomy'][$key]['options']) && is_array($config['taxonomy'][$key]['options'])) {
                $options = array();
                foreach($config['taxonomy'][$key]['options'] as $optionkey => $value) {
                    if (is_numeric($optionkey)) {
                        $optionkey = strtolower(safeString($value));
                    }
                    $options[$optionkey] = $value;
                }
                $config['taxonomy'][$key]['options'] = $options;
            }

        }

        // Clean up contenttypes
        $config['contenttypes'] = array();
        foreach ($tempcontenttypes as $temp) {
            if (!isset($temp['slug'])) {
                $temp['slug'] = makeSlug($temp['name']);
            }
            if (!isset($temp['singular_slug'])) {
                $temp['singular_slug'] = makeSlug($temp['singular_name']);
            }
            if (!isset($temp['show_on_dashboard'])) {
                $temp['show_on_dashboard'] = true;
            }
            if (!isset($temp['sort'])) {
                $temp['sort'] = "id";
            }
            // Make sure all fields are lowercase and 'safe'.
            $tempfields = $temp['fields'];
            $temp['fields'] = array();
            foreach($tempfields as $key => $value) {
                $key = str_replace("-", "_", strtolower(safeString($key, true)));
                $temp['fields'][ $key ] = $value;
            }

            if (isset($temp['fields']['slug']) && isset($temp['fields']['slug']['uses']) &&
                !is_array($temp['fields']['slug']['uses'])) {
                $temp['fields']['slug']['uses'] = array($temp['fields']['slug']['uses']);
            }

            // Make sure taxonomy is an array.
            if (isset($temp['taxonomy']) && !is_array($temp['taxonomy'])) {
                $temp['taxonomy'] = array($temp['taxonomy']);
            }

            $config['contenttypes'][ $temp['slug'] ] = $temp;

        }

        // Set all the distinctive arrays as part of our Config object.
        $this->data = $config;

    }



    /**
     * Sanity checks for doubles in in contenttypes.
     *
     */
    function checkConfig() {

        // Check DB-tables integrity
        if ($this->app['storage']->getIntegrityChecker()->needsCheck()) {
            if (count($this->app['storage']->getIntegrityChecker()->checkTablesIntegrity())>0) {
                $msg = __("The database needs to be updated / repaired. Go to 'Settings' > 'Check Database' to do this now.");
                $this->app['session']->getFlashBag()->set('error', $msg);
                return;
            }
        }

        $slugs = array();

        foreach ($this->data['contenttypes'] as $key => $ct) {

            // Make sure any field that has a 'uses' parameter actually points to a field that exists.
            // For example, this will show a notice:
            // entries:
            //   name: Entries
            //     singular_name: Entry
            //     fields:
            //       title:
            //         type: text
            //         class: large
            //       slug:
            //         type: slug
            //         uses: name
            //
            foreach($ct['fields'] as $fieldname => $field) {

                // Check 'uses'. If it's an array, split it up, and check the separate parts. We also need to check
                // for the fields that are always present, like 'id'.
                if (is_array($field) && !empty($field['uses']) ) {
                    foreach($field['uses'] as $useField) {
                        if (!empty($field['uses']) && empty($ct['fields'][ $useField ]) && !in_array($useField, $this->reservedfieldnames) ) {
                            $error =  __("In the contenttype for '%contenttype%', the field '%field%' has 'uses: %uses%', but the field '%uses%' does not exist. Please edit contenttypes.yml, and correct this.",
                                array( '%contenttype%' => $key, '%field%' => $fieldname, '%uses%' => $useField )
                            );
                            $this->app['session']->getFlashBag()->set('error', $error);
                        }
                    }
                }

                // Make sure we have a 'label', 'class', 'variant' and 'default'.
                if (!isset($field['label'])) {
                    $this->set("contenttypes/$key/fields/$fieldname/label", "");
                }
                if (!isset($field['class'])) {
                    $this->set("contenttypes/$key/fields/$fieldname/class", "");
                }
                if (!isset($field['variant'])) {
                    $this->set("contenttypes/$key/fields/$fieldname/variant", "");
                }
                if (!isset($field['default'])) {
                    $this->set("contenttypes/$key/fields/$fieldname/default", "");
                }
                if (!isset($field['pattern'])) {
                    $this->set("contenttypes/$key/fields/$fieldname/pattern", "");
                }
            }

            // Show some helpful warnings if slugs or names are not set correctly.
            if ($ct['slug'] == $ct['singular_slug']) {
                $error =  __("The slug and singular_slug for '%contenttype%' are the same (%slug%). Please edit contenttypes.yml, and make them distinct.",
                    array( '%contenttype%' => $key, '%slug%' => $ct['slug'] )
                );
                $this->app['session']->getFlashBag()->set('error', $error);
            }

            if ($ct['name'] == $ct['singular_name']) {
                $error =  __("The name and singular_name for '%contenttype%' are the same (%name%). Please edit contenttypes.yml, and make them distinct.",
                    array( '%contenttype%' => $key, '%name%' => $ct['name'] )
                );
                $this->app['session']->getFlashBag()->set('error', $error);
            }

            // Keep a running score of used slugs..
            if (!isset($slugs[ $ct['slug'] ])) { $slugs[ $ct['slug'] ] = 0; }
            $slugs[ $ct['slug'] ]++;
            if (!isset($slugs[ $ct['singular_slug'] ])) { $slugs[ $ct['singular_slug'] ] = 0; }
            $slugs[ $ct['singular_slug'] ]++;

        }

        // Sanity checks for taxomy.yml
        foreach ($this->data['taxonomy'] as $key => $taxo) {

            // Show some helpful warnings if slugs or keys are not set correctly.
            if ($taxo['slug'] != $key) {
                $error =  __("The identifier and slug for '%taxonomytype%' are the not the same ('%slug%' vs. '%taxonomytype%'). Please edit taxonomy.yml, and make them match to prevent inconsistencies between database storage and your templates.",
                    array( '%taxonomytype%' => $key, '%slug%' => $taxo['slug'] )
                );
                $this->app['session']->getFlashBag()->set('error', $error);
            }

        }

        // if there aren't any other errors, check for duplicates across contenttypes..
        if (!$this->app['session']->getFlashBag()->has('error')) {
            foreach ($slugs as $slug => $count) {
                if ($count > 1) {
                    $error =  __("The slug '%slug%' is used in more than one contenttype. Please edit contenttypes.yml, and make them distinct.",
                        array( '%slug%' => $slug )
                    );
                    $this->app['session']->getFlashBag()->set('error', $error);
                }
            }
        }


    }



    /**
     * Assume sensible defaults for a number of options.
     */
    private function setDefaults()
    {

        $this->defaultconfig = array(
            'database' => array('prefix' => 'bolt_'),
            'sitename' => 'Default Bolt site',
            'homepage' => 'page/*',
            'homepage_template' => 'index.twig',
            'locale' => 'en_GB',
            'recordsperpage' => 10,
            'recordsperdashboardwidget' => 5,
            'debug' => false,
            'debug_show_loggedoff' => false,
            'debug_error_level' => 6135, // equivalent to E_ALL &~ E_NOTICE &~ E_DEPRECATED &~ E_USER_DEPRECATED
            'strict_variables' => false,
            'theme' => "default",
            'debug_compressjs' => true,
            'debug_compresscss' => true,
            'listing_template' => 'listing.twig',
            'listing_records' => '5',
            'listing_sort' => 'datepublish DESC',
            'wysiwyg' => array(
                'images' => true,
                'tables' => false,
                'embed' => false,
                'fontcolor' => false,
                'align' => false,
                'subsuper' => false,
                'embed' => true,
                'anchor' => false,
                'ck' => array(
                    'allowedContent' => true,
                    'autoParagraph' => true,
                    'contentsCss' => array(
                        $this->paths['app'] . 'view/lib/ckeditor/contents.css',
                        $this->paths['app'] . 'view/css/ckeditor.css',
                    ),
                    'filebrowserWindowWidth' => 640,
                    'filebrowserWindowHeight' => 480
                ),
                'filebrowser' => array(
                    'browseUrl' => $this->paths['async'] . "filebrowser/",
                    'imageBrowseUrl' => $this->paths['bolt'] . "files/files"
                ),
            ),
            'canonical' => !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "",
            'developer_notices' => false,
            'cookies_use_remoteaddr' => true,
            'cookies_use_browseragent' => false,
            'cookies_use_httphost' => true,
            'cookies_https_only' => false,
            'cookies_lifetime' => 14*24*3600,
            'thumbnails' => array(
                'default_thumbnail' => array(160, 120),
                'default_image' => array(1000, 750),
                'quality' => 75,
                'cropping' => 'crop',
                'notfound_image' => 'view/img/default_notfound.png',
                'error_image' => 'view/img/default_error.png'
            ),
            'hash_strength' => 10,
            'branding' => array(
                'name' => "Bolt",
                'path' => "/bolt",
                'provided_by' => array()
            ),
            'maintenance_mode' => false
        );


    }

    private function setTwigPath() {

        // I don't think we can set Twig's path in runtime, so we have to resort to hackishness to set the path..
        $themepath = realpath(__DIR__.'/../../../theme/'. basename($this->get('general/theme')));

        $end = $this->getWhichEnd($this->get('general/branding/path'));

        if ( $end == "frontend" && file_exists($themepath) ) {
            $twigpath = array($themepath);
        } else {
            $twigpath = array(realpath(__DIR__.'/../../view'));
        }

        // If the template path doesn't exist, attempt to set a Flash error on the dashboard.
        if (!file_exists($themepath) && (gettype($this->app['session']) == "object") ) {
            $error = "Template folder 'theme/" . basename($this->get('general/theme')) . "' does not exist, or is not writable.";
            $this->app['session']->getFlashBag()->set('error', $error);
        }

        // We add these later, because the order is important: By having theme/ourtheme first,
        // files in that folder will take precedence. For instance when overriding the menu template.
        $twigpath[] = realpath(__DIR__.'/../../theme_defaults');

        $this->data['twigpath'] = $twigpath;

    }


    private function loadCache()
    {
        /* Get the timestamps for the config files. config_local defaults to '0', because if it isn't present,
           it shouldn't trigger an update for the cache, while the others should.
        */
        $timestamps = array(
            file_exists(BOLT_CONFIG_DIR.'/config.yml') ? filemtime(BOLT_CONFIG_DIR.'/config.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR.'/taxonomy.yml') ? filemtime(BOLT_CONFIG_DIR.'/taxonomy.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR.'/contenttypes.yml') ? filemtime(BOLT_CONFIG_DIR.'/contenttypes.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR.'/menu.yml') ? filemtime(BOLT_CONFIG_DIR.'/menu.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR.'/routing.yml') ? filemtime(BOLT_CONFIG_DIR.'/routing.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR.'/config_local.yml') ? filemtime(BOLT_CONFIG_DIR.'/config_local.yml') : 0,
        );
        $cachetimestamp = file_exists(__DIR__ . "/../../cache/config_cache.php") ? filemtime(__DIR__ . "/../../cache/config_cache.php") : 0;

        //\util::var_dump($timestamps);
        //\util::var_dump($cachetimestamp);

        if ($cachetimestamp > max($timestamps)) {

            $this->data = loadSerialize(__DIR__ . "/../../cache/config_cache.php");

            // Check if we loaded actual data.
            if (count($this->data)>3 && !empty($this->data['general'])) {
                return true;
            }

        }

        return false;


    }

    private function saveCache()
    {

        saveSerialize(__DIR__ . "/../../cache/config_cache.php", $this->data);

    }


    /**
     * Get an associative array with the correct options for the chosen database type.
     *
     * @return array
     */

    function getDBOptions()
    {
        $configdb = $this->data['general']['database'];

        if (isset($configdb['driver']) && ( $configdb['driver'] == "pdo_sqlite" || $configdb['driver'] == "sqlite" ) ) {

            $basename = isset($configdb['databasename']) ? basename($configdb['databasename']) : "bolt";
            if (getExtension($basename)!="db") {
                $basename .= ".db";
            }

            $dboptions = array(
                'driver' => 'pdo_sqlite',
                'path' => __DIR__ . "/../../database/" . $basename,
                'randomfunction' => "RANDOM()"
            );

        } else {
            // Assume we configured it correctly. Yeehaa!

            if (empty($configdb['password'])) {
                $configdb['password'] = "";
            }

            $driver = (isset($configdb['driver']) ? $configdb['driver'] : 'pdo_mysql');
            if ($driver == "mysql" || $driver == "mysqli") {
                $driver = 'pdo_mysql';
                $randomfunction = "RAND()";
            }
            if ($driver == "postgres" || $driver == "postgresql") {
                $driver = 'pdo_pgsql';
                $randomfunction = "RANDOM()";
            }

            $dboptions = array(
                'driver'    => $driver,
                'host'      => (isset($configdb['host']) ? $configdb['host'] : 'localhost'),
                'dbname'    => $configdb['databasename'],
                'user'      => $configdb['username'],
                'password'  => $configdb['password'],
                'randomfunction' => $randomfunction
            );
            if (!isset($configdb['charset'])) {
                $dboptions['charset'] = 'utf8';
            } else {
                $dboptions['charset'] = $configdb['charset'];
            }

        }

        switch($dboptions['driver']) {
            case 'pdo_mysql':
                $dboptions['port'] = isset($configdb['port']) ? $configdb['port'] : '3306';
                $dboptions['reservedwords'] = explode(',', "accessible,add,all,alter,analyze,and,as,asc,asensitive,before,between," .
                    "bigint,binary,blob,both,by,call,cascade,case,change,char,character,check,collate,column,condition,constraint," .
                    "continue,convert,create,cross,current_date,current_time,current_timestamp,current_user,cursor,database,databases," .
                    "day_hour,day_microsecond,day_minute,day_second,dec,decimal,declare,default,delayed,delete,desc,describe," .
                    "deterministic,distinct,distinctrow,div,double,drop,dual,each,else,elseif,enclosed,escaped,exists,exit,explain," .
                    "false,fetch,float,float4,float8,for,force,foreign,from,fulltext,get,grant,group,having,high_priority,hour_microsecond," .
                    "hour_minute,hour_second,if,ignore,in,index,infile,inner,inout,insensitive,insert,int,int1,int2,int3,int4,int8," .
                    "integer,interval,into,io_after_gtids,io_before_gtids,is,iterate,join,key,keys,kill,leading,leave,left,like,limit," .
                    "linear,lines,load,localtime,localtimestamp,lock,long,longblob,longtext,loop,low_priority,master_bind," .
                    "master_ssl_verify_server_cert,match,maxvalue,mediumblob,mediumint,mediumtext,middleint,minute_microsecond," .
                    "minute_second,mod,modifies,natural,nonblocking,not,no_write_to_binlog,null,numeric,on,optimize,option,optionally," .
                    "or,order,out,outer,outfile,partition,precision,primary,procedure,purge,range,read,reads,read_write,real,references," .
                    "regexp,release,rename,repeat,replace,require,resignal,restrict,return,revoke,right,rlike,schema,schemas," .
                    "second_microsecond,select,sensitive,separator,set,show,signal,smallint,spatial,specific,sql,sqlexception,sqlstate," .
                    "sqlwarning,sql_big_result,sql_calc_found_rows,sql_small_result,ssl,starting,straight_join,table,terminated,then," .
                    "tinyblob,tinyint,tinytext,to,trailing,trigger,true,undo,union,unique,unlock,unsigned,update,usage,use,using,utc_date," .
                    "utc_time,utc_timestamp,values,varbinary,varchar,varcharacter,varying,when,where,while,with,write,xor,year_month," .
                    "zerofill,nonblocking");
                break;
            case 'pdo_sqlite':
                $dboptions['reservedwords'] = explode(',', "abort,action,add,after,all,alter,analyze,and,as,asc,attach,autoincrement," .
                    "before,begin,between,by,cascade,case,cast,check,collate,column,commit,conflict,constraint,create,cross,current_date," .
                    "current_time,current_timestamp,database,default,deferrable,deferred,delete,desc,detach,distinct,drop,each,else,end," .
                    "escape,except,exclusive,exists,explain,fail,for,foreign,from,full,glob,group,having,if,ignore,immediate,in,index," .
                    "indexed,initially,inner,insert,instead,intersect,into,is,isnull,join,key,left,like,limit,match,natural,no,not," .
                    "notnull,null,of,offset,on,or,order,outer,plan,pragma,primary,query,raise,references,regexp,reindex,release,rename," .
                    "replace,restrict,right,rollback");
                break;
            case 'pdo_pgsql':
                $dboptions['port'] = isset($configdb['port']) ? $configdb['port'] : '5432';
                $dboptions['reservedwords'] = explode(',', "all,analyse,analyze,and,any,as,asc,authorization,between,bigint,binary,bit," .
                    "boolean,both,case,cast,char,character,check,coalesce,collate,column,constraint,convert,create,cross,current_date," .
                    "current_time,current_timestamp,current_user,dec,decimal,default,deferrable,desc,distinct,do,else,end,except,exists," .
                    "extract,float,for,foreign,freeze,from,full,grant,group,having,ilike,in,initially,inner,int,integer,intersect,interval," .
                    "into,is,isnull,join,leading,left,like,limit,localtime,localtimestamp,natural,nchar,new,none,not,notnull,null,nullif," .
                    "numeric,off,offset,old,on,only,or,order,outer,overlaps,overlay,placing,position,primary,real,references,right,row," .
                    "select,session_user,setof,similar,smallint,some,substring,table,then,time,timestamp,to,trailing,treat,trim,union," .
                    "unique,user,using,varchar,verbose,when,where,false,true");
        }

        return $dboptions;

    }


    /**
     * Utility function to determine which 'end' we're using right now. Can be either "frontend", "backend", "async" or "cli".
     *
     * @param string $mountpoint
     * @return string
     */
    function getWhichEnd($mountpoint = "")
    {

        if (empty($mountpoint)) {
            $mountpoint = $this->get('general/branding/path');
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            // Get the script's filename, but _without_ REQUEST_URI. We need to str_replace the slashes, because of a
            // weird quirk in dirname on windows: http://nl1.php.net/dirname#refsect1-function.dirname-notes
            $scriptdirname = "#" . str_replace("\\", "/", dirname($_SERVER['SCRIPT_NAME']));
            $scripturi = str_replace($scriptdirname, '', "#".$_SERVER['REQUEST_URI']);
            // make sure it starts with '/', like our mountpoint.
            if (empty($scripturi) || ($scripturi[0] != "/") ) {
                $scripturi = "/" . $scripturi;
            }
        } else {
            // We're probably in CLI mode.
            $this->app['end'] = "cli";
            return "cli";
        }

        // If the request URI starts with '/bolt' or '/async' in the URL, we assume we're in the backend or in async.
        if ( (substr($scripturi, 0, strlen($mountpoint)) == $mountpoint) ) {
            $end = 'backend';
        } else if ( (substr($scripturi, 0, 6) == "async/") || (strpos($scripturi, "/async/") !== false) ) {
            $end = 'async';
        } else {
            $end = 'frontend';
        }

        $this->app['end'] = $end;

        return $end;

    }

}
