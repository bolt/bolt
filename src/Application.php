<?php

namespace Bolt;

use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Bolt\Provider\LoggerServiceProvider;
use Bolt\Provider\PathServiceProvider;
use Bolt\Provider\WhoopsServiceProvider;
use Cocur\Slugify\Bridge\Silex\SlugifyServiceProvider;
use Silex;
use Symfony\Component\Stopwatch;

class Application extends Silex\Application
{
    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use $app['locale_fallbacks'] instead.
     */
    const DEFAULT_LOCALE = 'en_GB';

    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        /** @deprecated since 3.0, to be removed in 4.0. */
        $values['bolt_version'] = Version::VERSION;
        /** @deprecated since 3.0, to be removed in 4.0. */
        $values['bolt_name'] = Version::name();
        /** @deprecated since 3.0, to be removed in 4.0. */
        $values['bolt_released'] = Version::isStable();
        /** @deprecated since 3.0, to be removed in 4.0. */
        $values['bolt_long_version'] = Version::VERSION;

        /** @internal Parameter to track a deprecated PHP version */
        $values['deprecated.php'] = version_compare(PHP_VERSION, '5.5.9', '<');

        parent::__construct($values);

        $this->register(new PathServiceProvider());

        // Initialize the config. Note that we do this here, on 'construct'.
        // All other initialisation is triggered from bootstrap.php
        // Warning!
        // One of a valid ResourceManager ['resources'] or ClassLoader ['classloader']
        // must be defined for working properly
        if (!isset($this['resources'])) {
            $this['resources'] = new Configuration\ResourceManager($this);
        } else {
            $this['classloader'] = $this['resources']->getClassLoader();
        }

        // Register a PHP shutdown function to catch fatal errors with the application object
        register_shutdown_function(['\Bolt\Exception\LowlevelException', 'catchFatalErrors'], $this);

        $this['resources']->setApp($this);
        $this->initConfig();
        $this->initLogger();
        $this['resources']->initialize();

        $this['debug'] = $this['config']->get('general/debug', false);

        $locales = (array) $this['config']->get('general/locale');
        $this['locale'] = reset($locales);

        // Initialize the 'editlink' and 'edittitle'.
        $this['editlink'] = '';
        $this['edittitle'] = '';

        // Initialize the JavaScript data gateway.
        $this['jsdata'] = [];
    }

    protected function initConfig()
    {
        $this->register(new Provider\DatabaseSchemaServiceProvider())
            ->register(new Provider\ConfigServiceProvider())
        ;
        $this['config']->initialize();
    }

    protected function initSession()
    {
        $this
            ->register(new Provider\SessionServiceProvider())
        ;
    }

    public function initialize()
    {
        // Set up session handling
        $this->initSession();

        // Set up locale and translations.
        $this->initLocale();

        // Initialize Twig and our rendering Provider.
        $this->initRendering();

        // Initialize debugging
        $this->initDebugging();

        // Initialize the Database Providers.
        $this->initDatabase();

        // Initialize the rest of the Providers.
        $this->initProviders();

        // Do a version check
        $this['config.environment']->checkVersion();

        // Calling for BC. Controllers are mounted in ControllerServiceProvider now.
        $this->initMountpoints();

        // Initialize enabled extensions before executing handlers.
        $this->initExtensions();

        // Initialize the global 'before' handler.
        $this->before([$this, 'beforeHandler']);

        // Initialize the global 'after' handler.
        $this->after([$this, 'afterHandler']);

        // Calling for BC. Initialize the 'error' handler.
        $this->error([$this, 'errorHandler']);
    }

    /**
     * Initialize the loggers.
     */
    public function initLogger()
    {
        $this->register(new LoggerServiceProvider(), []);
    }

    /**
     * Initialize the database providers.
     */
    public function initDatabase()
    {
        $this->register(new Provider\DatabaseServiceProvider());
        $this->checkDatabaseConnection();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    protected function checkDatabaseConnection()
    {
    }

    /**
     * Initialize the rendering providers.
     */
    public function initRendering()
    {
        $this
            ->register(new Provider\TwigServiceProvider())
            ->register(new Provider\RenderServiceProvider())
            ->register(new Silex\Provider\HttpCacheServiceProvider(),
                ['http_cache.cache_dir' => $this['resources']->getPath('cache')]
            );
    }

    /**
     * Set up the debugging if required.
     */
    public function initDebugging()
    {
        if (!$this['debug']) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED);

            return;
        }

        // Set the error_reporting to the level specified in config.yml
        error_reporting($this['config']->get('general/debug_error_level'));

        // Register Whoops, to handle errors for logged in users only.
        if ($this['config']->get('general/debug_enable_whoops')) {
            $this->register(new WhoopsServiceProvider());
        }

        $this->register(new Provider\DumperServiceProvider());

        // Initialize Web Profiler providers
        $this->initProfiler();
    }

    /**
     * Set up the profilers for the toolbar.
     */
    public function initProfiler()
    {
        $this->register(new Provider\ProfilerServiceProvider());
    }

    public function initLocale()
    {
        $this->register(new Provider\TranslationServiceProvider());
    }

    public function initProviders()
    {
        $this
            ->register(new Silex\Provider\HttpFragmentServiceProvider())
            ->register(new Silex\Provider\UrlGeneratorServiceProvider())
            ->register(new Silex\Provider\ValidatorServiceProvider())
            ->register(new Provider\RoutingServiceProvider())
            ->register(new Silex\Provider\ServiceControllerServiceProvider()) // must be after Routing
            ->register(new Provider\RandomGeneratorServiceProvider())
            ->register(new Provider\PermissionsServiceProvider())
            ->register(new Provider\StorageServiceProvider())
            ->register(new Provider\QueryServiceProvider())
            ->register(new Provider\AccessControlServiceProvider())
            ->register(new Provider\UsersServiceProvider())
            ->register(new Provider\CacheServiceProvider())
            ->register(new Provider\ExtensionServiceProvider())
            ->register(new Provider\StackServiceProvider())
            ->register(new Provider\OmnisearchServiceProvider())
            ->register(new Provider\TemplateChooserServiceProvider())
            ->register(new Provider\CronServiceProvider())
            ->register(new Provider\FilePermissionsServiceProvider())
            ->register(new Provider\MenuServiceProvider())
            ->register(new Provider\UploadServiceProvider())
            ->register(new Provider\FilesystemServiceProvider())
            ->register(new Provider\ThumbnailsServiceProvider())
            ->register(new Provider\NutServiceProvider())
            ->register(new Provider\GuzzleServiceProvider())
            ->register(new Provider\PrefillServiceProvider())
            ->register(new SlugifyServiceProvider())
            ->register(new Provider\MarkdownServiceProvider())
            ->register(new Provider\ControllerServiceProvider())
            ->register(new Provider\EventListenerServiceProvider())
            ->register(new Provider\AssetServiceProvider())
            ->register(new Provider\FormServiceProvider())
            ->register(new Provider\MailerServiceProvider())
            ->register(new Provider\PagerServiceProvider())
        ;

        $this['paths'] = $this['resources']->getPaths();

        // Initialize stopwatch even if debug is not enabled.
        $this['stopwatch'] = $this->share(
            function () {
                return new Stopwatch\Stopwatch();
            }
        );
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see ControllerEvents::MOUNT} instead.
     */
    public function initExtensions()
    {
        $this['extensions']->addManagedExtensions();
        $this['extensions']->register($this);
    }

    /**
     * {@inheritdoc}
     */
    public function mount($prefix, $controllers)
    {
        if (!$this->booted) {
            // Forward call to mount event if we can (which handles prioritization).
            $this->on(
                ControllerEvents::MOUNT,
                function (MountEvent $event) use ($prefix, $controllers) {
                    $event->mount($prefix, $controllers);
                }
            );
        } else {
            // Already missed mounting event just append it to bottom of controller list
            parent::mount($prefix, $controllers);
        }

        return $this;
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use {@see ControllerEvents::MOUNT} instead.
     */
    public function initMountpoints()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function beforeHandler()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function afterHandler()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function errorHandler()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string $name
     *
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this[$name]);
    }

    /**
     * Get the Bolt version string
     *
     * @return string
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *             Use parameters in application instead
     */
    public function getVersion()
    {
        return Version::VERSION;
    }

    /**
     * Generates a path from the given parameters.
     *
     * @param string $route      The name of the route
     * @param array  $parameters An array of parameters
     *
     * @return string The generated path
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *             Use {@see \Symfony\Component\Routing\Generator\UrlGeneratorInterface} instead.
     */
    public function generatePath($route, $parameters = [])
    {
        return $this['url_generator']->generate($route, $parameters);
    }
}
