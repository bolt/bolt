<?php

namespace Bolt;

use Bolt\Common\Deprecated;
use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Bolt\Legacy\AppSingleton;
use Bolt\Provider\LoggerServiceProvider;
use Bolt\Provider\PathServiceProvider;
use Cocur\Slugify\Bridge\Silex2\SlugifyServiceProvider;
use Silex;
use Symfony\Component\HttpFoundation\Request;

class Application extends Silex\Application
{
    /** @var bool */
    protected $initialized;

    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        parent::__construct();

        /** @deprecated since 3.3, to be removed in 4.0. */
        AppSingleton::set($this);

        /** @internal Parameter to track a deprecated PHP version */
        $values['deprecated.php'] = version_compare(PHP_VERSION, '7.0.8', '<');

        // Debug 1st phase: Register early error & exception handlers
        $this->register(new Provider\DebugServiceProvider());

        /*
         * Extensions registration phase is actually during subscribe(), but
         * since it is the first SP to register it acts like a late
         * registration. However, services needed by Extension Manager cannot
         * be modified.
         */
        $this->register(new Provider\ExtensionServiceProvider());

        // Debug 2nd phase: Modify handlers with values from config
        $this->register(new Provider\DebugServiceProvider(false));

        $this->register(new PathServiceProvider());

        $this->initConfig();
        $this->initLogger();

        // Initialize the JavaScript data gateway.
        $this['jsdata'] = [];

        $this->initialize();

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run(Request $request = null)
    {
        if (!$this->booted) {
            $this->boot();
        }

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
        if ($this->initialized) {
            Deprecated::method(3.3);

            return;
        }
        $this->initialized = true;

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
            ->register(new Silex\Provider\HttpCacheServiceProvider())
        ;
        $this['http_cache.cache_dir'] = function () {
            return $this['path_resolver']->resolve('%cache%/' . $this['environment'] . '/http');
        };
        $this['http_cache.options'] = function () {
            return $this['config']->get('general/performance/http_cache/options', []);
        };
    }

    /**
     * Set up the debugging if required.
     */
    public function initDebugging()
    {
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
            ->register(new Provider\ValidatorServiceProvider())
            ->register(new Provider\RoutingServiceProvider())
            ->register(new Silex\Provider\ServiceControllerServiceProvider()) // must be after Routing
            ->register(new Silex\Provider\CsrfServiceProvider())
            ->register(new Provider\SecurityServiceProvider())
            ->register(new Provider\RandomGeneratorServiceProvider())
            ->register(new Provider\PermissionsServiceProvider())
            ->register(new Provider\StorageServiceProvider())
            ->register(new Provider\QueryServiceProvider())
            ->register(new Provider\AccessControlServiceProvider())
            ->register(new Provider\UsersServiceProvider())
            ->register(new Provider\CacheServiceProvider())
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
            ->register(new Provider\EmbedServiceProvider())
        ;

        // Initialize our friendly helpers, if available.
        if (class_exists('\Bolt\Starter\Provider\StarterProvider')) {
            $this->register(new \Bolt\Starter\Provider\StarterProvider());
        }
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
}
