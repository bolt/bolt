<?php

namespace Bolt;

use Bolt;
use Bolt\Asset\Target;
use Bolt\Extensions\ExtensionInterface;
use Bolt\Translation\Translator as Trans;
use Composer\Autoload\ClassLoader;
use Composer\Json\JsonFile;
use Monolog\Logger;
use Silex;
use Symfony\Component\Finder\Finder;

class Extensions
{
    /** @var \Silex\Application */
    private $app;
    /** @var string The extension base folder. */
    private $basefolder;
    /** @var ExtensionInterface[] List of enabled extensions. */
    private $enabled = [];
    /** @var array Queue with widgets to insert. */
    private $widgetqueue;
    /** @var array List of menu items to add in the backend. */
    private $menuoptions = [];
    /** @var integer Number of registered extensions that need to be able to send mail. */
    private $mailsenders = 0;
    /** @var array Contains all initialized extensions. */
    private $initialized;

    /**
     * Contains json of loaded extensions.
     *
     * @var array
     */
    public $composer = [];

    private $isInitialized = false;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->basefolder = $app['resources']->getPath('extensions');
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
        if (!$this->app['filesystem']->has('extensions://local/')) {
            return;
        }

        if (!$force && $this->app['filesystem']->has('app://cache/.local.autoload.built')) {
            return;
        }

        // If the composer.json file doesn't exist, we should create it now
        if (!$this->app['filesystem']->has('extensions://composer.json')) {
            $this->app['extend.action']['json']->updateJson();
        }

        $finder = new Finder();
        $finder->files()
            ->in($this->basefolder . '/local')
            ->followLinks()
            ->name('composer.json')
            ->depth('== 2')
        ;

        if ($finder->count() > 0) {
            $this->setLocalExtensionPsr4($finder);
        }
    }

    /**
     * Write the PSR-4 data to the extensions/composer.json file.
     *
     * @param Finder $finder
     */
    private function setLocalExtensionPsr4(Finder $finder)
    {
        // Get Bolt's extension JSON
        $composerOptions = $this->app['extend.action.options'];
        $composerJsonFile = new JsonFile($composerOptions['composerjson']);
        $boltJson = $composerJsonFile->read();
        $boltPsr4 = isset($boltJson['autoload']['psr-4']) ? $boltJson['autoload']['psr-4'] : [];

        foreach ($finder as $file) {
            try {
                $extensionJsonFile = new JsonFile($file->getRealpath());
                $json = $extensionJsonFile->read();
            } catch (\Exception $e) {
                $this->logInitFailure('Reading local extension composer.json file failed', $file->getRealpath(), $e, Logger::ERROR);
            }

            if (isset($json['autoload']['psr-4'])) {
                $basePath = str_replace($this->app['resources']->getPath('extensions/local'), 'local', dirname($file->getRealpath()));
                $psr4 = $this->getLocalExtensionPsr4($basePath, $json['autoload']['psr-4']);
                $boltPsr4 = array_merge($boltPsr4, $psr4);
            }
        }

        // Modify Bolt's extension JSON and write out changes
        $boltJson['autoload']['psr-4'] = $boltPsr4;
        $composerJsonFile->write($boltJson);
        $this->app['extend.manager']->dumpautoload();
        $this->app['filesystem']->put('app://cache/.local.autoload.built', time());
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
        $psr4 = [];
        foreach ($autoload as $namespace => $namespacePaths) {
            $paths = null;
            if (is_string($namespacePaths)) {
                $paths = "$path/$namespacePaths";
            } else {
                foreach ($namespacePaths as $namespacePath) {
                    $paths[] = "$path/$namespacePath";
                }
            }

            // Ensure the namespace is valid for PSR-4
            $namespace = rtrim($namespace, '\\') . '\\';
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
     * Get an initialized extension object.
     *
     * @param string $name
     *
     * @return object|null
     */
    public function getInitialized($name)
    {
        if (array_key_exists($name, $this->initialized)) {
            return $this->initialized[$name];
        }
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
        return isset($this->composer[$extensionName]) ? $this->composer[$extensionName] : ['name' => $extensionName];
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

        try {
            $this->loadExtensionConfig($extension, $name);
            $this->loadExtensionInitialize($extension, $name);
            $this->loadExtensionTwigGlobal($extension, $name);
            $this->loadExtensionTwig($extension, $name);
        } catch (\Exception $e) {
            // Should be already caught, go into slient mode
        }

        // Flag the extension as initialised
        $this->initialized[$name] = $extension;

        // If an extension makes it known it sends email, increase the counter
        if (is_callable([$extension, 'sendsMail']) && $extension->sendsMail()) {
            $this->mailsenders++;
        }
    }

    /**
     * Attempt to get extension YAML config.
     *
     * @param ExtensionInterface $extension
     * @param string             $name
     *
     * @throws \Exception
     */
    private function loadExtensionConfig(ExtensionInterface $extension, $name)
    {
        try {
            $extension->getConfig();
        } catch (\Exception $e) {
            $this->logInitFailure('Failed to load YAML config', $name, $e, Logger::ERROR);
            throw $e;
        }
    }

    /**
     * Initialise the extension.
     *
     * @param ExtensionInterface $extension
     * @param string             $name
     *
     * @throws \Exception
     */
    private function loadExtensionInitialize(ExtensionInterface $extension, $name)
    {
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
                ));
            }
        } catch (\Exception $e) {
            $this->logInitFailure('Initialisation failed', $name, $e, Logger::ERROR);

            throw $e;
        }
    }

    /**
     * Add an object of this extension to the global Twig scope.
     *
     * @param ExtensionInterface $extension
     * @param string             $name
     *
     * @throws \Exception
     */
    private function loadExtensionTwigGlobal(ExtensionInterface $extension, $name)
    {
        try {
            $namespace = $this->getNamespace($extension);
            if (!empty($namespace)) {
                $this->app['twig'] = $this->app->share(
                    $this->app->extend(
                        'twig',
                        function (\Twig_Environment $twig) use ($namespace, $extension) {
                            $twig->addGlobal($namespace, $extension);

                            return $twig;
                        }
                ));
            }
        } catch (\Exception $e) {
            $this->logInitFailure('Initialisation failed', $name, $e, Logger::ERROR);

            throw $e;
        }
    }

    /**
     * Add Twig extensions.
     *
     * @param ExtensionInterface $extension
     * @param string             $name
     *
     * @throws \Exception
     */
    private function loadExtensionTwig(ExtensionInterface $extension, $name)
    {
        if (!is_callable([$extension, 'getTwigExtensions'])) {
            return;
        }

        /** @var \Twig_Extension[] $extensions */
        $twigExtensions = $extension->getTwigExtensions();
        $addTwigExFunc = [$this, 'addTwigExtension'];
        foreach ($twigExtensions as $twigExtension) {
            $this->app['twig'] = $this->app->share(
                $this->app->extend(
                    'twig',
                    function (\Twig_Environment $twig) use ($addTwigExFunc, $twigExtension, $name) {
                        call_user_func($addTwigExFunc, $twig, $twigExtension, $name);

                        return $twig;
                    }
            ));

            if (!is_callable([$extension, 'isSafe']) || !$extension->isSafe()) {
                continue;
            }
            $this->app['safe_twig'] = $this->app->share(
                $this->app->extend(
                    'safe_twig',
                    function (\Twig_Environment $twig) use ($addTwigExFunc, $twigExtension, $name) {
                        call_user_func($addTwigExFunc, $twig, $twigExtension, $name);

                        return $twig;
                    }
            ));
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
     *
     * @deprecated Since 2.3 will be removed in Bolt 3.0
     */
    public function addJquery()
    {
        $this->app['config']->set('general/add_jquery', true);
    }

    /**
     * Don't add jQuery to the output.
     *
     * @deprecated Since 2.3 will be removed in Bolt 3.0
     */
    public function disableJquery()
    {
        $this->app['config']->set('general/add_jquery', false);
    }

    /**
     * Legacy function that returns a list of all css and js assets that are
     * added via extensions.
     *
     * @deprecated Use $app['asset.queue.file']->getQueue() and/or $app['asset.queue.snippet']->getQueue()
     *
     * @return array
     */
    public function getAssets()
    {
        $files = $this->app['asset.queue.file']->getQueue();
        $assets = [
            'css' => [],
            'js'  => []
        ];

        foreach ($files['javascript'] as $file) {
            $assets['js'][] = $file->getFileName();
        }
        foreach ($files['stylesheet'] as $file) {
            $assets['css'][] = $file->getFileName();
        }

        return $assets;
    }

    /**
     * Get the namespcae from a FQCN.
     *
     * @param ExtensionInterface $extension
     */
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
    public function addCss($filename, $options = [])
    {
        // Handle pre-2.2 function parameters, namely $late and $priority
        if (!is_array($options)) {
            $args = func_get_args();

            $options = [
                'late'     => isset($args[1]) ? isset($args[1]) : false,
                'priority' => isset($args[2]) ? isset($args[2]) : 0,
                'attrib'   => false
            ];
        }

        $this->app['asset.queue.file']->add('stylesheet', $filename, $options);
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
    public function addJavascript($filename, $options = [])
    {
        // Handle pre-2.2 function parameters, namely $late and $priority
        if (!is_array($options)) {
            $args = func_get_args();

            $options = [
                'late'     => isset($args[1]) ? isset($args[1]) : false,
                'priority' => isset($args[2]) ? isset($args[2]) : 0,
                'attrib'   => false
            ];
        }

        $this->app['asset.queue.file']->add('javascript', $filename, $options);
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
    public function insertWidget($options)
    {

        // Was: $type, $location, $callback, $extensionname, $additionalhtml = '', $defer = true, $cacheduration = 180, $extraparameters = ""

        // dump($options);

        // $authSession = $this->app['session']->get('authentication');
        // $sessionkey = $authSession->getToken()->getToken();

        $options['key'] = substr(md5(json_encode($options)), 0, 8);

        $this->widgetqueue[] = $options;
    }

    /**
     * Renders a div as a placeholder for a particular type of widget on the
     * given location.
     *
     * @param string $type
     * @param string $location For convenience, use the constant from Bolt\Extensions\Snippets\Location
     */
    public function renderWidgetHolder($type, $position)
    {
        if (is_array($this->widgetqueue)) {
            foreach ($this->widgetqueue as $widget) {
                if ($type == $widget['type'] && $position == $widget['position']) {

                    if (!$widget['defer']) {
                        $widgethtml = $this->renderWidget($widget['key']);
                    } else {
                        $widgethtml = '';
                    }

                    $html = $this->app['render']->render('widgetholder.twig', [
                        'widget' => $widget,
                        'html' => $widgethtml
                    ]);

                    // If it's a widget in the frontend, _and_ we're using it defered,
                    // insert a snippet of Javascript to fetch the actual widget's contents.
                    if ($widget['type'] == 'frontend' && $widget['defer'] == true) {
                        $javascript = $this->app['render']->render('widgetjavascript.twig', [
                            'widget' => $widget
                        ]);
                        $this->app['asset.queue.snippet']->add(Target::AFTER_BODY_JS, (string) $javascript);
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
                    $html = $this->initialized[$widget['extension']]->parseWidget($widget['callback'], $widget['callbackarguments']);
                    // $this->app['cache']->save($cachekey, $html, $widget['cacheduration']);
                } elseif (is_callable($widget['callback'])) {
                    // Widget is a callback in the 'global scope'
                    $html = call_user_func($widget['callback'], $this->app, $widget['callbackarguments']);
                    // $this->app['cache']->save($cachekey, $html, $widget['cacheduration']);
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
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function insertSnippet($location, $callback, $extensionname = 'core', $extraparameters = [])
    {
        $this->app['asset.queue.snippet']->add($location, $callback, $extensionname, (array) $extraparameters);
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function clearSnippetQueue()
    {
        $this->app['asset.queue.snippet']->clear();
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function processSnippetQueue($html)
    {
        return $this->app['asset.queue.snippet']->process($html);
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function processAssets($html)
    {
        return $this->app['asset.queue.file']->process($html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertStartOfHead($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::START_OF_HEAD, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertStartOfBody($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::START_OF_BODY, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertEndOfHead($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::END_OF_HEAD, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertEndOfBody($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::END_OF_BODY, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertEndOfHtml($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::END_OF_HTML, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertAfterMeta($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::AFTER_META, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertAfterCss($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::AFTER_CSS, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertBeforeCss($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::BEFORE_CSS, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertBeforeJS($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::BEFORE_JS, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertAfterJs($tag, $html, $insidehead = true)
    {
        return $this->app['asset.injector']->inject($tag, Target::AFTER_JS, $html, $insidehead);
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
            $this->menuoptions[$path] = [
                'label' => $label,
                'path'  => $path,
                'icon'  => $icon
            ];
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
        $context = [
            'event'     => 'extensions',
            'exception' => $e
        ];

        $this->app['logger.system']->addRecord($level, sprintf("%s for %s: %s", $msg, $extensionName, $e->getMessage()), $context);

        $this->app['logger.flash']->error(
            Trans::__("[Extension error] $msg for %ext%: %error%", ['%ext%' => $extensionName, '%error%' => $e->getMessage()])
        );
    }
}
