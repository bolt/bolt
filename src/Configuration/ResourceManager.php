<?php

namespace Bolt\Configuration;

use Bolt\Configuration\Validation\ValidatorInterface;
use Bolt\Pager\PagerManager;
use Composer\Autoload\ClassLoader;
use Eloquent\Pathogen\AbsolutePathInterface;
use Eloquent\Pathogen\RelativePathInterface;
use Silex\Application;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

/**
 * A Base Class to handle resource management of paths and urls within a Bolt App.
 *
 * Intended to simplify the ability to override resource location
 *
 * @deprecated Deprecated since 3.0, to be removed in 4.0.
 *
 * @author Ross Riley, riley.ross@gmail.com
 */
class ResourceManager
{
    /** @var \Silex\Application */
    public $app;

    /** @var string */
    public $urlPrefix = '';

    /**
     * @var \Silex\Application
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public static $theApp;

    /** @var \Eloquent\Pathogen\AbsolutePathInterface */
    protected $root;
    /** @var Request */
    protected $requestObject;
    /** @var AbsolutePathInterface[] */
    protected $paths = [];
    /** @var array */
    protected $urls = [];
    /** @var string[] */
    protected $request = [];
    /** @var LowLevelChecks|null */
    protected $verifier;
    /** @var \Composer\Autoload\ClassLoader|null */
    protected $classLoader;
    /** @var \Eloquent\Pathogen\FileSystem\Factory\FileSystemPathFactory */
    protected $pathManager;

    /**
     * @deprecated since 3.0, to be removed in 4.0.
     *
     * @var PathsProxy
     */
    private $pathsProxy;

    /** @var bool */
    private $requestInitialized;

    /** @var bool */
    private $configInitialized;

    /** @var PathResolver|null */
    private $pathResolver;
    /** @var PathResolverFactory */
    private $pathResolverFactory;

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

        if (isset($container['path_resolver'])) {
            $this->pathResolver = $container['path_resolver'];
        }

        if (isset($container['path_resolver_factory'])) {
            $this->pathResolverFactory = $container['path_resolver_factory'];
        }
        if ($this->pathResolverFactory === null) {
            $this->pathResolverFactory = new PathResolverFactory();
        }

        if (!empty($container['classloader']) && $container['classloader'] instanceof ClassLoader) {
            $this->root = $this->useLoader($container['classloader']);
        } else {
            $this->root = $this->setPath('root', $container['rootpath'], false);
        }
        if (!$this->pathResolverFactory->hasRootPath()) {
            $this->pathResolverFactory->setRootPath($this->root->string());
        }

        if (!($container instanceof Application) && !empty($container['request'])) {
            try {
                $this->requestObject = $container['request'];
            } catch (\RuntimeException $e) {
            }
        }

        $this->setUrl('root', '/');

        $this->setUrl('app', '/app/');
        $this->setPath('apppath', '%root%/app', false);

        $this->setUrl('extensions', '/extensions/');
        $this->setPath('extensionsconfig', '%config%/extensions', false);
        $this->setPath('extensionspath', '%root%/extensions', false);

        $this->setUrl('files', '/files/');
        $this->setPath('filespath', '%web%/files', false);

        $this->setUrl('async', '/async/');
        $this->setUrl('upload', '/upload/');
        $this->setUrl('bolt', '/bolt/');
        $this->setUrl('theme', '/theme/');
        $this->setUrl('themes', '/theme/'); // Needed for filebrowser. See #5759

        $this->setPath('web', 'public', false);
        $this->setPath('cache', 'app/cache', false);
        $this->setPath('config', 'app/config', false);
        $this->setPath('src', dirname(__DIR__), false);
        $this->setPath('database', 'app/database', false);
        $this->setPath('themebase', '%web%/theme', false);
        $this->setPath('view', '%web%/bolt-public/view', false);
        $this->setUrl('view', '/app/view/');
    }

    /**
     * @param \Composer\Autoload\ClassLoader $loader
     *
     * @return \Eloquent\Pathogen\RelativePathInterface|\Eloquent\Pathogen\AbsolutePathInterface
     */
    public function useLoader(ClassLoader $loader)
    {
        $this->classLoader = $loader;
        $loaderPath = dirname($loader->findFile('Composer\\Autoload\\ClassLoader'));
        // Remove last vendor/* off loaderPath to get our root path
        list($rootPath) = explode('vendor', $loaderPath, -1);

        return $this->setPath('root', $rootPath);
    }

    /**
     * Don't use!
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param Application $app
     */
    public function setApp(Application $app)
    {
        $this->app = $app;
        self::$theApp = $app;
        $this->pathResolver = $app['path_resolver'];
    }

    /**
     * Set a resource path.
     *
     * @param string $name
     * @param string $value
     * @param bool   $applyToResolver
     *
     * @return AbsolutePathInterface|RelativePathInterface|\Closure
     */
    public function setPath($name, $value, $applyToResolver = true)
    {
        if (strpos($value, '%') !== false) { // contains variable
            $path = function () use ($value) {
                if (!$this->pathResolver) {
                    throw new \LogicException(sprintf('Cannot resolve path "%s" without having a path resolver.', $value));
                }

                $path = $this->pathResolver->resolve($value);

                return $this->pathManager->create($path);
            };
        } else {
            // If this is a relative path make it relative to root.
            $path = $this->pathManager->create($value);
            if ($path instanceof RelativePathInterface) {
                $path = $path->resolveAgainst($this->paths['root']);
            }

            $path = $path->normalize();
        }

        $this->paths[$name] = $path;
        if (strpos($name, 'path') === false) {
            $this->paths[$name . 'path'] = $path;
        }

        if ($applyToResolver) {
            if ($this->pathResolver) {
                $this->pathResolver->define($name, $value);
            } else {
                $this->pathResolverFactory->addPaths([$name => $value]);
            }
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
        $name = str_replace('\\', '/', $name);

        $parts = [];
        if (strpos($name, '/') !== false) {
            $parts = explode('/', $name);
            $name = array_shift($parts);
        }

        if (!$this->configInitialized && in_array($name, ['theme', 'themepath', 'templates', 'templatespath'])) {
            $this->initializeConfig();
        }

        if (array_key_exists($name . 'path', $this->paths)) {
            $path = $this->paths[$name . 'path'];
        } elseif (array_key_exists($name, $this->paths)) {
            $path = $this->paths[$name];
        } else {
            throw new \InvalidArgumentException("Requested path $name is not available", 1);
        }

        if (is_callable($path)) {
            $path = $path();
        }

        if (!empty($parts)) {
            $path = $path->joinAtomSequence($parts);
        }

        return $path;
    }

    /**
     * Checks if the given name has a path associated with it
     *
     * @param string $name of path
     *
     * @return Boolean
     */
    public function hasPath($name)
    {
        if (strpos($name, '/') !== false) {
            $parts = explode('/', $name);
            $name = array_shift($parts);
        }

        return array_key_exists($name, $this->paths) || array_key_exists($name . 'path', $this->paths);
    }

    public function getPathResolverFactory()
    {
        return $this->pathResolverFactory;
    }

    public function setPathResolver(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }

    /**
     * Set a URL path definition.
     *
     * @param string $name
     * @param string $value
     */
    public function setUrl($name, $value)
    {
        $this->urls[$name] = $value;
    }

    /**
     * Get a URL path definition.
     *
     * @param string $name
     * @param bool   $includeBasePath
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getUrl($name, $includeBasePath = true)
    {
        if (($name === 'canonical' || $name === 'canonicalurl') && isset($this->app['canonical'])) {
            if ($url = $this->app['canonical']->getUrl()) {
                return $url;
            }
        }

        if (!$this->requestInitialized && $this->app) {
            $this->initializeRequest($this->app, $this->requestObject);
        }

        if (!$this->configInitialized && in_array($name, ['theme', 'bolt', 'templates', 'templatespath'])) {
            $this->initializeConfig();
        }

        if (array_key_exists($name . 'url', $this->urls) && $name !== 'root') {
            return $this->urls[$name . 'url'];
        }
        if (! array_key_exists($name, $this->urls)) {
            throw new \InvalidArgumentException("Requested url $name is not available", 1);
        }

        $url = $this->urls[$name];

        if (!$includeBasePath || strpos($url, 'http') === 0 || strpos($url, '//') === 0) {
            return $url;
        }

        return $this->urlPrefix . $url;
    }

    /**
     * Set a parameter that describes the request.
     * e.g. 'hostname', 'protocol' or 'canonical'
     *
     * @param string $name
     * @param string $value
     */
    public function setRequest($name, $value)
    {
        $this->request[$name] = $value;
    }

    /**
     * Get a request parameter.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getRequest($name)
    {
        if (!$this->requestInitialized && in_array($name, ['canonical', 'protocol', 'hostname'])) {
            $this->initializeRequest($this->app, $this->requestObject);
        }

        if (! array_key_exists($name, $this->request)) {
            throw new \InvalidArgumentException("Request component $name is not available", 1);
        }

        return $this->request[$name];
    }

    /**
     * Just don't use this.
     *
     * @deprecated since 3.0, to be removed in 4.0.
     *
     * @return PathsProxy
     */
    public function getPaths()
    {
        if ($this->pathsProxy === null) {
            $this->pathsProxy = new PathsProxy($this);
        }

        return $this->pathsProxy;
    }

    /**
     * Takes a Request object and uses it to initialize settings that depend on
     * the request.
     *
     * @param Application $app
     * @param Request     $request
     */
    public function initializeRequest(Application $app, Request $request = null)
    {
        if ($request === null) {
            $request = $app['request_stack']->getCurrentRequest() ?: Request::createFromGlobals();
        }

        // This is where we set the canonical. Note: The protocol (scheme) defaults to 'http',
        // and the path is discarded, as it makes no sense in this context: Bolt always
        // determines the path for a page / record. This is not the canonical's job.
        $canonical = $app['config']->get('general/canonical', $request->server->get('HTTP_HOST'));
        if (strpos($canonical, 'http') !== 0) {
            $canonical = 'http://' . $canonical;
        }

        $canonical = parse_url($canonical);

        if (empty($canonical['host'])) {
            $canonical['host'] = $request->server->get('HTTP_HOST');
        }

        if (empty($canonical['scheme'])) {
            $canonical['scheme'] = 'http';
        }

        if (($request->server->get('HTTPS') == 'on') ||
            ($request->server->get('SERVER_PROTOCOL') == 'https') ||
            ($request->server->get('HTTP_X_FORWARDED_PROTO') == 'https') ||
            ($request->server->get('HTTP_X_FORWARDED_SSL') == 'on')) {
            $canonical['scheme'] = 'https';
        }

        $this->setRequest('canonical', sprintf('%s://%s', $canonical['scheme'], $canonical['host']));

        $rootUrl = rtrim($this->urls['root'], '/');
        if ($rootUrl !== $request->getBasePath()) {
            $this->urlPrefix = $request->getBasePath();
        }

        $this->setRequest('protocol', $canonical['scheme']);
        $hostname = $request->server->get('HTTP_HOST', 'localhost');
        $this->setRequest('hostname', $hostname);
        $current = $request->getBasePath() . $request->getPathInfo();
        $this->setUrl('current', $current);
        $this->setUrl('currenturl', sprintf('%s://%s%s', $canonical['scheme'], $hostname, $current));
        $this->setUrl('hosturl', sprintf('%s://%s', $canonical['scheme'], $hostname));
        $this->setUrl('rooturl', sprintf('%s%s/', $this->request['canonical'], $rootUrl));

        $url = sprintf('%s%s', $this->request['canonical'], $current);
        if (PagerManager::isPagingRequest($request)) {
            $url .= '?' . http_build_query($request->query->all());
        }
        $this->setUrl('canonicalurl', $url);

        $this->requestInitialized = true;
    }

    /**
     * Takes a loaded config array and uses it to initialize settings that depend on it.
     */
    public function initializeConfig()
    {
        $this->configInitialized = true;

        $this->setThemePath($this->app['config']->get('general'));

        $theme = $this->app['config']->get('theme');
        if (isset($theme['template_directory'])) {
            $this->setPath('templatespath', $this->getPath('theme') . '/' . $theme['template_directory']);
        } else {
            $this->setPath('templatespath', $this->getPath('theme'));
        }

        $branding = '/' . trim($this->app['config']->get('general/branding/path'), '/') . '/';
        $this->setUrl('bolt', $branding);
    }

    public function initialize()
    {
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
        $themeUrl = isset($generalConfig['theme_path']) ? $generalConfig['theme_path'] : '/theme';

        // See if the user has set a theme path otherwise use the default
        if (!isset($generalConfig['theme_path'])) {
            $this->setPath('themepath', $this->getPath('themebase') . $themeDir);
            $this->setUrl('theme', $themeUrl . $themeDir . '/');
        } else {
            $this->setPath('themepath', $this->getPath('root') . $themePath . $themeDir);
            $this->setUrl('theme', $themeUrl . $themeDir . '/');
        }
    }

    /**
     * @deprecated Deprecated since 3.1, to be removed in 4.0.
     */
    public function verify()
    {
    }

    /**
     * @deprecated Deprecated since 3.1, to be removed in 4.0.
     */
    public function verifyDb()
    {
    }

    /**
     * Get the LowlevelChecks object.
     *
     * @return ValidatorInterface
     */
    public function getVerifier()
    {
        if (! $this->verifier) {
            $verifier = new LowlevelChecks($this);
            $this->verifier = $verifier;
        }

        return $this->verifier;
    }

    /**
     * Set the LowlevelChecks object.
     *
     * @param ValidatorInterface|null $verifier
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
     * @return \Silex\Application
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
