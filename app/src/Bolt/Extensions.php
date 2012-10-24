<?php

namespace Bolt;

use Silex;
use Bolt;
use util;

class Extensions
{
    public $db;
    public $config;
    public $basefolder;
    public $enabled;
    public $snippetqueue;
    public $ignored;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->basefolder = realpath(__DIR__."/../../extensions/");
        $this->ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");
        $this->enabledExtensions();

    }

    public function enabledExtensions()
    {
        $list = $this->app['config']['general']['enabled_extensions'];
        $folders = array();

        $d = dir($this->basefolder);

        // Make a list of extensions, actually present..
        while (false !== ($foldername = $d->read())) {

            if (in_array($foldername, $this->ignored) || substr($foldername, 0, 2) == "._" ) {
                continue;
            }

            if (is_dir($this->basefolder."/".$foldername) && is_readable($this->basefolder."/".$foldername."/extension.php")) {
                $folders[] = $foldername;
            }

        }

        $d->close();

        $this->enabled = array_intersect($list, $folders);

    }

    /**
     * Get an array of information about each of the present extensions, and
     * whether they're enabled or not.
     *
     * @return array
     */
    public function getInfo()
    {

        $d = dir($this->basefolder);

        $info = array();

        while (false !== ($entry = $d->read())) {

            if (in_array($entry, $this->ignored) || substr($entry, 0, 2) == "._" ) {
                continue;
            }

            if (is_dir($this->basefolder."/".$entry)) {
                $info[] = $this->infoHelper($this->basefolder."/".$entry);
            }


        }

        $d->close();

        return $info;

    }


    private function infoHelper($path)
    {
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

                if (!isset($info['dependancies'])) {
                    $info['dependancies'] = array();
                }

                if (!isset($info['tags'])) {
                    $info['tags'] = array();
                }

                if (!isset($info['priority'])) {
                    $info['priority'] = 10;
                }

                return $info;

            } else {
                $this->app['log']->add("Couldn't initialize $namespace: function 'init()' doesn't exist", 3);

                return false;
            }
        }



    }


    /**
     * Check if an extension is enabled, case sensitive.
     *
     * @param  string $name
     * @return bool
     */
    public function isEnabled($name)
    {
        // echo "<pre>\n" . util::var_dump($this->enabled, true) . "</pre>\n";
        return in_array($name, $this->enabled);


    }


    /**
     * Initialize the enabled extensions.
     *
     */
    public function initialize()
    {
        foreach ($this->enabled as $extension) {
            $filename = $this->basefolder . "/" . $extension . "/extension.php";

            if (is_readable($filename)) {
                include_once($filename);
                if (function_exists($extension.'\init')) {
                    call_user_func($extension.'\init', $this->app);
                } else {
                    $this->app['log']->add("Couldn't initialize $extension: function 'init()' doesn't exist", 3);
                }
            } else {
                $this->app['log']->add("Couldn't initialize $extension: file '$filename' not readable", 3);
            }

        }

    }


    public function addCss($filename)
    {

        $html = sprintf('<link rel="stylesheet" href="%s" media="screen">', $filename);

        $this->insertSnippet("aftercss", $html);

    }

    public function insertSnippet($location, $callback, $var1 = "", $var2 = "", $var3 = "")
    {
        $this->snippetqueue[] = array(
            'location' => $location,
            'callback' => $callback,
            'var1' => $var1,
            'var2' => $var2,
            'var3' => $var3
        );

    }



    public function processSnippetQueue($html)
    {
        foreach ($this->snippetqueue as $item) {

            // Get the snippet, either by using a callback function, or else use the
            // passed string as-is..
            if (function_exists($item['callback'])) {
                $snippet = call_user_func($item['callback'], $this->app, $item['var1'], $item['var2'], $item['var3']);
            } else {
                $snippet = $item['callback'];
            }

            // then insert it into the HTML, somewhere.
            switch ($item['location']) {
                case "endofhead":
                    $html = $this->insertEndOfHead($snippet, $html);
                    break;
                case "aftermeta":
                    $html = $this->insertAfterMeta($snippet, $html);
                    break;
                case "aftercss":
                    $html = $this->insertAfterCss($snippet, $html);
                    break;
                case "startofhead":
                    $html = $this->insertStartOfHead($snippet, $html);
                    break;
                case "startofbody":
                    $html = $this->insertStartOfBody($snippet, $html);
                    break;
                case "endofbody":
                    $html = $this->insertEndOfBody($snippet, $html);
                    break;
                case "endofhtml":
                    $html = $this->insertEndOfHtml($snippet, $html);
                    break;
                default:
                    $html .= $snippet."\n";
                    break;
            }

        }

        return $html;

    }


    /**
     *
     * Helper function to insert some HTML into thestart of the head section of
     * an HTML page, right after the <head> tag.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertStartOfHead($tag, $html)
    {

        // first, attempt to insert it after the <head> tag, matching indentation..

        if (preg_match("~^([ \t]+)<head(.*)~mi", $html, $matches)) {

            // Try to insert it after <head>
            $replacement = sprintf("%s\n%s\t%s", $matches[0], $matches[1], $tag);
            $html = str_replace($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag."\n";

        }

        return $html;

    }


    /**
     *
     * Helper function to insert some HTML into thestart of the head section of
     * an HTML page, right after the <head> tag.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertStartOfBody($tag, $html)
    {

        // first, attempt to insert it after the <body> tag, matching indentation..

        if (preg_match("~^([ \t]+)<body(.*)~mi", $html, $matches)) {

            // Try to insert it after <body>
            $replacement = sprintf("%s\n%s\t%s", $matches[0], $matches[1], $tag);
            $html = str_replace($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag."\n";

        }

        return $html;

    }



    /**
     *
     * Helper function to insert some HTML into the head section of an HTML
     * page, right before the </head> tag.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertEndOfHead($tag, $html)
    {

        // first, attempt to insert it before the </head> tag, matching indentation..

        if (preg_match("~^([ \t]+)</head~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = str_replace($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag."\n";

        }

        return $html;

    }

    /**
     *
     * Helper function to insert some HTML into the body section of an HTML
     * page, right before the </body> tag.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertEndOfBody($tag, $html)
    {

        // first, attempt to insert it before the </body> tag, matching indentation..

        if (preg_match("~^([ \t]?)</body~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = str_replace($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag."\n";

        }

        return $html;

    }


    /**
     *
     * Helper function to insert some HTML into the html section of an HTML
     * page, right before the </html> tag.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertEndOfHtml($tag, $html)
    {

        // first, attempt to insert it before the </body> tag, matching indentation..

        if (preg_match("~^([ \t]?)</html~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = str_replace($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag."\n";

        }

        return $html;

    }


    /**
     *
     * Helper function to insert some HTML into the head section of an HTML page.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertAfterMeta($tag, $html)
    {

        // first, attempt ot insert it after the last meta tag, matching indentation..

        if (preg_match_all("~^([ \t]+)<meta (.*)~mi", $html, $matches)) {
            //echo "<pre>\n" . util::var_dump($matches, true) . "</pre>\n";

            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0])-1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = str_replace($matches[0][$last], $replacement, $html);

        } else {
            $html = $this->insertEndOfHead($tag, $html);
        }

        return $html;

    }


    /**
     *
     * Helper function to insert some HTML into the head section of an HTML page.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertAfterCss($tag, $html)
    {

        // first, attempt ot insert it after the last <link> tag, matching indentation..

        if (preg_match_all("~^([ \t]+)<link (.*)~mi", $html, $matches)) {
            //echo "<pre>\n" . util::var_dump($matches, true) . "</pre>\n";

            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0])-1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = str_replace($matches[0][$last], $replacement, $html);

        } else {
            $html = $this->insertEndOfHead($tag, $html);
        }

        return $html;

    }

}
