<?php

namespace Bolt;

use Silex;
use Bolt;
use util;

class Extensions {
  
    var $db;
    var $config;
    var $basefolder;
    var $enabled;

    function __construct(Silex\Application $app) {
    
        $this->app = $app;
        $this->basefolder = realpath(__DIR__."/../../extensions/");
        $this->enabled = $this->app['config']['general']['enabled_extensions'];

    }


    /**
     * Get an array of information about each of the present extensions, and
     * whether they're enabled or not.
     *
     * @return array
     */
    function getInfo() {


        $d = dir($this->basefolder);

        $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");

        while (false !== ($entry = $d->read())) {

            if (in_array($entry, $ignored) || substr($entry, 0, 2) == "._" ) { continue; }

            if (is_dir($this->basefolder."/".$entry)) {
                $info[] = $this->infoHelper($this->basefolder."/".$entry);
            }


        }

        $d->close();

        return $info;

    }


    private function infoHelper($path) {

        $filename = $path."/extension.php";
        $namespace = basename($path);

        if (is_readable($filename)) {
            include_once($filename);
            if (function_exists($namespace.'\info')) {
                $info = call_user_func($namespace.'\info');

                $info['enabled'] = in_array($namespace, $this->enabled);

                if (file_exists($path."/readme.md")) {
                    $info['readme'] = $namespace."/readme.md";
                }

                if (file_exists($path."/config.yml")) {
                    $info['config'] = $namespace."/config.yml";
                }

                $info['version_ok'] = checkVersion($GLOBALS['bolt_version'], $info['required_bolt_version']);

                $info['namespace'] = $namespace;

                return $info;

            } else {
                $this->log->add("Couldn't initialize $namespace: function 'init()' doesn't exist", 3);
                return false;
            }
        }



    }

    /**
     * Initialize the enabled extensions.
     *
     */
    function initialize() {

        foreach($this->enabled as $extension) {
            $filename = $this->basefolder . "/" . $extension . "/extension.php";

            if (is_readable($filename)) {
                include_once($filename);
                if (function_exists($extension.'\init')) {
                    call_user_func($extension.'\init', $this->app);
                } else {
                    $this->log->add("Couldn't initialize $extension: function 'init()' doesn't exist", 3);
                }
            } else {
                $this->log->add("Couldn't initialize $extension: file '$filename' not readable", 3);
            }

        }

    }

    
  
}