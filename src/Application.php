<?php

namespace Bolt;

use Bolt\Debug\ShutdownHandler;
use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Bolt\Provider\LoggerServiceProvider;
use Bolt\Provider\PathServiceProvider;
use Cocur\Slugify\Bridge\Silex\SlugifyServiceProvider;
use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Stopwatch;

class Application extends Silex\Application
{
    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        /** @internal Parameter to track a deprecated PHP version */
        $values['deprecated.php'] = version_compare(PHP_VERSION, '5.5.9', '<');

        // Register PHP shutdown functions to catch fatal errors & exceptions
        ShutdownHandler::register();

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

        $this['resources']->setApp($this);
        $this->initConfig();
        $this->initLogger();
        $this['resources']->initialize();

        if (($debugOverride = $this['config']->get('general/debug')) !== null) {
            $this['debug'] = $debugOverride;
        }

        // Re-register the shutdown functions now that we know our debug setting
        ShutdownHandler::register($this['debug']);
        if (!isset($this['environment'])) {
            $this['environment'] = $this['debug'] ? 'development' : 'production';
        }

        if (($locales = $this['config']->get('general/locale')) !== null) {
            $locales = (array) $locales;
            $this['locale'] = reset($locales);
        }

        // Initialize the JavaScript data gateway.
        $this['jsdata'] = [];
    }

    /**
     * {@inheritdoc}
     */
    public function run(Request $request = null)
    {
        if ($this['config']->get('general/caching/request')) {
            $this['http_cache']->run($request);
        } else {
            parent::run($request);
        }
    }

    protected function initConfig()
    {
        $this
            ->register(new Provider\FilesystemServiceProvider())
            ->register(new Provider\DatabaseSchemaServiceProvider())
            ->register(new Provider\ConfigServiceProvider())
        ;
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

        // Initialize the Database Providers.
        $this->initDatabase();

        // Initialize the rest of the Providers.
        $this->initProviders();

        // Initialize debugging
        $this->initDebugging();

        // Do a version check
        $this['config.environment']->checkVersion();

        // Initialize enabled extensions before executing handlers.
        $this->initExtensions();
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
                [
                    'http_cache.cache_dir' => $this['resources']->getPath('cache/' . $this['environment'] . '/http'),
                    'http_cache.options'   => $this['config']->get('general/performance/http_cache/options', []),
                ]
            );
    }

    /**
     * Set up the debugging if required.
     */
    public function initDebugging()
    {
        // Set the error_reporting to the level specified in config.yml
        if (($errorLevel = $this['config']->get($this['debug'] ? 'general/debug_error_level' : 'production_error_level')) !== null) {
            error_reporting($errorLevel);
        }

        $this->register(new Provider\DumperServiceProvider());

        if (!$this['debug']) {
            return;
        }

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
            ->register(new Provider\CanonicalServiceProvider())
        ;

        $this['paths'] = $this['resources']->getPaths();

        // Initialize stopwatch even if debug is not enabled.
        $this['stopwatch'] = $this->share(
            function () {
                return new Stopwatch\Stopwatch();
            }
        );
    }

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
}
