<?php

namespace Bolt;

use Bolt;
use Bolt\Extensions\Snippets\Location as SnippetLocation;
use Bolt\Extensions\BaseExtensionInterface;

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
     * List of menu items to add in the backend
     *
     * @var array
     */
    private $menuoptions;

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
        $this->basefolder = $app['resources']->getPath('extensions');
        $this->matchedcomments = array();

        if ($app['config']->get('general/add_jquery')) {
            $this->addjquery = true;
        } else {
            $this->addjquery = false;
        }
    }

    /**
     * Autoloads all registered extension files with an instance of the app
     *
     * @return void
     **/
    public function autoload($app)
    {
        $loader = new \Composer\Autoload\ClassLoader();

        $mapfile = $this->basefolder . '/vendor/composer/autoload_psr4.php';
        if (is_readable($mapfile)) {
            $map = require_once $mapfile;
            foreach ($map as $namespace => $path) {
                $loader->setPsr4($namespace, $path);
            }

            $mapfile = $this->basefolder . '/vendor/composer/autoload_classmap.php';
            if (is_readable($mapfile)) {
                $map = require_once $mapfile;
                $loader->addClassMap($map);
            }
            $loader->register();
        }

        $filepath = $this->basefolder.'/vendor/composer/autoload_files.php';
        if (is_readable($filepath)) {
            $files = include $filepath;
            foreach ($files as $file) {
                try {
                    $current = str_replace($app['resources']->getPath('extensions'), '', $file);
                    ob_start(function() use($current){
                        $error=error_get_last();
                        if ($error['type'] == 1) {
            
                            $message = $this->app['translator']->trans("There is a fatal error in one of the extensions loaded on your Bolt Installation.");
                            if ($current) {
                                $message .= $this->app['translator']->trans(" Try removing the package that was initialized here: ".$current);
                            }
                            return $message;
                        }
                    });
                    if (is_readable($file)) {
                        require $file;
                    }
                } catch (\Exception $e) {
                    $app->redirect($app["url_generator"]->generate("repair", array('path'=>$current)));
                }
                
            }
        }
    }

    /**
     * Extension register method. Allows any extension to register itself onto the enabled stack.
     *
     * @return void
     **/
    public function register(BaseExtensionInterface $extension)
    {
        $name = $extension->getName();
        $this->app['extensions.'.$name] = $extension;
        $this->enabled[$name] = $this->app['extensions.'.$name];
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
        $this->autoload($this->app);
        foreach ($this->enabled as $name => $extension) {
            
            try {
                $extension->getConfig();
                $extension->initialize();
                $this->initialized[$name] = $extension;
            } catch (\Exception $e) {
                $path = str_replace($app['resources']->getPath('extensions'), '', $file);
                $app->redirect($app["url_generator"]->generate("repair", array('package'=>$name)));
            }

            

            // Check if (instead, or on top of) initialize, the extension has a 'getSnippets' method
            $this->getSnippets($name);

            if ($extension instanceof \Twig_Extension) {
                $this->app['twig']->addExtension($extension);
                if (!empty($info['allow_in_user_content'])) {
                    $this->app['safe_twig']->addExtension($extension);
                }
            }
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
     * @param bool $late
     */
    public function addCss($filename, $late = false)
    {
        $html = sprintf('<link rel="stylesheet" href="%s" media="screen">', $filename);

        if ($late) {
            $this->insertSnippet(SnippetLocation::END_OF_BODY, $html);
        } else {
            $this->insertSnippet(SnippetLocation::BEFORE_CSS, $html);
        }
    }

    /**
     * Add a particular javascript file to the output. This will be inserted after
     * the other javascript files.
     * @param string $filename
     * @param bool $late
     */
    public function addJavascript($filename, $late = false)
    {
        $html = sprintf('<script src="%s"></script>', $filename);

        if ($late) {
            $this->insertSnippet(SnippetLocation::END_OF_BODY, $html);
        } else {
            $this->insertSnippet(SnippetLocation::AFTER_JS, $html);
        }
    }

    /**
     * Insert a widget. And by 'insert' we actually mean 'add it to the queue,
     * to be processed later'.
     */
    public function insertWidget($type, $location, $callback, $extensionname, $additionalhtml = '', $defer = true, $cacheduration = 180, $extraparameters = "")
    {
        $user = $this->app['session']->get('user');

        $sessionkey = !empty($user['sessionkey']) ? $user['sessionkey'] : '';

        $key = substr(md5(sprintf("%s%s%s%s", $sessionkey, $type, $location, !is_array($callback) ? $callback : get_class($callback[0]) . $callback[1])), 0, 8);

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

                    $html = sprintf(
                        "<section><div class='widget' id='widget-%s' data-key='%s'%s>%s</div>%s</section>",
                        $widget['key'],
                        $widget['key'],
                        !$widget['defer'] ? '' : " data-defer='true'",
                        $widget['defer'] ? '' : $this->renderWidget($widget['key']),
                        empty($widget['additionalhtml']) ? '' : "\n" . $widget['additionalhtml']
                    );

                    echo $html;
                }
            }
        }
    }

    /**
     * Renders the widget identified by the given key.
     *
     * @param  string $key Widget identifier
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
                } elseif (is_string($widget['callback']) && method_exists($this->initialized[$widget['extension']], $widget['callback'])) {
                    // Widget is defined in the extension itself.
                    $html = $this->initialized[$widget['extension']]->parseWidget($widget['callback'], $widget['extraparameters']);
                    $this->app['cache']->save($cachekey, $html, $widget['cacheduration']);
                } elseif (is_callable($widget['callback'])) {
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
                    $snippet[3] = '';
                }
                $this->insertSnippet($snippet[0], $snippet[1], $snippet[2], $snippet[3]);
            }
        }
    }

    /**
     * Insert a snippet. And by 'insert' we actually mean 'add it to the queue, to be processed later'.
     */
    public function insertSnippet($location, $callback, $extensionname = 'core', $extraparameters = '')
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
            } elseif (function_exists($item['callback'])) {
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
     * @param bool $insidehead
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
        if (preg_match_all("~^([ \t]*)(.*)</script>~mi", $context, $matches)) {
            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = str_replace_first($matches[0][$last], $replacement, $html);

        } elseif ($insidehead) {
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
     * @param  string $html
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
            $jqueryfile = $this->app['paths']['app'] . "view/js/jquery-1.10.2.min.js";
            $html = $this->insertBeforeJs("<script src='$jqueryfile'></script>", $html);

            return $html;
        } else {
            // We've already got jQuery. Yay, us!
            return $html;
        }
    }

    /**
     * Add a menu option to the 'settings' menu. Note that the item is only added if the current user
     * meets the required permission.
     *
     * @see \Bolt\BaseExtension\addMenuOption()
     *
     * @param string $label
     * @param string $path
     * @param bool $icon
     * @param string $requiredPermission (NULL if no permission is required)
     */
    public function addMenuOption($label, $path, $icon = false, $requiredPermission = null)
    {
        // Fix the path, if we have not given a full path..
        if (strpos($path, '/') === false) {
            $path = $this->app['paths']['bolt'] . $path;
        }

        if (empty($requiredPermission) || $this->app['users']->isAllowed($requiredPermission)) {
            $this->menuoptions[$path] = array(
                'label' => $label,
                'path' => $path,
                'icon' => $icon
            );
        }
    }

    /**
     * Check if there are additional menu-options set for the current user.
     *
     * @see \Bolt\Extensions\hasMenuOptions()
     */
    public function hasMenuOptions()
    {
        return (!empty($this->menuoptions));
    }

    /**
     * Get an array with the additional menu-options that are set for the current user.
     *
     * @see \Bolt\Extensions\hasMenuOptions()
     */
    public function getMenuOptions()
    {
        return $this->menuoptions;
    }

    /**
     * Callback method to identify comments and store them in the matchedcomments
     * array. These will be put back after the replacements on the HTML are
     * finished.
     *
     * @param  string $c
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
