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
    var $snippetqueue;

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

                $info['enabled'] = $this->isEnabled($namespace);

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
     * Check if an extension is enabled, case insensitive.
     *
     * @param string $name
     * @return bool
     */
    function isEnabled($name) {

        $name = strtolower($name);
        $lowernames = array_map('strtolower', $this->enabled);

        return in_array($name, $lowernames);


    }


    /**
     * Initialize the enabled extensions.
     *
     */
    function initialize() {

        foreach($this->enabled as $extension) {
            $filename = $this->basefolder . "/" . $extension . "/extension.php";

            // echo "<p>$filename</p>";

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


    function insertSnippet($location, $callback) {

        $this->snippetqueue[] = array(
            'location' => $location,
            'callback' => $callback,
        );

    }



    function processSnippetQueue($html) {

        // echo "<pre>\n" . util::var_dump($this->snippetqueue, true) . "</pre>\n";

        foreach($this->snippetqueue as $item) {

            // Get the snippet, either by using a callback function, or else use the
            // passed string as-is..
            if (function_exists($item['callback'])) {
                $snippet = call_user_func($item['callback']);
            } else {
                $snippet = $item['callback'];
            }

            //echo "<pre>\n" . util::var_dump($snippet, true) . "</pre>\n";

            // then insert it into the HTML, somewhere.
            switch($item['location']) {
                case "beforeclosehead":
                    $html = $this->insertBeforeCloseHead($snippet, $html);
                    break;

                case "aftermeta":
                    $html = $this->insertAfterMeta($snippet, $html);
                    break;

                default:
                    $html .= $snippet;
                    break;
            }


        }

        return $html;

    }




    /**
     *
     * Helper function to insert some HTML into the head section of an HTML
     * page, right before the </head> tag.
     *
     * @param string $tag
     * @param string $html
     * @return string
     */
    function insertBeforeCloseHead($tag, $html)
    {

        // first, attempt ot insert it after the last meta tag, matching indentation..

        if (preg_match("~^([ \t]+)</head~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = str_replace($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag;

        }

        return $html;

    }


    /**
     *
     * Helper function to insert some HTML into the head section of an HTML page.
     *
     * @param string $tag
     * @param string $html
     * @return string
     */
    function insertAfterMeta($tag, $html)
    {

        // first, attempt ot insert it after the last meta tag, matching indentation..

        if (preg_match_all("~^([ \t]+)<meta (.*)~mi", $html, $matches)) {
            //echo "<pre>\n" . util::var_dump($matches, true) . "</pre>\n";

            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0])-1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = str_replace($matches[0][$last], $replacement, $html);

        } elseif (preg_match("~^([ \t]+)</head~mi", $html, $matches)) {

            //echo "<pre>\n" . util::var_dump($matches, true) . "</pre>\n";
            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = str_replace($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag;

        }

        return $html;

    }

  
}