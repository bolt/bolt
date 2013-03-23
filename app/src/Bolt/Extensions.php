<?php

namespace Bolt;

use Bolt;
use util;
use Bolt\Extensions\Snippets\Location as SnippetLocation;

class Extensions
{
    /**
     * @var \Bolt\Application
     */
    private $app;

    /**
     * The extension base folder.
     *
     * @var string
     */
    private $basefolder;

    /**
     * List of enabled extensions.
     *
     * @var array
     */
    private $enabled = array();

    /**
     * Queue with snippets of HTML to insert.
     *
     * @var array
     */
    private $snippetqueue;

    /**
     * Queue with widgets to insert.
     *
     * @var array
     */
    private $widgetqueue;

    /**
     * Files which may be in the extensions folder, but have to be ignored.
     *
     * @var array
     */
    private $ignored;

    /**
     * Whether or not to add jQuery.
     *
     * @var bool
     */
    private $addjquery;

    /**
     * List of comments in snippets, these must not be replaced, so they are
     * stored here while the rest of the snippet is processed.
     *
     * @var array
     */
    private $matchedcomments;

    /**
     * Contains all initialized extensions.
     *
     * @var array
     */
    private $initialized;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->basefolder = realpath(__DIR__ . "/../../extensions/");
        $this->ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");
        $this->enabledExtensions();
        $this->matchedcomments = array();

        if (isset($app['config']['general']['add_jquery']) && $app['config']['general']['add_jquery'] == true) {
            $this->addjquery = true;
        } else {
            $this->addjquery = false;
        }

    }

    /**
     * Defines the extensions which are enabled through the configuration and
     * are actually present in the extensions folder.
     */
    public function enabledExtensions()
    {
        $list = $this->app['config']['general']['enabled_extensions'];
        $folders = array();

        $d = dir($this->basefolder);

        // Make a list of extensions, actually present..
        while (false !== ($foldername = $d->read())) {

            if (in_array($foldername, $this->ignored) || substr($foldername, 0, 2) == "._") {
                continue;
            }

            if (is_dir($this->basefolder . "/" . $foldername) && is_readable($this->basefolder . "/" . $foldername . "/extension.php")) {
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

            if (in_array($entry, $this->ignored) || substr($entry, 0, 2) == "._") {
                continue;
            }

            if (is_dir($this->basefolder . "/" . $entry)) {
                $info[$entry] = $this->infoHelper($this->basefolder . "/" . $entry);
            }

        }
        $d->close();

        ksort($info);

        return $info;

    }

    /**
     * Get the 'information' for an extension, whether it's active or not.
     *
     * @param $path
     * @return array
     */
    private function infoHelper($path)
    {
        $filename = $path . "/extension.php";
        $namespace = basename($path);

        if (!is_readable($filename)) {
            // No extension.php in the folder, skip it!
            $this->app['log']->add("Couldn't initialize $namespace: 'extension.php' doesn't exist", 3);
            return array();
        }

        include_once($filename);

        if (!class_exists($namespace . '\Extension')) {
            // No class Extensionname\Extension, skip it!
            $this->app['log']->add("Couldn't initialize $namespace: Class '$namespace\\Extension' doesn't exist", 3);
            return array();
        }

        $classname = '\\' . $namespace . '\\Extension';
        /**
         * @var \Bolt\BaseExtension $extension
         */
        $extension = new $classname($this->app);

        $info = $extension->getInfo();

        $info['enabled'] = $this->isEnabled($namespace);

        // \util::var_dump($info);

        return $info;

    }


    /**
     * Check if an extension is enabled, case sensitive.
     *
     * @param  string $name
     * @return bool
     */
    public function isEnabled($name)
    {
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

                $classname = '\\' . $extension . '\\Extension';

                if (!class_exists($classname)) {
                    $this->app['log']->add("Couldn't initialize $extension: Class '$classname' doesn't exist", 3);
                    return;
                }

                $this->initialized[$extension] = new $classname($this->app);

                if ($this->initialized[$extension] instanceof \Bolt\BaseExtensionInterface) {

                    $this->initialized[$extension]->getConfig();
                    $this->initialized[$extension]->initialize();

                    // Check if (instead, or on top of) initialize, the extension has a 'getSnippets' method
                    $this->getSnippets($extension);

                    if ($this->initialized[$extension] instanceof \Twig_Extension) {
                        $this->app['twig']->addExtension($this->initialized[$extension]);
                    }
                }
            }


            /*
                if (function_exists($extension.'\init')) {
                    call_user_func($extension.'\init', $this->app);
                } else {
                    $this->app['log']->add("Couldn't initialize $extension: function 'init()' doesn't exist", 3);
                }
            } else {
                $this->app['log']->add("Couldn't initialize $extension: file '$filename' not readable", 3);
            }
            */

        }

    }

    /**
     * Add jQuery to the output.
     */
    public function addJquery()
    {
        $this->addjquery = true;
    }

    /**
     * Don't add jQuery to the output.
     */
    public function disableJquery()
    {
        $this->addjquery = false;
    }

    /**
     * Add a particular CSS file to the output. This will be inserted before the
     * other css files.
     *
     * @param string $filename
     */
    public function addCss($filename)
    {

        $html = sprintf('<link rel="stylesheet" href="%s" media="screen">', $filename);

        $this->insertSnippet(SnippetLocation::BEFORE_CSS, $html);

    }

    /**
     * Add a particular javascript file to the output. This will be inserted after
     * the other javascript files.
     * @param string $filename
     */
    public function addJavascript($filename)
    {

        $html = sprintf('<script src="%s"></script>', $filename);

        $this->insertSnippet(SnippetLocation::AFTER_JS, $html);

    }

    /**
     * Insert a widget. And by 'insert' we actually mean 'add it to the queue,
     * to be processed later'.
     */
    public function insertWidget($type, $location, $callback, $extensionname, $additionalhtml = "", $defer = true, $cacheduration = 180, $extraparameters = "")
    {

        $user = $this->app['session']->get('user');

        $sessionkey = !empty($user['sessionkey']) ? $user['sessionkey'] : "";

        $key = substr(md5(sprintf("%s%s%s%s", $sessionkey, $type, $location, $callback)), 0, 8);

        $this->widgetqueue[] = array(
            'type' => $type,
            'location' => $location,
            'callback' => $callback,
            'additionalhtml' => $additionalhtml,
            'cacheduration' => $cacheduration,
            'extension' => $extensionname,
            'defer' => $defer,
            'extraparameters' => $extraparameters,
            'key' => $key
        );

    }

    /**
     * Renders a div as a placeholder for a particular type of widget on the
     * given location.
     *
     * @param string $type
     * @param string $location For convenience, use the constant from Bolt\Extensions\Snippets\Location
     */
    public function renderWidgetHolder($type, $location)
    {
        if (is_array($this->widgetqueue)) {
            foreach ($this->widgetqueue as $widget) {
                if ($type == $widget['type'] && $location == $widget['location']) {

                    $html = sprintf("<section><div class='widget' id='widget-%s' data-key='%s'></div></section>", $widget['key'], $widget['key']);

                    if (!empty($widget['additionalhtml'])) {
                        $html .= "\n" . $widget['additionalhtml'];
                    }

                    echo $html;
                }
            }
        }
    }

    /**
     * Renders the widget identified by the given key.
     *
     * @param string $key Widget identifier
     * @return string HTML
     */
    public function renderWidget($key)
    {

        foreach ($this->widgetqueue as $widget) {
            if ($key == $widget['key']) {

                $cachekey = 'widget_' . $widget['key'];

                if ($this->app['cache']->contains($cachekey)) {
                    // Present in the cache ..
                    $html = $this->app['cache']->fetch($cachekey);
                } else if (method_exists($this->initialized[$widget['extension']], $widget['callback'])) {
                    // Widget is defined in the extension itself.
                    $html = $this->initialized[$widget['extension']]->parseWidget($widget['callback'], $widget['extraparameters']);
                    $this->app['cache']->save($cachekey, $html, $widget['cacheduration']);
                } else if (function_exists($widget['callback'])) {
                    // Widget is a callback in the 'global scope'
                    $html = call_user_func($widget['callback'], $this->app, $widget['extraparameters']);
                    $this->app['cache']->save($cachekey, $html, $widget['cacheduration']);
                } else {
                    // Insert the 'callback' as string.
                    $html = $widget['callback'];
                }

                return $html;

            }
        }

        return "Invalid key '$key'. No widget found.";
    }

    /**
     * Call the 'getSnippets' function of an initialized extension, and make sure the snippets are initialized
     */
    public function getSnippets($extensionname)
    {

        $snippets = $this->initialized[$extensionname]->getSnippets();

        if (!empty($snippets)) {
            foreach ($snippets as $snippet) {
                // Make sure 'snippet[2]' is the correct name.
                $snippet[2] = $extensionname;
                if (!isset($snippet[3])) {
                    $snippet[3] = "";
                }
                $this->insertSnippet($snippet[0], $snippet[1], $snippet[2], $snippet[3]);
            }
        }

    }

    /**
     * Insert a snippet. And by 'insert' we actually mean 'add it to the queue, to be processed later'.
     */
    public function insertSnippet($location, $callback, $extensionname = "core", $extraparameters = "")
    {

        $key = md5($extensionname . $callback . $location);

        // http://php.net/manual/en/function.func-get-args.php

        $this->snippetqueue[$key] = array(
            'location' => $location,
            'callback' => $callback,
            'extension' => $extensionname,
            'extraparameters' => $extraparameters
        );


    }

    /**
     * Clears the snippet queue
     */
    public function clearSnippetQueue()
    {
        $this->snippetqueue = array();
    }

    public function processSnippetQueue($html)
    {

        // First, gather all html <!-- comments -->, because they shouldn't be
        // considered for replacements. We use a callback, so we can fill our
        // $this->matchedcomments array
        $html = preg_replace_callback('/<!--(.*)-->/Uis', array($this, 'pregcallback'), $html);

        // Replace the snippets in the queue..
        foreach ($this->snippetqueue as $item) {

            // Get the snippet, either by using a callback function, or else use the
            // passed string as-is..

            if (($item['extension'] != "core") && method_exists($this->initialized[$item['extension']], $item['callback'])) {
                // Snippet is defined in the extension itself.
                $snippet = $this->initialized[$item['extension']]->parseSnippet($item['callback'], $item['extraparameters']);
            } else if (function_exists($item['callback'])) {
                // Snippet is a callback in the 'global scope'
                $snippet = call_user_func($item['callback'], $this->app, $item['extraparameters']);
            } else {
                // Insert the 'callback' as a string..
                $snippet = $item['callback'];
            }

            // then insert it into the HTML, somewhere.
            switch ($item['location']) {
                case SnippetLocation::END_OF_HEAD:
                    $html = $this->insertEndOfHead($snippet, $html);
                    break;
                case SnippetLocation::AFTER_META:
                    $html = $this->insertAfterMeta($snippet, $html);
                    break;
                case SnippetLocation::BEFORE_CSS:
                    $html = $this->insertBeforeCss($snippet, $html);
                    break;
                case SnippetLocation::AFTER_CSS:
                    $html = $this->insertAfterCss($snippet, $html);
                    break;
                case SnippetLocation::BEFORE_JS:
                    $html = $this->insertBeforeJs($snippet, $html);
                    break;
                case SnippetLocation::AFTER_JS:
                    $html = $this->insertAfterJs($snippet, $html);
                    break;
                case SnippetLocation::START_OF_HEAD:
                    $html = $this->insertStartOfHead($snippet, $html);
                    break;
                case SnippetLocation::START_OF_BODY:
                    $html = $this->insertStartOfBody($snippet, $html);
                    break;
                case SnippetLocation::END_OF_BODY:
                    $html = $this->insertEndOfBody($snippet, $html);
                    break;
                case SnippetLocation::END_OF_HTML:
                    $html = $this->insertEndOfHtml($snippet, $html);
                    break;
                default:
                    $html .= $snippet . "\n";
                    break;
            }

        }

        if ($this->addjquery == true) {
            $html = $this->insertJquery($html);
        }

        // Finally, replace back ###comment### with its original comment.
        if (!empty($this->matchedcomments)) {
            $html = preg_replace(array_keys($this->matchedcomments), $this->matchedcomments, $html, 1);
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

        if (preg_match("~^([ \t]*)<head(.*)~mi", $html, $matches)) {

            // Try to insert it after <head>
            $replacement = sprintf("%s\n%s\t%s", $matches[0], $matches[1], $tag);
            $html = str_replace_first($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";

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

        if (preg_match("~^([ \t]*)<body(.*)~mi", $html, $matches)) {

            // Try to insert it after <body>
            $replacement = sprintf("%s\n%s\t%s", $matches[0], $matches[1], $tag);
            $html = str_replace_first($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";

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

        if (preg_match("~^([ \t]*)</head~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = str_replace_first($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";

        }

        return $html;

    }

    /**
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

        if (preg_match("~^([ \t]*)</body~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = str_replace_first($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";

        }

        return $html;

    }


    /**
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

        if (preg_match("~^([ \t]*)</html~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = str_replace_first($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";

        }

        return $html;

    }


    /**
     * Helper function to insert some HTML into the head section of an HTML page.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertAfterMeta($tag, $html)
    {

        // first, attempt to insert it after the last meta tag, matching indentation..

        if (preg_match_all("~^([ \t]*)<meta (.*)~mi", $html, $matches)) {
            //echo "<pre>\n" . util::var_dump($matches, true) . "</pre>\n";

            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = str_replace_first($matches[0][$last], $replacement, $html);

        } else {
            $html = $this->insertEndOfHead($tag, $html);
        }

        return $html;

    }


    /**
     * Helper function to insert some HTML into the head section of an HTML page.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertAfterCss($tag, $html)
    {

        // first, attempt to insert it after the last <link> tag, matching indentation..

        if (preg_match_all("~^([ \t]*)<link (.*)~mi", $html, $matches)) {
            //echo "<pre>\n" . util::var_dump($matches, true) . "</pre>\n";

            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = str_replace_first($matches[0][$last], $replacement, $html);

        } else {
            $html = $this->insertEndOfHead($tag, $html);
        }

        return $html;

    }


    /**
     * Helper function to insert some HTML before the first CSS include in the page.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertBeforeCss($tag, $html)
    {

        // first, attempt to insert it after the <body> tag, matching indentation..

        if (preg_match("~^([ \t]*)<link(.*)~mi", $html, $matches)) {

            // Try to insert it before the match
            $replacement = sprintf("%s%s\n%s\t%s", $matches[1], $tag, $matches[0], $matches[1]);
            $html = str_replace_first($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";

        }

        return $html;

    }


    /**
     * Helper function to insert some HTML before the first javascript include in the page.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertBeforeJS($tag, $html)
    {

        // first, attempt to insert it after the <body> tag, matching indentation..

        if (preg_match("~^([ \t]*)<script(.*)~mi", $html, $matches)) {

            // Try to insert it before the match
            $replacement = sprintf("%s%s\n%s\t%s", $matches[1], $tag, $matches[0], $matches[1]);
            $html = str_replace_first($matches[0], $replacement, $html);

        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";

        }

        return $html;

    }

    /**
     * Helper function to insert some HTML after the last javascript include.
     * First in the head section, but if there is no script in the head, place
     * it anywhere.
     *
     * @param  string $tag
     * @param  string $html
     * @return string
     */
    public function insertAfterJs($tag, $html, $insidehead = true)
    {

        // Set $context: only the part until </head>, or entire document.
        if ($insidehead) {
            $pos = strpos($html, "</head>");
            $context = substr($html, 0, $pos);
        } else {
            $context = $html;
        }

        // then, attempt to insert it after the last <script> tag within context, matching indentation..
        if (preg_match_all("~^([ \t]*)<script (.*)~mi", $context, $matches)) {
            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = str_replace_first($matches[0][$last], $replacement, $html);

        } else if ($insidehead) {
            // Second attempt: entire document
            $html = $this->insertAfterJs($tag, $html, false);
        } else {
            // Just insert it at the end of the head section.
            $html = $this->insertEndOfHead($tag, $html);
        }

        return $html;

    }

    /**
     * Insert jQuery, if it's not inserted already.
     *
     * @param string $html
     * @return string HTML
     */
    private function insertJquery($html)
    {

        // check if jquery is not yet present. Some of the patterns that 'match' are:
        // jquery.js
        // jquery.min.js
        // jquery-latest.js
        // jquery-latest.min.js
        // jquery-1.8.2.min.js
        // jquery-1.5.js
        if (!preg_match('/<script(.*)jquery(-latest|-[0-9\.]*)?(\.min)?\.js/', $html)) {
            $jqueryfile = $this->app['paths']['app'] . "view/js/jquery-1.9.1.min.js";
            $html = $this->insertBeforeJs("<script src='$jqueryfile'></script>", $html);
            return $html;
        } else {
            // We've already got jQuery. Yay, us!
            return $html;
        }
    }

    /**
     * Callback method to identify comments and store them in the matchedcomments
     * array. These will be put back after the replacements on the HTML are
     * finished.
     *
     * @param string $c
     * @return string The key under which the comment is stored
     */
    private function pregcallback($c)
    {
        $key = "###bolt-comment-" . count($this->matchedcomments) . "###";
        // Add it to the array of matched comments..
        $this->matchedcomments["/" . $key . "/"] = $c[0];
        return $key;

    }

}
