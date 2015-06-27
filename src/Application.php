<?php

namespace Bolt;

use Bolt\Debug\DebugToolbarEnabler;
use Bolt\Exception\LowlevelException;
use Bolt\Helpers\Str;
use Bolt\Provider\LoggerServiceProvider;
use Bolt\Provider\PathServiceProvider;
use Bolt\Provider\WhoopsServiceProvider;
use Cocur\Slugify\Bridge\Silex\SlugifyServiceProvider;
use Doctrine\DBAL\DBALException;
use RandomLib;
use SecurityLib;
use Silex;
use Symfony\Component\Stopwatch;

class Application extends Silex\Application
{
    /**
     * The default locale, used as fallback.
     */
    const DEFAULT_LOCALE = 'en_GB';

    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $values['bolt_version'] = '2.3.0';
        $values['bolt_name'] = 'alpha 1';
        $values['bolt_released'] = false; // `true` for stable releases, `false` for alpha, beta and RC.

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
            $this['resources']->compat();
        } else {
            $this['classloader'] = $this['resources']->getClassLoader();
        }

        $this['resources']->setApp($this);
        $this->initConfig();
        $this->initLogger();
        $this['resources']->initialize();

        $this['debug'] = $this['config']->get('general/debug', false);

        // Initialize the 'editlink' and 'edittitle'.
        $this['editlink'] = '';
        $this['edittitle'] = '';

        // Initialise the JavaScipt data gateway
        $this['jsdata'] = [];
    }

    protected function initConfig()
    {
        $this->register(new Provider\DatabaseSchemaProvider())
            ->register(new Provider\ConfigServiceProvider());
    }

    protected function initSession()
    {
        $this->register(new Provider\TokenServiceProvider())
            ->register(new Silex\Provider\SessionServiceProvider(), [
                'session.storage.options' => [
                    'name'            => $this['token.session.name'],
                    'cookie_path'     => $this['resources']->getUrl('root'),
                    'cookie_domain'   => $this['config']->get('general/cookies_domain'),
                    'cookie_secure'   => $this['config']->get('general/enforce_ssl'),
                    'cookie_httponly' => true
                ],
                'session.test' => isset($this['session.test']) ? $this['session.test'] : false
            ]
        );

        // Disable Silex's built-in native filebased session handler, and fall back to
        // whatever's set in php.ini.
        // @see: http://silex.sensiolabs.org/doc/providers/session.html#custom-session-configurations
        if ($this['config']->get('general/session_use_storage_handler') === false) {
            $this['session.storage.handler'] = null;
        }
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

        // Calling for BC. Controllers are mounted in ControllerServiceProvider now.
        $this->initMountpoints();

        // Initialize enabled extensions before executing handlers.
        $this->initExtensions();

        // Initialise the global 'before' handler.
        $this->before([$this, 'beforeHandler']);

        // Initialise the global 'after' handler.
        $this->after([$this, 'afterHandler']);

        // Calling for BC. Initialise the 'error' handler.
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
        $this->register(
            new Silex\Provider\DoctrineServiceProvider(),
            [
                'db.options' => $this['config']->get('general/database')
            ]
        );
        $this->register(new Storage\Database\InitListener());

        $this->checkDatabaseConnection();

        $this->register(
            new Silex\Provider\HttpCacheServiceProvider(),
            ['http_cache.cache_dir' => $this['resources']->getPath('cache')]
        );
    }

    /**
     * Set up the DBAL connection now to check for a proper connection to the database.
     *
     * @throws LowlevelException
     */
    protected function checkDatabaseConnection()
    {
        // [SECURITY]: If we get an error trying to connect to database, we throw a new
        // LowLevelException with general information to avoid leaking connection information.
        try {
            $this['db']->connect();
        // A ConnectionException or DriverException could be thrown, we'll catch DBALException to be safe.
        } catch (DBALException $e) {
            // Trap double exceptions caused by throwing a new LowlevelException
            set_exception_handler(['\Bolt\Exception\LowlevelException', 'nullHandler']);

            /*
             * Using Driver here since Platform may try to connect
             * to the database, which has failed since we are here.
             */
            $platform = $this['db']->getDriver()->getName();
            $platform = Str::replaceFirst('pdo_', '', $platform);

            $error = "Bolt could not connect to the configured database.\n\n" .
                     "Things to check:\n" .
                     "&nbsp;&nbsp;* Ensure the $platform database is running\n" .
                     "&nbsp;&nbsp;* Check the <code>database:</code> parameters are configured correctly in <code>app/config/config.yml</code>\n" .
                     "&nbsp;&nbsp;&nbsp;&nbsp;* Database name is correct\n" .
                     "&nbsp;&nbsp;&nbsp;&nbsp;* User name has access to the named database\n" .
                     "&nbsp;&nbsp;&nbsp;&nbsp;* Password is correct\n";
            throw new LowlevelException($error);
        }

        // Resume normal error handling
        restore_error_handler();
    }

    /**
     * Initialize the rendering providers.
     */
    public function initRendering()
    {
        $this->register(new Provider\TwigServiceProvider());
        $this->register(new Provider\SafeTwigServiceProvider());

        $this->register(new Provider\RenderServiceProvider());
        $this->register(new Provider\RenderServiceProvider(true));
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

        // Initialize Web Profiler providers
        $this->initProfiler();
    }

    /**
     * Set up the profilers for the toolbar.
     */
    public function initProfiler()
    {
        // Register the Silex/Symfony web debug toolbar.
        $this->register(
            new Silex\Provider\WebProfilerServiceProvider(),
            [
                'profiler.cache_dir'                => $this['resources']->getPath('cache') . '/profiler',
                'profiler.mount_prefix'             => '/_profiler', // this is the default
                'web_profiler.debug_toolbar.enable' => false,
            ]
        );
        $this->register(new DebugToolbarEnabler());

        // Register the toolbar item for our Database query log.
        $this->register(new Provider\DatabaseProfilerServiceProvider());

        // Register the toolbar item for our Bolt nipple.
        $this->register(new Provider\BoltProfilerServiceProvider());
    }

    public function initLocale()
    {
        $configLocale = (array) $this['config']->get('general/locale', Application::DEFAULT_LOCALE);

        // $app['locale'] should only be a single value.
        $this['locale'] = reset($configLocale);

        // Set the default timezone if provided in the Config
        date_default_timezone_set($this['config']->get('general/timezone') ?: ini_get('date.timezone') ?: 'UTC');

        // for javascript datetime calculations, timezone offset. e.g. "+02:00"
        $this['timezone_offset'] = date('P');

        // Set default locale, for Bolt
        $locale = [];
        foreach ($configLocale as $value) {
            $locale = array_merge($locale, [
                $value . '.UTF-8',
                $value . '.utf8',
                $value,
                Application::DEFAULT_LOCALE . '.UTF-8',
                Application::DEFAULT_LOCALE . '.utf8',
                Application::DEFAULT_LOCALE,
                substr(Application::DEFAULT_LOCALE, 0, 2)
            ]);
        }

        setlocale(LC_ALL, array_unique($locale));

        $this->register(
            new Silex\Provider\TranslationServiceProvider(),
            [
                'translator.cache_dir' => $this['resources']->getPath('cache/trans'),
                'locale_fallbacks'     => [Application::DEFAULT_LOCALE]
            ]
        );

        $this->register(new Provider\TranslationServiceProvider());
    }

    public function initProviders()
    {
        // Make sure we keep our current locale.
        $currentlocale = $this['locale'];

        // Setup Swiftmailer, with the selected Mail Transport options: smtp or `mail()`.
        $this->register(new Silex\Provider\SwiftmailerServiceProvider());
        $this->setSwiftmailerOptions();

        // Set up our secure random generator.
        $factory = new RandomLib\Factory();
        $this['randomgenerator'] = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

        // Set up forms and use a secure CSRF secret
        $this->register(new Silex\Provider\FormServiceProvider());
        $this['form.secret'] = $this->share(function () {
            if (!$this['session']->isStarted()) {
                return;
            } elseif ($secret = $this['session']->get('form.secret')) {
                return $secret;
            } else {
                $secret = $this['randomgenerator']->generate(32);
                $this['session']->set('form.secret', $secret);

                return $secret;
            }
        });

        $this
            ->register(new Silex\Provider\HttpFragmentServiceProvider())
            ->register(new Silex\Provider\UrlGeneratorServiceProvider())
            ->register(new Silex\Provider\ValidatorServiceProvider())
            ->register(new Provider\RoutingServiceProvider())
            ->register(new Silex\Provider\ServiceControllerServiceProvider()) // must be after Routing
            ->register(new Provider\PermissionsServiceProvider())
            ->register(new Provider\StorageServiceProvider())
            ->register(new Provider\AuthenticationServiceProvider())
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
            ->register(new Provider\FilesystemProvider())
            ->register(new Thumbs\ThumbnailProvider())
            ->register(new Provider\NutServiceProvider())
            ->register(new Provider\GuzzleServiceProvider())
            ->register(new Provider\PrefillServiceProvider())
            ->register(new SlugifyServiceProvider())
            ->register(new Provider\MarkdownServiceProvider())
            ->register(new Provider\ControllerServiceProvider())
            ->register(new Provider\EventListenerServiceProvider())
            ->register(new Provider\AssetServiceProvider())
        ;

        $this['paths'] = $this['resources']->getPaths();

        // For some obscure reason, and under suspicious circumstances $app['locale'] might become 'null'.
        // Re-set it here, just to be sure. See https://github.com/bolt/bolt/issues/1405
        $this['locale'] = $currentlocale;

        // Initialize stopwatch even if debug is not enabled.
        $this['stopwatch'] = $this->share(
            function () {
                return new Stopwatch\Stopwatch();
            }
        );
    }

    /**
     * Set up the optional parameters for Swiftmailer
     */
    private function setSwiftmailerOptions()
    {
        if ($this['config']->get('general/mailoptions')) {
            // Use the preferred options. Assume it's SMTP, unless set differently.
            $this['swiftmailer.options'] = $this['config']->get('general/mailoptions');
        }

        if (is_bool($this['config']->get('general/mailoptions/spool'))) {
            // enable or disable the mail spooler.
            $this['swiftmailer.use_spool'] = $this['config']->get('general/mailoptions/spool');
        }

        if ($this['config']->get('general/mailoptions/transport') === 'mail') {
            // Use the 'mail' transport. Discouraged, but some people want it. ¯\_(ツ)_/¯
            $this['swiftmailer.transport'] = \Swift_MailTransport::newInstance();
        }
    }

    public function initExtensions()
    {
        $this['extensions']->checkLocalAutoloader();
        $this['extensions']->initialize();
    }

    /**
     * @deprecated To be removed in Bolt 3.0. Use {@see ControllerEvents::MOUNT} instead.
     */
    public function initMountpoints()
    {
    }

    /**
     * @deprecated since Bolt 2.3 and will be removed in Bolt 3.0.
     */
    public function beforeHandler()
    {
    }

    /**
     * @deprecated since Bolt 2.3 and will be removed in Bolt 3.0.
     */
    public function afterHandler()
    {
    }

    /**
     * @deprecated since Bolt 2.3 and will be removed in Bolt 3.0.
     */
    public function errorHandler()
    {
    }

    /**
     * @deprecated Remove with the monolithic Bolt\Storage in 3.0
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
     * @param boolean $long TRUE returns 'version name', FALSE 'version'
     *
     * @return string
     */
    public function getVersion($long = true)
    {
        if ($long) {
            return trim($this['bolt_version'] . ' ' . $this['bolt_name']);
        }

        return $this['bolt_version'];
    }

    /**
     * Generates a path from the given parameters.
     *
     * Note: This can be pulled in from Silex\Application\UrlGeneratorTrait
     * once we support Traits.
     *
     * @param string $route      The name of the route
     * @param array  $parameters An array of parameters
     *
     * @return string The generated path
     */
    public function generatePath($route, $parameters = [])
    {
        return $this['url_generator']->generate($route, $parameters);
    }
}
