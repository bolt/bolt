<?php
namespace Bolt\Configuration;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Composer\Autoload\ClassLoader;
use Symfony\Component\Filesystem\Filesystem;

/**
 * A Base Class to handle resource management of paths and urls within a Bolt App.
 *
 * Intended to simplify the ability to override resource location
 *
 *
 * @author Ross Riley, riley.ross@gmail.com
 *
 * @property \Composer\Autoload\ClassLoader $classloader
 * @property \Bolt\Application $app
 * @property \Symfony\Component\HttpFoundation\Request $requestObject
 * @property \Eloquent\Pathogen\FileSystem\Factory\FileSystemPathFactory $pathManager
 */
class ResourceManager
{

    public $app;

    public $urlPrefix = "";

    /**
     * Don't use! Will probably refactored out soon
     */
    protected static $theApp;

    protected $root;

    protected $requestObject;

    protected $paths = array();

    protected $urls = array();

    protected $request = array();

    protected $verifier = false;

    protected $classLoader;

    protected $pathManager;

    /**
     * Constructor initialises on the app root path.
     *
     * @param \ArrayAccess $container
     * ArrayAccess compatible DI container that must contain one of:
     * 'classloader' of instance a ClassLoader will use introspection to find root path or
     * 'rootpath' will be treated as an existing directory as string.
     *
     * Optional ones:
     * 'request' - Symfony\Component\HttpFoundation\Request
     * 'verifier' - LowLevelChecks verifier
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

        if (!empty($container['verifier'])) {
            $this->verifier = $container['verifier'];
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
        $ldpath = dirname($loader->findFile('Composer\\Autoload\\ClassLoader'));
        $expath = explode('vendor', $ldpath);
        array_pop($expath);

        return $this->setPath('root', join('vendor', $expath));
    }

    /*
     * Setters
     */

    public function setApp(Application $app)
    {
        static::$theApp = $this->app = $app;
    }

    public function setPath($name, $value)
    {
        if (! preg_match("/^(?:\/|\\\\|\w:\\\\|\w:\/).*$/", $value)) {
            $path = $this->pathManager->create($value);
            $path = $this->paths['root']->resolve($path);
        } else {
            $path = $this->pathManager->create($value);
        }

        $this->paths[$name] = $path;
        if (strpos($name, "path") === false) {
            $this->paths[$name . "path"] = $path;
        }

        return $path;
    }

    public function getPath($name)
    {
        if (array_key_exists($name . "path", $this->paths)) {
            return $this->paths[$name . "path"]->string();
        }

        if (! array_key_exists($name, $this->paths)) {
            throw new \InvalidArgumentException("Requested path $name is not available", 1);
        }

        return $this->paths[$name];
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
            throw new \InvalidArgumentException("Request componenet $name is not available", 1);
        }

        return $this->request[$name];
    }

    /**
     * Returns merged array of Urls, Paths and current request.
     * However $this->paths can be either mixed array elements of String or Path
     * getPaths() will convert them string to provide homogeneous type result.
     *
     * @return array String array of merge
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
     * Takes a Request object and uses it to initialize settings that depend on the request
     *
     * @return void
     *
     */
    public function initializeRequest(Request $request = null)
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        // Set the current protocol. Default to http, unless otherwise..
        $protocol = "http";

        if (($request->server->get('HTTPS') == 'on') || 
            ($request->server->get('SERVER_PROTOCOL') == 'https') || 
            ($request->server->get('HTTP_X_FORWARDED_PROTO') == 'https') || 
            ($request->server->get('HTTP_X_FORWARDED_SSL') == 'on')) {
            $protocol = "https";
        } elseif ($request->server->get("SERVER_PROTOCOL") == null) {
            $protocol = "cli";
        }

        if ($request->getBasePath() !== "") {
            $this->setUrl('root', $request->getBasePath() . "/");
            $this->setUrl("app", $this->getUrl('root') . "app/");
            $this->setUrl("extensions", $this->getUrl('root') . "extensions/");
            $this->setUrl("files", $this->getUrl('root') . "files/");
            $this->setUrl("async", $this->getUrl('root') . "async/");
            $this->setUrl("upload", $this->getUrl('root') . "upload/");
        }

        $this->setRequest("protocol", $protocol);
        $this->setRequest("hostname", $request->server->get('HTTP_HOST'));
        $this->setUrl("current", $request->getPathInfo());
        $this->setUrl("canonicalurl", sprintf('%s://%s%s', $this->getRequest("protocol"), $this->getRequest('canonical'), $this->getUrl('current')));
        $this->setUrl("currenturl", sprintf('%s://%s%s', $this->getRequest("protocol"), $this->getRequest('hostname'), $this->getUrl('current')));
        $this->setUrl("hosturl", sprintf('%s://%s', $this->getRequest("protocol"), $this->getRequest('hostname')));
        $this->setUrl("rooturl", sprintf('%s://%s%s', $this->getRequest("protocol"), $this->getRequest('canonical'), $this->getUrl("root")));
    }

    /**
     * Takes a Bolt Application and uses it to initialize settings that depend on the application config
     *
     * @return void
     *
     */
    public function initializeApp(Application $app)
    {
        $canonical = $app['config']->get('general/canonical', "");
        $this->setRequest("canonical", $canonical);
    }

    /**
     * Takes a loaded config array and uses it to initialize settings that depend on it
     *
     * @return void
     *
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
        $branding = ltrim($this->app['config']->get('general/branding/path') . '/', '/');
        $this->setUrl("bolt", $this->getUrl('root') . $branding);
        $this->app['config']->setCkPath();
        $this->verifyDb();
    }

    public function compat()
    {
        if (! defined("BOLT_COMPOSER_INSTALLED")) {
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
     * @return void
     *
     */
    public function setThemePath($generalConfig)
    {
        $theme_dir = isset($generalConfig['theme']) ? '/' . $generalConfig['theme'] : '';
        $theme_path = isset($generalConfig['theme_path']) ? $generalConfig['theme_path'] : '/theme';
        $theme_url = isset($generalConfig['theme_path']) ? $generalConfig['theme_path'] : $this->getUrl('root') . 'theme';

        // See if the user has set a theme path otherwise use the default
        if (!isset($generalConfig['theme_path'])) {
            $this->setPath('themepath', $this->getPath('themebase') . $theme_dir);
            $this->setUrl('theme', $theme_url . $theme_dir . '/');
        } else {
            $this->setPath('themepath', $this->getPath('rootpath') . $theme_path . $theme_dir);
            $this->setUrl('theme', $theme_url . $theme_dir . '/');
        }
    }

    /**
     * Verifies the configuration to ensure that paths exist and are writable.
     *
     * @return void
     * @author
     *
     */
    public function verify()
    {
        $this->getVerifier()->doChecks();
    }

    public function verifyDb()
    {
        $this->getVerifier()->doDatabaseCheck();
    }

    public function getVerifier()
    {
        if (! $this->verifier) {
            $this->verifier = new LowlevelChecks($this);
        }

        return $this->verifier;
    }

    public function getClassLoader()
    {
        return $this->classLoader;
    }

    public static function getApp()
    {
        if (! static::$theApp) {
            $message = sprintf("The Bolt 'Application' object isn't initialized yet so the container can't be accessed here: <code>%s</code>", htmlspecialchars(debug_backtrace(), ENT_QUOTES));
            throw new LowlevelException($message);
        }

        return static::$theApp;
    }

    /**
    *
    * Find the relative file system path between two file system paths
    *
    * @param string $frompath Path to start from
    * @param string $topath Path we want to end up in
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
