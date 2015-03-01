<?php
namespace Bolt\Configuration;

use Bolt\Application;
use Composer\Autoload\ClassLoader;
use Eloquent\Pathogen\AbsolutePathInterface;
use Eloquent\Pathogen\RelativePathInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * A Base Class to handle resource management of paths and urls within a Bolt App.
 *
 * Intended to simplify the ability to override resource location
 *
 * @author Ross Riley, riley.ross@gmail.com
 */
class ResourceManager
{
    /** @var \Bolt\Application */
    public $app;

    public $urlPrefix = "";

    /**
     * @var \Bolt\Application
     *
     * @deprecated Don't use! Will probably refactored out soon
     */
    public static $theApp;

    /** @var \Eloquent\Pathogen\AbsolutePathInterface */
    protected $root;

    /** @var Request */
    protected $requestObject;

    /** @var AbsolutePathInterface[] */
    protected $paths = array();

    protected $urls = array();

    /** @var string[] */
    protected $request = array();

    /** @var LowLevelChecks|null */
    protected $verifier;

    /** @var \Composer\Autoload\ClassLoader|null */
    protected $classLoader;

    /** @var \Eloquent\Pathogen\FileSystem\Factory\FileSystemPathFactory */
    protected $pathManager;

    /**
     * Constructor initialises on the app root path.
     *
     * @param \ArrayAccess $container ArrayAccess compatible DI container that must contain one of:
     *                                'classloader' of instance a ClassLoader will use introspection to find root path or
     *                                'rootpath' will be treated as an existing directory as string.
     *
     * Optional ones:
     * 'request' - Symfony\Component\HttpFoundation\Request
     */
    public function __construct(\ArrayAccess $container)
    {
        $this->pathManager = $container['pathmanager'];

        if (!empty($container['classloader']) && $container['classloader'] instanceof ClassLoader) {
            $this->root = $this->useLoader($container['classloader']);
        } else {
            $this->root = $this->setPath('root', $container['rootpath']);
        }

        if (!($container instanceof Application) && !empty($container['request'])) {
            $this->requestObject = $container['request'];
        }

        $this->setUrl('root', '/');

        $this->setUrl('app', '/app/');
        $this->setPath('apppath', 'app');

        $this->setUrl('extensions', '/extensions/');
        $this->setPath('extensionsconfig', 'app/config/extensions');
        $this->setPath('extensionspath', 'extensions');

        $this->setUrl('files', '/files/');
        $this->setPath('filespath', 'files');

        $this->setUrl('async', '/async/');
        $this->setUrl('upload', '/upload/');
        $this->setUrl('bolt', '/bolt/');
        $this->setUrl('theme', '/theme/');

        $this->setPath('web', '');
        $this->setPath('cache', 'app/cache');
        $this->setPath('config', 'app/config');
        $this->setPath('database', 'app/database');
        $this->setPath('themebase', 'theme');
    }

    public function useLoader(ClassLoader $loader)
    {
        $this->classLoader = $loader;
        $loaderPath = dirname($loader->findFile('Composer\\Autoload\\ClassLoader'));
        // Remove last vendor/* off loaderPath to get our root path
        list($rootPath) = explode('vendor', $loaderPath, -1);
        return $this->setPath('root', $rootPath);
    }

    public function setApp(Application $app)
    {
        $this->app = $app;
        ResourceManager::$theApp = $app;
    }

    public function setPath($name, $value)
    {
        // If this is a relative path make it relative to root.
        $path = $this->pathManager->create($value);
        if ($path instanceof RelativePathInterface) {
            $path = $path->resolveAgainst($this->paths['root']);
        }

        $this->paths[$name] = $path;
        if (strpos($name, "path") === false) {
            $this->paths[$name . "path"] = $path;
        }

        return $path;
    }

    /**
     * Gets a path as a string.
     *
     * Subdirectories are automatically parsed to correct filesystem.
     *
     * For example:
     *
     *     $bar = getPath('root/foo/bar');
     *
     * @param string $name Name of path
     *
     * @throws \InvalidArgumentException If path isn't available
     *
     * @return string
     */
    public function getPath($name)
    {
        return $this->getPathObject($name)->string();
    }

    /**
     * Gets a path as a PathInterface.
     *
     * Subdirectories are automatically parsed to correct filesystem.
     *
     * For example:
     *
     *     $bar = getPath('root/foo/bar');
     *
     * @param string $name Name of path
     *
     * @throws \InvalidArgumentException If path isn't available
     *
     * @return AbsolutePathInterface
     */
    public function getPathObject($name)
    {
        $parts = array();
        if (strpos($name, '/') !== false) {
            $parts = explode('/', $name);
            $name = array_shift($parts);
        }

        if (array_key_exists($name . "path", $this->paths)) {
            $path = $this->paths[$name . "path"];
        } elseif (array_key_exists($name, $this->paths)) {
            $path = $this->paths[$name];
        } else {
            throw new \InvalidArgumentException("Requested path $name is not available", 1);
        }

        if (!empty($parts)) {
            $path = $path->joinAtomSequence($parts);
        }

        return $path;
    }

    public function setUrl($name, $value)
    {
        $this->urls[$name] = $value;
    }

    public function getUrl($name)
    {
        if (array_key_exists($name . "url", $this->urls) && $name !== 'root') {
            return $this->urls[$name . "url"];
        }
        if (! array_key_exists($name, $this->urls)) {
            throw new \InvalidArgumentException("Requested url $name is not available", 1);
        }

        return $this->urlPrefix . $this->urls[$name];
    }

    public function setRequest($name, $value)
    {
        $this->request[$name] = $value;
    }

    public function getRequest($name)
    {
        if (! array_key_exists($name, $this->request)) {
            throw new \InvalidArgumentException("Request component $name is not available", 1);
        }

        return $this->request[$name];
    }

    /**
     * Returns merged array of Urls, Paths and current request.
     * However $this->paths can be either mixed array elements of String or Path
     * getPaths() will convert them string to provide homogeneous type result.
     *
     * @return string[] array of merged strings
     */
    public function getPaths()
    {
        $paths = array_map(
            function ($item) {
                return (string) $item;
            },
            $this->paths
        );

        return array_merge($paths, $this->urls, $this->request);
    }

    /**
     * Takes a Request object and uses it to initialize settings that depend on the request.
     *
     * @param Request $request
     */
    public function initializeRequest(Request $request = null)
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        // Set the current protocol. Default to http, unless otherwise.
        $protocol = "http";

        if (($request->server->get('HTTPS') == 'on') ||
            ($request->server->get('SERVER_PROTOCOL') == 'https') ||
            ($request->server->get('HTTP_X_FORWARDED_PROTO') == 'https') ||
            ($request->server->get('HTTP_X_FORWARDED_SSL') == 'on')) {
            $protocol = "https";
        } elseif ($request->server->get("SERVER_PROTOCOL") === null) {
            $protocol = "cli";
        }

        $rootUrl = $this->getUrl('root');
        if ($request->getBasePath() !== '') {
            $rootUrl = $request->getBasePath() . '/';
            $this->setUrl('root', $rootUrl);
            $this->setUrl('app', $rootUrl . 'app/');
            $this->setUrl('extensions', $rootUrl . 'extensions/');
            $this->setUrl('files', $rootUrl . 'files/');
            $this->setUrl('async', $rootUrl . 'async/');
            $this->setUrl('upload', $rootUrl . 'upload/');
        }

        $this->setRequest("protocol", $protocol);
        $hostname = $request->server->get('HTTP_HOST');
        $this->setRequest('hostname', $hostname);
        $current = $request->getPathInfo();
        $this->setUrl('current', $current);
        $this->setUrl('canonicalurl', sprintf('%s://%s%s', $protocol, $this->getRequest('canonical'), $current));
        $this->setUrl('currenturl', sprintf('%s://%s%s', $protocol, $hostname, $current));
        $this->setUrl('hosturl', sprintf('%s://%s', $protocol, $hostname));
        $this->setUrl('rooturl', sprintf('%s://%s%s', $protocol, $this->getRequest('canonical'), $rootUrl));
    }

    /**
     * Takes a Bolt Application and uses it to initialize settings that depend on the application config.
     *
     * @param \Bolt\Application $app
     */
    public function initializeApp(Application $app)
    {
        $canonical = $app['config']->get('general/canonical', "");
        $this->setRequest("canonical", $canonical);
    }

    /**
     * Takes a loaded config array and uses it to initialize settings that depend on it.
     *
     * @param array $config
     */
    public function initializeConfig($config)
    {
        if (is_array($config) && isset($config['general'])) {
            $this->setThemePath($config["general"]);
        }
    }

    public function initialize()
    {
        $this->initializeApp($this->app);
        $this->initializeRequest($this->requestObject);
        $this->postInitialize();
    }

    public function postInitialize()
    {
        $this->setThemePath($this->app['config']->get("general"));

        $theme = $this->app['config']->get('theme');
        if (isset($theme['template_directory'])) {
            $this->setPath('templatespath', $this->getPath('theme') . '/' . $this->app['config']->get('theme/template_directory'));
        } else {
            $this->setPath('templatespath', $this->getPath('theme'));
        }

        $branding = ltrim($this->app['config']->get('general/branding/path') . '/', '/');
        $this->setUrl("bolt", $this->getUrl('root') . $branding);
        $this->app['config']->setCkPath();
        $this->verifyDb();
    }

    public function compat()
    {
        if (! defined('BOLT_COMPOSER_INSTALLED')) {
            define('BOLT_COMPOSER_INSTALLED', false);
        }
        if (! defined("BOLT_PROJECT_ROOT_DIR")) {
            define('BOLT_PROJECT_ROOT_DIR', $this->getPath('root'));
        }
        if (! defined('BOLT_WEB_DIR')) {
            define('BOLT_WEB_DIR', $this->getPath('web'));
        }
        if (! defined('BOLT_CACHE_DIR')) {
            define('BOLT_CACHE_DIR', $this->getPath('cache'));
        }
        if (! defined('BOLT_CONFIG_DIR')) {
            define('BOLT_CONFIG_DIR', $this->getPath('config'));
        }
    }

    /**
     * This currently gets special treatment because of the processing order.
     * The theme path is needed before the app has constructed, so this is a shortcut to
     * allow the Application constructor to pre-provide a theme path.
     *
     * @param array $generalConfig
     */
    public function setThemePath($generalConfig)
    {
        $themeDir = isset($generalConfig['theme']) ? '/' . $generalConfig['theme'] : '';
        $themePath = isset($generalConfig['theme_path']) ? $generalConfig['theme_path'] : '/theme';
        $themeUrl = isset($generalConfig['theme_path']) ? $generalConfig['theme_path'] : $this->getUrl('root') . 'theme';

        // See if the user has set a theme path otherwise use the default
        if (!isset($generalConfig['theme_path'])) {
            $this->setPath('themepath', $this->getPath('themebase') . $themeDir);
            $this->setUrl('theme', $themeUrl . $themeDir . '/');
        } else {
            $this->setPath('themepath', $this->getPath('rootpath') . $themePath . $themeDir);
            $this->setUrl('theme', $themeUrl . $themeDir . '/');
        }
    }

    /**
     * Verifies the configuration to ensure that paths exist and are writable.
     */
    public function verify()
    {
        $this->getVerifier()->doChecks();
    }

    /**
     * Verify the database folder.
     */
    public function verifyDb()
    {
        $this->getVerifier()->doDatabaseCheck();
    }

    /**
     * Get the LowlevelChecks object.
     *
     * @return LowlevelChecks
     */
    public function getVerifier()
    {
        if (! $this->verifier) {
            $this->verifier = new LowlevelChecks($this);
        }

        return $this->verifier;
    }

    /**
     * Set the LowlevelChecks object.
     *
     * @param $verifier LowlevelChecks
     */
    public function setVerifier($verifier)
    {
        $this->verifier = $verifier;
    }

    /**
     * Get the Composer autoload ClassLoader.
     *
     * @return ClassLoader
     */
    public function getClassLoader()
    {
        return $this->classLoader;
    }

    /**
     * Get the Bolt\Application object.
     *
     * @throws \RuntimeException
     *
     * @return \Bolt\Application
     */
    public static function getApp()
    {
        if (! static::$theApp) {
            $trace = debug_backtrace(false);
            $trace = $trace[0]['file'] . '::' . $trace[0]['line'];
            $message = sprintf("The Bolt 'Application' object isn't initialized yet so the container can't be accessed here: <code>%s</code>", $trace);
            throw new \RuntimeException($message);
        }

        return static::$theApp;
    }

    /**
     * Find the relative file system path between two file system paths.
     *
     * @param string $frompath Path to start from
     * @param string $topath   Path we want to end up in
     *
     * @return string Path leading from $frompath to $topath
     */
    public function findRelativePath($frompath, $topath)
    {
        $filesystem = new Filesystem();
        $relative = $filesystem->makePathRelative($topath, $frompath);

        return $relative;
    }
}
