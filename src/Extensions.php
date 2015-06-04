<?php

namespace Bolt;

use Bolt;
use Bolt\Composer\Action\BoltExtendJson;
use Bolt\Extensions\ExtensionInterface;
use Bolt\Extensions\Snippets\Location as SnippetLocation;
use Bolt\Helpers\Str;
use Bolt\Translation\Translator as Trans;
use Composer\Autoload\ClassLoader;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Monolog\Logger;
use Symfony\Component\Finder\Finder;

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
     * @var ExtensionInterface[]
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
     * List of menu items to add in the backend.
     *
     * @var array
     */
    private $menuoptions = array();

    /**
     * Number of registered extensions that need to be able to send mail.
     *
     * @var integer
     */
    private $mailsenders = 0;

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

    /**
     * Contains json of loaded extensions.
     *
     * @var array
     */
    public $composer = array();

    /**
     * Contains a list of all css and js assets added through addCss and
     * addJavascript functions.
     *
     * @var array
     */
    private $assets;

    private $isInitialized = false;

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

        $this->assets = array(
            'css' => array(),
            'js'  => array()
        );
    }

    /**
     * Autoloads all registered extension files with an instance of the app.
     *
     * @return void
     **/
    public function autoload($app)
    {
        $loader = new ClassLoader();

        $mapfile = $this->basefolder . '/vendor/composer/autoload_psr4.php';
        if (is_readable($mapfile)) {
            $map = require $mapfile;
            foreach ($map as $namespace => $path) {
                $loader->setPsr4($namespace, $path);
            }

            $mapfile = $this->basefolder . '/vendor/composer/autoload_classmap.php';
            if (is_readable($mapfile)) {
                $map = require $mapfile;
                $loader->addClassMap($map);
            }

            $loader->register();
        }

        $filepath = $this->basefolder . '/vendor/composer/autoload_files.php';
        if (is_readable($filepath)) {
            $files = include $filepath;
            foreach ($files as $file) {
                try {
                    if (is_readable($file)) {
                        require $file;
                    }
                } catch (\Exception $e) {
                    $this->logInitFailure('Error importing extension class', $file, $e, Logger::ERROR);
                }
            }
        }
    }

    /**
     * Workaround to load locally installed extensions.
     *
     * @param Application $app
     */
    public function localload($app)
    {
        $flag = $this->app['filesystem']->has('extensions://local');

        // Check that local exists
        if (!$flag) {
            return;
        }

        // Find init.php files that are exactly 2 directories below etensions/local/
        $finder = new Finder();
        $finder->files()
               ->in($this->basefolder . '/local')
               ->followLinks()
               ->name('init.php')
               ->depth('== 2')
       ;

        foreach ($finder as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            try {
                // Include the extensions core file
                require_once dirname($file->getRealpath()) . '/Extension.php';

                // Include the init file
                require_once $file->getRealpath();

                // Mark is as a local extension
                $extension = end($this->enabled);
                $extension->setInstallType('local');
            } catch (\Exception $e) {
                $this->logInitFailure('Error importing local extension class', $file->getBasename(), $e, Logger::ERROR);
            }
        }
    }

    /**
     * Check for local extension composer.json files and import their PSR-4 settings.
     *
     * @param boolean $force
     *
     * @internal
     */
    public function checkLocalAutoloader($force = false)
    {
        if (!$this->app['filesystem']->has('extensions://local/') || !$force || $this->app['filesystem']->has('extensions://local/.built')) {
            return;
        }

        if (!$this->app['filesystem']->has('extensions://composer.json')) {
            $initjson = new BoltExtendJson($this->app['extend.manager']->getOptions());
            $this->json = $initjson->updateJson($this->app);
        }

        // Get Bolt's extension JSON
        $composerOptions = $this->app['extend.manager']->getOptions();
        $composerJsonFile = new JsonFile($composerOptions['composerjson']);
        $boltJson = $composerJsonFile->read();
        $boltPsr4 = isset($boltJson['autoload']['psr-4']) ? $boltJson['autoload']['psr-4'] : array();

        $finder = new Finder();
        $finder->files()
            ->in($this->basefolder . '/local')
            ->followLinks()
            ->name('composer.json')
            ->depth('== 2')
        ;

        foreach ($finder as $file) {
            try {
                $extensionJsonFile = new JsonFile($file->getRealpath());
                $json = $extensionJsonFile->read();
            } catch (\Exception $e) {
                // Ignore for now
            }

            if (isset($json['autoload']['psr-4'])) {
                $basePath = str_replace($this->app['resources']->getPath('extensions/local'), 'local', dirname($file->getRealpath()));
                $psr4 = $this->getLocalExtensionPsr4($basePath, $json['autoload']['psr-4']);
                $boltPsr4 = array_merge($boltPsr4, $psr4);
            }
        }

        $boltJson['autoload']['psr-4'] = $boltPsr4;
        $composerJsonFile->write($boltJson);
        $this->app['extend.manager']->dumpautoload();
        $this->app['filesystem']->write('extensions://local/.built', time());
    }

    /**
     * Get the PSR-4 data for a local extension with the paths adjusted.
     *
     * @param string $path
     * @param array  $autoload
     *
     * @return array
     */
    private function getLocalExtensionPsr4($path, array $autoload)
    {
        $psr4 = array();
        foreach ($autoload as $namespace => $namespacePaths) {
            $paths = null;
            if (is_string($namespacePaths)) {
                $paths = "$path/$namespacePaths";
            } else {
                foreach ($namespacePaths as $namespacePath) {
                    $paths[] = "$path/$namespacePath";
                }
            }

            $psr4[$namespace] = $paths;
        }

        return $psr4;
    }

    /**
     * Extension register method. Allows any extension to register itself onto the enabled stack.
     *
     * @param ExtensionInterface $extension
     *
     * @return void
     */
    public function register(ExtensionInterface $extension)
    {
        $name = $extension->getName();
        $this->app['extensions.' . $name] = $extension;
        $this->enabled[$name] = $this->app['extensions.' . $name];

        // Store the composer part of the extensions config
        $conf = $extension->getExtensionConfig();
        foreach ($conf as $key => $value) {
            $this->composer[$key] = $value;
        }

        // Conditionally initalise extension
        if ($this->isInitialized) {
            $this->initializeExtension($extension);
        }
    }

    /**
     * Check if an extension is enabled, case sensitive.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isEnabled($name)
    {
        return array_key_exists($name, $this->enabled);
    }

    /**
     * Get the enabled extensions.
     *
     * @return ExtensionInterface[]
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Gets the composer config for an extension.
     *
     * @param string $extensionName
     *
     * @return array
     */
    public function getComposerConfig($extensionName)
    {
        return isset($this->composer[$extensionName]) ? $this->composer[$extensionName] : array();
    }

    /**
     * Initialize the enabled extensions.
     */
    public function initialize()
    {
        // Don't initialise if extension loading globally disabled
        if (!$this->app['extend.enabled']) {
            return;
        }

        $this->autoload($this->app);
        $this->localload($this->app);
        $this->isInitialized = true;
        foreach ($this->enabled as $extension) {
            $this->initializeExtension($extension);
        }
    }

    protected function initializeExtension(ExtensionInterface $extension)
    {
        $name = $extension->getName();

        // Attempt to get extension YAML config
        try {
            $extension->getConfig();
        } catch (\Exception $e) {
            $this->logInitFailure('Failed to load YAML config', $name, $e, Logger::ERROR);

            return;
        }

        // Call extension initialize()
        try {
            $extension->initialize();

            // Add an object of this extension to the global Twig scope.
            $namespace = $this->getNamespace($extension);
            if (!empty($namespace)) {
                $this->app['twig'] = $this->app->share(
                    $this->app->extend(
                        'twig',
                        function (\Twig_Environment $twig) use ($namespace, $extension) {
                            $twig->addGlobal($namespace, $extension);

                            return $twig;
                        }
                    )
                );
            }
        } catch (\Exception $e) {
            $this->logInitFailure('Initialisation failed', $name, $e, Logger::ERROR);

            return;
        }

        // Flag the extension as initialised
        $this->initialized[$name] = $extension;

        // If an extension makes it known it sends email, increase the counter
        if (is_callable(array($extension, 'sendsMail')) && $extension->sendsMail()) {
            $this->mailsenders++;
        }

        // Get the extension defined snippets
        try {
            $this->getSnippets($name);
        } catch (\Exception $e) {
            $this->logInitFailure('Snippet loading failed', $name, $e, Logger::ERROR);

            return;
        }

        // Add Twig extensions
        if (!is_callable(array($extension, 'getTwigExtensions'))) {
            return;
        }
        /** @var \Twig_Extension[] $extensions */
        $twigExtensions = $extension->getTwigExtensions();
        $addTwigExFunc = array($this, 'addTwigExtension');
        foreach ($twigExtensions as $twigExtension) {
            $this->app['twig'] = $this->app->share(
                $this->app->extend(
                    'twig',
                    function (\Twig_Environment $twig) use ($addTwigExFunc, $twigExtension, $name) {
                        call_user_func($addTwigExFunc, $twig, $twigExtension, $name);

                        return $twig;
                    }
                )
            );

            if (!is_callable(array($extension, 'isSafe')) || !$extension->isSafe()) {
                continue;
            }
            $this->app['safe_twig'] = $this->app->share(
                $this->app->extend(
                    'safe_twig',
                    function (\Twig_Environment $twig) use ($addTwigExFunc, $twigExtension, $name) {
                        call_user_func($addTwigExFunc, $twig, $twigExtension, $name);

                        return $twig;
                    }
                )
            );
        }
    }

    /**
     * @internal DO NOT USE!
     *
     * @param \Twig_Environment $twig
     * @param \Twig_Extension   $extension
     * @param string            $name
     */
    public function addTwigExtension(\Twig_Environment $twig, $extension, $name)
    {
        try {
            $twig->addExtension($extension);
        } catch (\Exception $e) {
            $this->logInitFailure('Twig function registration failed', $name, $e, Logger::ERROR);
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
     * Returns a list of all css and js assets that are added via extensions.
     *
     * @return array
     */
    public function getAssets()
    {
        return $this->assets;
    }

    private function getNamespace($extension)
    {
        $classname = get_class($extension);
        $classatoms = explode('\\', $classname);

        // throw away last atom.
        array_pop($classatoms);

        // return second to last as namespace name
        return (array_pop($classatoms));
    }

    /**
     * Add a particular CSS file to the output. This will be inserted before the
     * other css files.
     *
     * @param string $filename File name to add to href=""
     * @param array  $options  'late'     - True to add to the end of the HTML <body>
     *                         'priority' - Loading priority
     *                         'attrib'   - A string containing either/or 'defer', and 'async'
     */
    public function addCss($filename, $options = array())
    {
        // Handle pre-2.2 function parameters, namely $late and $priority
        if (!is_array($options)) {
            $args = func_get_args();

            $options = array(
                'late'     => isset($args[1]) ? isset($args[1]) : false,
                'priority' => isset($args[2]) ? isset($args[2]) : 0,
                'attrib'   => false
            );
        }

        $this->assets['css'][md5($filename)] = array(
            'filename' => $filename,
            'late'     => isset($options['late'])     ? $options['late']     : false,
            'priority' => isset($options['priority']) ? $options['priority'] : 0,
            'attrib'   => isset($options['attrib'])   ? $options['attrib']   : false
        );
    }

    /**
     * Add a particular javascript file to the output. This will be inserted after
     * the other javascript files.
     *
     * @param string $filename File name to add to src=""
     * @param array  $options  'late'     - True to add to the end of the HTML <body>
     *                         'priority' - Loading priority
     *                         'attrib'   - A string containing either/or 'defer', and 'async'
     */
    public function addJavascript($filename, $options = array())
    {
        // Handle pre-2.2 function parameters, namely $late and $priority
        if (!is_array($options)) {
            $args = func_get_args();

            $options = array(
                'late'     => isset($args[1]) ? isset($args[1]) : false,
                'priority' => isset($args[2]) ? isset($args[2]) : 0,
                'attrib'   => false
            );
        }

        $this->assets['js'][md5($filename)] = array(
            'filename' => $filename,
            'late'     => isset($options['late'])     ? $options['late']     : false,
            'priority' => isset($options['priority']) ? $options['priority'] : 0,
            'attrib'   => isset($options['attrib'])   ? $options['attrib']   : false
        );
    }

    /**
     * Insert a widget. And by 'insert' we actually mean 'add it to the queue,
     * to be processed later'.
     *
     * @param        $type
     * @param        $location
     * @param        $callback
     * @param        $extensionname
     * @param string $additionalhtml
     * @param bool   $defer
     * @param int    $cacheduration
     * @param string $extraparameters
     */
    public function insertWidget($type, $location, $callback, $extensionname, $additionalhtml = '', $defer = true, $cacheduration = 180, $extraparameters = "")
    {
        $user = $this->app['session']->get('user');

        $sessionkey = !empty($user['sessionkey']) ? $user['sessionkey'] : '';

        $key = substr(md5(sprintf("%s%s%s%s", $sessionkey, $type, $location, !is_array($callback) ? $callback : get_class($callback[0]) . $callback[1])), 0, 8);

        $this->widgetqueue[] = array(
            'type'            => $type,
            'location'        => $location,
            'callback'        => $callback,
            'additionalhtml'  => $additionalhtml,
            'cacheduration'   => $cacheduration,
            'extension'       => $extensionname,
            'defer'           => $defer,
            'extraparameters' => $extraparameters,
            'key'             => $key
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
     * @param string $key Widget identifier
     *
     * @return string HTML
     */
    public function renderWidget($key)
    {
        foreach ($this->widgetqueue as $widget) {
            if ($key == $widget['key']) {
                $cachekey = 'widget_' . $widget['key'];

                if ($this->app['cache']->contains($cachekey)) {
                    // Present in the cache .
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
     * Call the 'getSnippets' function of an initialized extension, and make sure the snippets are initialized.
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
     *
     * @param        $location
     * @param        $callback
     * @param string $extensionname
     * @param string $extraparameters
     */
    public function insertSnippet($location, $callback, $extensionname = 'core', $extraparameters = '')
    {
        $key = md5($extensionname . $callback . $location);

        $this->snippetqueue[$key] = array(
            'location'        => $location,
            'callback'        => $callback,
            'extension'       => $extensionname,
            'extraparameters' => $extraparameters
        );
    }

    /**
     * Clears the snippet queue.
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

        // Replace the snippets in the queue.
        foreach ($this->snippetqueue as $item) {

            // Get the snippet, either by using a callback function, or else use the
            // passed string as-is.

            if (($item['extension'] != "core") && method_exists($this->initialized[$item['extension']], $item['callback'])) {
                // Snippet is defined in the extension itself.
                $snippet = $this->initialized[$item['extension']]->parseSnippet($item['callback'], $item['extraparameters']);
            } elseif (function_exists($item['callback'])) {
                // Snippet is a callback in the 'global scope'
                $snippet = call_user_func($item['callback'], $this->app, $item['extraparameters']);
            } else {
                // Insert the 'callback' as a string.
                $snippet = $item['callback'];
            }

            // then insert it into the HTML, somewhere.
            switch ($item['location']) {
                case SnippetLocation::END_OF_HEAD:
                case SnippetLocation::AFTER_HEAD_JS: // same as end of head because we cheat a little
                case SnippetLocation::AFTER_HEAD_CSS: // same as end of head because we cheat a little
                case SnippetLocation::AFTER_HEAD_META: // same as end of head because meta tags are unordered
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
                case SnippetLocation::BEFORE_HEAD_JS: // same as start of head because we cheat a little
                case SnippetLocation::BEFORE_HEAD_CSS: // same as start of head because we cheat a little
                case SnippetLocation::BEFORE_HEAD_META: // same as start of head because meta tags are unordered
                    $html = $this->insertStartOfHead($snippet, $html);
                    break;
                case SnippetLocation::START_OF_BODY:
                case SnippetLocation::BEFORE_BODY_JS: // same as start of body because we cheat a little
                case SnippetLocation::BEFORE_BODY_CSS: // same as start of body because we cheat a little
                    $html = $this->insertStartOfBody($snippet, $html);
                    break;
                case SnippetLocation::END_OF_BODY:
                case SnippetLocation::AFTER_BODY_JS: // same as end of body because we cheat a little
                case SnippetLocation::AFTER_BODY_CSS: // same as end of body because we cheat a little
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

        // While this looks slightly illogical, our CLI tests want to see that
        // jQuery can be inserted, but we don't want it inserted on either the
        // backend or AJAX requests.
        $end = $this->app['config']->getWhichEnd();
        if ($this->addjquery === true && ($end === 'frontend' || $end === 'cli')) {
            $html = $this->insertJquery($html);
        }

        // Finally, replace back ###comment### with its original comment.
        if (!empty($this->matchedcomments)) {
            $html = preg_replace(array_keys($this->matchedcomments), $this->matchedcomments, $html, 1);
        }

        return $html;
    }

    /**
     * Insert all assets in template. Use sorting by priority.
     *
     * @param $html
     *
     * @return string
     */
    public function processAssets($html)
    {
        foreach ($this->getAssets() as $type => $files) {

            // Use http://en.wikipedia.org/wiki/Schwartzian_transform for stable sort
            // We use create_function(), because it's faster than closure
            // decorate
            array_walk($files, create_function('&$v, $k', '$v = array($v[\'priority\'], $k, $v);'));
            // sort
            sort($files);
            // undecorate
            array_walk($files, create_function('&$v, $k', '$v = $v[2];'));

            foreach ($files as $file) {
                $late     = $file['late'];
                $filename = $file['filename'];
                $attrib   = $file['attrib'] ? ' ' . $file['attrib'] : '';

                if ($type === 'js') {
                    $htmlJs = sprintf('<script src="%s"%s></script>', $filename, $attrib);
                    if ($late) {
                        $html = $this->insertEndOfBody($htmlJs, $html);
                    } else {
                        $html = $this->insertAfterJs($htmlJs, $html);
                    }
                } else {
                    $htmlCss = sprintf('<link rel="stylesheet" href="%s" media="screen">', $filename);
                    if ($late) {
                        $html = $this->insertEndOfBody($htmlCss, $html);
                    } else {
                        $html = $this->insertBeforeCss($htmlCss, $html);
                    }
                }
            }
        }

        return $html;
    }

    /**
     * Helper function to insert some HTML into thestart of the head section of
     * an HTML page, right after the <head> tag.
     *
     * @param string $tag
     * @param string $html
     *
     * @return string
     */
    public function insertStartOfHead($tag, $html)
    {
        // first, attempt to insert it after the <head> tag, matching indentation.

        if (preg_match("~^([ \t]*)<head(.*)~mi", $html, $matches)) {

            // Try to insert it after <head>
            $replacement = sprintf("%s\n%s\t%s", $matches[0], $matches[1], $tag);
            $html = Str::replaceFirst($matches[0], $replacement, $html);
        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";
        }

        return $html;
    }

    /**
     * Helper function to insert some HTML into thestart of the head section of
     * an HTML page, right after the <head> tag.
     *
     * @param string $tag
     * @param string $html
     *
     * @return string
     */
    public function insertStartOfBody($tag, $html)
    {
        // first, attempt to insert it after the <body> tag, matching indentation.
        if (preg_match("~^([ \t]*)<body(.*)~mi", $html, $matches)) {

            // Try to insert it after <body>
            $replacement = sprintf("%s\n%s\t%s", $matches[0], $matches[1], $tag);
            $html = Str::replaceFirst($matches[0], $replacement, $html);
        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";
        }

        return $html;
    }

    /**
     * Helper function to insert some HTML into the head section of an HTML
     * page, right before the </head> tag.
     *
     * @param string $tag
     * @param string $html
     *
     * @return string
     */
    public function insertEndOfHead($tag, $html)
    {
        // first, attempt to insert it before the </head> tag, matching indentation.
        if (preg_match("~^([ \t]*)</head~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = Str::replaceFirst($matches[0], $replacement, $html);
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
     * @param string $tag
     * @param string $html
     *
     * @return string
     */
    public function insertEndOfBody($tag, $html)
    {
        // first, attempt to insert it before the </body> tag, matching indentation.
        if (preg_match("~^([ \t]*)</body~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = Str::replaceFirst($matches[0], $replacement, $html);
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
     * @param string $tag
     * @param string $html
     *
     * @return string
     */
    public function insertEndOfHtml($tag, $html)
    {
        // first, attempt to insert it before the </body> tag, matching indentation.
        if (preg_match("~^([ \t]*)</html~mi", $html, $matches)) {

            // Try to insert it just before </head>
            $replacement = sprintf("%s\t%s\n%s", $matches[1], $tag, $matches[0]);
            $html = Str::replaceFirst($matches[0], $replacement, $html);
        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";
        }

        return $html;
    }

    /**
     * Helper function to insert some HTML into the head section of an HTML page.
     *
     * @param string $tag
     * @param string $html
     *
     * @return string
     */
    public function insertAfterMeta($tag, $html)
    {
        // first, attempt to insert it after the last meta tag, matching indentation.
        if (preg_match_all("~^([ \t]*)<meta (.*)~mi", $html, $matches)) {

            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = Str::replaceFirst($matches[0][$last], $replacement, $html);
        } else {
            $html = $this->insertEndOfHead($tag, $html);
        }

        return $html;
    }

    /**
     * Helper function to insert some HTML into the head section of an HTML page.
     *
     * @param string $tag
     * @param string $html
     *
     * @return string
     */
    public function insertAfterCss($tag, $html)
    {
        // first, attempt to insert it after the last <link> tag, matching indentation.
        if (preg_match_all("~^([ \t]*)<link (.*)~mi", $html, $matches)) {

            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = Str::replaceFirst($matches[0][$last], $replacement, $html);
        } else {
            $html = $this->insertEndOfHead($tag, $html);
        }

        return $html;
    }

    /**
     * Helper function to insert some HTML before the first CSS include in the page.
     *
     * @param string $tag
     * @param string $html
     *
     * @return string
     */
    public function insertBeforeCss($tag, $html)
    {
        // first, attempt to insert it after the <body> tag, matching indentation.
        if (preg_match("~^([ \t]*)<link(.*)~mi", $html, $matches)) {

            // Try to insert it before the match
            $replacement = sprintf("%s%s\n%s\t%s", $matches[1], $tag, $matches[0], $matches[1]);
            $html = Str::replaceFirst($matches[0], $replacement, $html);
        } else {

            // Since we're serving tag soup, just append it.
            $html .= $tag . "\n";
        }

        return $html;
    }

    /**
     * Helper function to insert some HTML before the first javascript include in the page.
     *
     * @param string $tag
     * @param string $html
     *
     * @return string
     */
    public function insertBeforeJS($tag, $html)
    {
        // first, attempt to insert it after the <body> tag, matching indentation.
        if (preg_match("~^([ \t]*)<script(.*)~mi", $html, $matches)) {

            // Try to insert it before the match
            $replacement = sprintf("%s%s\n%s\t%s", $matches[1], $tag, $matches[0], $matches[1]);
            $html = Str::replaceFirst($matches[0], $replacement, $html);
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
     * @param string $tag
     * @param string $html
     * @param bool   $insidehead
     *
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

        // then, attempt to insert it after the last <script> tag within context, matching indentation.
        if (preg_match_all("~^([ \t]*)(.*)</script>~mi", $context, $matches)) {
            // matches[0] has some elements, the last index is -1, because zero indexed.
            $last = count($matches[0]) - 1;
            $replacement = sprintf("%s\n%s%s", $matches[0][$last], $matches[1][$last], $tag);
            $html = Str::replaceFirst($matches[0][$last], $replacement, $html);
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
     * @param string $html
     *
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
            $jqueryfile = $this->app['paths']['app'] . 'view/js/jquery-1.11.2.min.js';
            $html = $this->insertBeforeJs('<script src="' . $jqueryfile . '"></script>', $html);
        }

        return $html;
    }

    /**
     * Add a menu option to the 'settings' menu. Note that the item is only added if the current user
     * meets the required permission.
     *
     * @see \Bolt\BaseExtension\addMenuOption()
     *
     * @param string $label
     * @param string $path
     * @param bool   $icon
     * @param string $requiredPermission (NULL if no permission is required)
     */
    public function addMenuOption($label, $path, $icon = false, $requiredPermission = null)
    {
        // Fix the path, if we have not given a full path.
        if (strpos($path, '/') === false) {
            $path = $this->app['resources']->getUrl('bolt') . $path;
        }

        if (empty($requiredPermission) || $this->app['users']->isAllowed($requiredPermission)) {
            $this->menuoptions[$path] = array(
                'label' => $label,
                'path'  => $path,
                'icon'  => $icon
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
     * Returns whether or not there are any extensions that need so send mail
     */
    public function hasMailSenders()
    {
        return $this->mailsenders;
    }

    /**
     * @param string     $msg
     * @param string     $extensionName
     * @param \Exception $e
     * @param int        $level
     */
    protected function logInitFailure($msg, $extensionName, \Exception $e, $level = Logger::CRITICAL)
    {
        $context = array(
            'event'     => 'extensions',
            'exception' => $e
        );

        $this->app['logger.system']->addRecord($level, sprintf("%s for %s: %s", $msg, $extensionName, $e->getMessage()), $context);

        $this->app['session']->getFlashBag()->add(
            'error',
            Trans::__("[Extension error] $msg for %ext%: %error%", array('%ext%' => $extensionName, '%error%' => $e->getMessage()))
        );
    }

    /**
     * Callback method to identify comments and store them in the matchedcomments
     * array. These will be put back after the replacements on the HTML are
     * finished.
     *
     * @param string $c
     *
     * @return string The key under which the comment is stored
     */
    private function pregcallback($c)
    {
        $key = "###bolt-comment-" . count($this->matchedcomments) . "###";
        // Add it to the array of matched comments.
        $this->matchedcomments["/" . $key . "/"] = $c[0];

        return $key;
    }
}
