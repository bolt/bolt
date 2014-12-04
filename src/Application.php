<?php

namespace Bolt;

use Bolt\Configuration\LowlevelException;
use Bolt\Library as Lib;
use RandomLib;
use SecurityLib;
use Silex;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Stopwatch;
use Whoops\Provider\Silex\WhoopsServiceProvider;
use Bolt\Provider\PathServiceProvider;

class Application extends Silex\Application
{
    /**
     * The default locale, used as fallback
     */
    const DEFAULT_LOCALE = 'en_GB';

    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '2.0.0';
        $values['bolt_name'] = 'beta 5 pl 4';

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
        $this['resources']->initialize();

        $this['debug'] = $this['config']->get('general/debug', false);
        $this['debugbar'] = false;

        // Initialize the 'editlink' and 'edittitle'..
        $this['editlink'] = '';
        $this['edittitle'] = '';
    }

    /**
     * Initialize the config and session providers.
     */
    private function initConfig()
    {
        $this->register(new Provider\ConfigServiceProvider());
        $this->register(
            new Silex\Provider\SessionServiceProvider(),
            array(
                'session.storage.options' => array(
                    'name'            => 'bolt_session',
                    'cookie_secure'   => $this['config']->get('general/cookies_https_only'),
                    'cookie_httponly' => true
                )
            )
        );

        // Disable Silex's built-in native filebased session handler, and fall back to
        // whatever's set in php.ini.
        // @see: http://silex.sensiolabs.org/doc/providers/session.html#custom-session-configurations
        if ($this['config']->get('general/session_use_storage_handler') === false) {
            $this['session.storage.handler'] = null;
        }

        $this->register(new Provider\LogServiceProvider());
    }

    public function initialize()
    {
        // Set up locale and translations.
        $this->initLocale();

        // Initialize Twig and our rendering Provider.
        $this->initRendering();

        // Initialize the Database Providers.
        $this->initDatabase();

        // Initialize the Console Application for Nut
        $this->initConsoleApplication();

        // Initialize the rest of the Providers.
        $this->initProviders();

        // Initialise the Mount points for 'frontend', 'backend' and 'async'.
        $this->initMountpoints();

        // Initialize enabled extensions before executing handlers.
        $this->initExtensions();

        // Initialise the global 'before' handler.
        $this->before(array($this, 'beforeHandler'));

        // Initialise the global 'after' handlers.
        $this->initAfterHandler();

        // Initialise the 'error' handler.
        $this->error(array($this, 'errorHandler'));
    }

    /**
     * Initialize the database providers.
     */
    public function initDatabase()
    {
        $dboptions = $this['config']->getDBOptions();

        $this->register(
            new Silex\Provider\DoctrineServiceProvider(),
            array(
                'db.options' => $dboptions
            )
        );

        // Do a dummy query, to check for a proper connection to the database.
        try {
            $this['db']->query("SELECT 1;");
        } catch (\PDOException $e) {
            $error = "Bolt could not connect to the database. Make sure the database is configured correctly in
                    <code>app/config/config.yml</code>, that the database engine is running.";
            if ($dboptions['driver'] != 'pdo_sqlite') {
                $error .= "<br><br>Since you're using " . $dboptions['driver'] . ", you should also make sure that the
                database <code>" . $dboptions['dbname'] . "</code> exists, and the configured user has access to it.";
            }
            throw new LowlevelException($error);
        }

        if ($dboptions['driver'] == 'pdo_sqlite') {
            $this['db']->query('PRAGMA synchronous = OFF');
        } elseif ($dboptions['driver'] == 'pdo_mysql') {
            /**
             * @link https://groups.google.com/forum/?fromgroups=#!topic/silex-php/AR3lpouqsgs
             */
            $this['db']->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            // set utf8 on names and connection as all tables has this charset

            $this['db']->query("SET NAMES 'utf8';");
            $this['db']->query("SET CHARACTER_SET_CONNECTION = 'utf8';");
            $this['db']->query("SET CHARACTER SET utf8;");
        }

        $this->register(
            new Silex\Provider\HttpCacheServiceProvider(),
            array(
                'http_cache.cache_dir' => $this['resources']->getPath('cache'),
            )
        );
    }

    public function initRendering()
    {
        // Should we cache or not?
        if ($this['config']->get('general/caching/templates')) {
            $cache = $this['resources']->getPath('cache');
        } else {
            $cache = false;
        }

        $this->register(
            new Silex\Provider\TwigServiceProvider(),
            array(
                'twig.path'    => $this['config']->get('twigpath'),
                'twig.options' => array(
                    'debug'            => true,
                    'cache'            => $cache,
                    'strict_variables' => $this['config']->get('general/strict_variables'),
                    'autoescape'       => true,
                )
            )
        );

        $this->register(new Provider\RenderServiceProvider());
        $this->register(new Provider\RenderServiceProvider(true));
    }

    public function initLocale()
    {
        $this['locale'] = $this['config']->get('general/locale', Application::DEFAULT_LOCALE);

        // Set The Timezone Based on the Config, fallback to UTC
        date_default_timezone_set(
            $this['config']->get('general/timezone') ?: 'UTC'
        );

        // Set default locale
        $locale = array(
            $this['locale'] . '.UTF-8',
            $this['locale'] . '.utf8',
            $this['locale'],
            Application::DEFAULT_LOCALE . '.UTF-8',
            Application::DEFAULT_LOCALE . '.utf8',
            Application::DEFAULT_LOCALE,
            substr(Application::DEFAULT_LOCALE, 0, 2)
        );
        setlocale(LC_ALL, $locale);

        $this->register(
            new Silex\Provider\TranslationServiceProvider(),
            array('locale_fallbacks' => array(Application::DEFAULT_LOCALE))
        );

        // Loading stub functions for when intl / IntlDateFormatter isn't available.
        if (!function_exists('intl_get_error_code')) {
            require_once $this->app['resources']->getPath('root') . '/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/functions.php';
            require_once $this->app['resources']->getPath('root') . '/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/IntlDateFormatter.php';
        }

        $this->register(new Provider\TranslationServiceProvider());
    }

    public function initProviders()
    {
        // Make sure we keep our current locale..
        $currentlocale = $this['locale'];

        // Setup Swiftmailer, with optional SMTP settings. If no settings are provided in config.yml, mail() is used.
        $this->register(new Silex\Provider\SwiftmailerServiceProvider());
        if ($this['config']->get('general/mailoptions')) {
            $this['swiftmailer.options'] = $this['config']->get('general/mailoptions');
        }

        // Set up our secure random generator.
        $factory = new RandomLib\Factory();
        $this['randomgenerator'] = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

        $this->register(new Silex\Provider\UrlGeneratorServiceProvider())
            ->register(new Silex\Provider\FormServiceProvider())
            ->register(new Silex\Provider\ValidatorServiceProvider())
            ->register(new Provider\PermissionsServiceProvider())
            ->register(new Provider\StorageServiceProvider())
            ->register(new Provider\UsersServiceProvider())
            ->register(new Provider\CacheServiceProvider())
            ->register(new Provider\IntegrityCheckerProvider())
            ->register(new Provider\ExtensionServiceProvider())
            ->register(new Provider\StackServiceProvider())
            ->register(new Provider\OmnisearchServiceProvider())
            ->register(new Provider\TemplateChooserServiceProvider())
            ->register(new Provider\CronServiceProvider())
            ->register(new Provider\SafeTwigServiceProvider())
            ->register(new Provider\FilePermissionsServiceProvider())
            ->register(new Controllers\Upload())
            ->register(new Controllers\Extend())
            ->register(new Provider\FilesystemProvider())
            ->register(new Thumbs\ThumbnailProvider());

        $this['paths'] = $this['resources']->getPaths();

        $this['twig']->addGlobal('paths', $this['paths']);

        // For some obscure reason, and under suspicious circumstances $app['locale'] might become 'null'.
        // Re-set it here, just to be sure. See https://github.com/bolt/bolt/issues/1405
        $this['locale'] = $currentlocale;

        // Add the Bolt Twig functions, filters and tags.
        $this['twig']->addExtension(new TwigExtension($this));
        $this['safe_twig']->addExtension(new TwigExtension($this, true));

        $this['twig']->addTokenParser(new SetcontentTokenParser());

        // Initialize stopwatch even if debug is not enabled.
        $this['stopwatch'] = $this->share(
            function () {
                return new Stopwatch\Stopwatch();
            }
        );

        // @todo: make a provider for the Random generator..
    }

    public function initExtensions()
    {
        $this['extensions']->initialize();
    }

    public function initMountpoints()
    {
        $app = $this;

        // Wire up our custom url matcher to replace the default Silex\RedirectableUrlMatcher
        $this['url_matcher'] = $this->share(
            function () use ($app) {
                return new BoltUrlMatcher(
                    new \Symfony\Component\Routing\Matcher\UrlMatcher($app['routes'], $app['request_context'])
                );
            }
        );

        $request = Request::createFromGlobals();
        if ($proxies = $this['config']->get('general/trustProxies')) {
            $request->setTrustedProxies($proxies);
        }

        // Mount the 'backend' on the branding:path setting. Defaults to '/bolt'.
        $this->mount($this['config']->get('general/branding/path'), new Controllers\Backend());

        // Mount the 'async' controllers on /async. Not configurable.
        $this->mount('/async', new Controllers\Async());

        // Mount the 'thumbnail' provider on /thumbs.
        $this->mount('/thumbs', new \Bolt\Thumbs\ThumbnailProvider());

        // Mount the 'upload' controller on /upload.
        $this->mount('/upload', new Controllers\Upload());

        // Mount the 'extend' controller on /branding/extend.
        $this->mount(
            $this['config']->get('general/branding/path') . '/extend',
            $this['extend']
        );

        if ($this['config']->get('general/enforce_ssl')) {
            foreach ($this['routes']->getIterator() as $route) {
                $route->requireHttps();
            }
        }

        // Mount the 'frontend' controllers, ar defined in our Routing.yml
        $this->mount('', new Controllers\Routing());
    }


    /** 
     * Add all the global twig variables, like 'user' and 'theme'
     */
    private function addTwigGlobals()
    { 

        $this['twig']->addGlobal('bolt_name', $this['bolt_name']);
        $this['twig']->addGlobal('bolt_version', $this['bolt_version']);

        $this['twig']->addGlobal('frontend', false);
        $this['twig']->addGlobal('backend', false);
        $this['twig']->addGlobal('async', false);
        $this['twig']->addGlobal($this['config']->getWhichEnd(), true);

        $this['twig']->addGlobal('user', $this['users']->getCurrentUser());
        $this['twig']->addGlobal('users', $this['users']->getUsers());
        $this['twig']->addGlobal('config', $this['config']);
        $this['twig']->addGlobal('theme', $this['config']->get('theme'));

        $this['safe_twig']->addGlobal('bolt_name', $this['bolt_name']);
        $this['safe_twig']->addGlobal('bolt_version', $this['bolt_version']);

        $this['safe_twig']->addGlobal('frontend', false);
        $this['safe_twig']->addGlobal('backend', false);
        $this['safe_twig']->addGlobal('async', false);
        $this['safe_twig']->addGlobal($this['config']->getWhichEnd(), true);

        $this['safe_twig']->addGlobal('user', $this['users']->getCurrentUser());
        $this['safe_twig']->addGlobal('theme', $this['config']->get('theme'));

    }


    /**
     * Initializes the Console Application that is responsible for CLI interactions.
     */
    public function initConsoleApplication()
    {
        $this['console'] = $this->share(
            function (Application $app) {
                $console = new ConsoleApplication();
                $console->setName('Bolt console tool - Nut');
                $console->setVersion($app->getVersion());

                return $console;
            }
        );
    }

    public function beforeHandler(Request $request)
    {
        // Start the 'stopwatch' for the profiler.
        $this['stopwatch']->start('bolt.app.before');

        // Set the twig Globals, like 'user' and 'theme'.
        $this->addTwigGlobals();

        if ($response = $this['render']->fetchCachedRequest()) {
            // Stop the 'stopwatch' for the profiler.
            $this['stopwatch']->stop('bolt.app.before');

            // Short-circuit the request, return the HTML/response. YOLO.
            return $response;
        }

        // Sanity checks for doubles in in contenttypes.
        // unfortunately this has to be done here, because the 'translator' classes need to be initialised.
        $this['config']->checkConfig();

        // Stop the 'stopwatch' for the profiler.
        $this['stopwatch']->stop('bolt.app.before');
    }

    public function initAfterHandler()
    {
        // On 'after' attach the debug-bar, if debug is enabled..
        if ($this['debug'] && ($this['session']->has('user') || $this['config']->get('general/debug_show_loggedoff'))) {

            // Set the error_reporting to the level specified in config.yml
            error_reporting($this['config']->get('general/debug_error_level'));

            // Register Whoops, to handle errors for logged in users only.
            if ($this['config']->get('general/debug_enable_whoops')) {
                $this->register(new WhoopsServiceProvider());
            }

            $this->register(new Silex\Provider\ServiceControllerServiceProvider());

            // Register the Silex/Symfony web debug toolbar.
            $this->register(
                new Silex\Provider\WebProfilerServiceProvider(),
                array(
                    'profiler.cache_dir'    => $this['resources']->getPath('cache') . '/profiler',
                    'profiler.mount_prefix' => '/_profiler', // this is the default
                )
            );

            // Register the toolbar item for our Database query log.
            $this->register(new Provider\DatabaseProfilerServiceProvider());

            // Register the toolbar item for our bolt nipple.
            $this->register(new Provider\BoltProfilerServiceProvider());

            // Register the toolbar item for the Twig toolbar item.
            $this->register(new Provider\TwigProfilerServiceProvider());

            $this['twig.loader.filesystem']->addPath(
                $this['resources']->getPath('root') . '/vendor/symfony/web-profiler-bundle/Symfony/Bundle/WebProfilerBundle/Resources/views',
                'WebProfiler'
            );

            $this['twig.loader.filesystem']->addPath($this['resources']->getPath('app') . '/view', 'BoltProfiler');

            // PHP 5.3 does not allow 'use ($this)' in closures.
            $app = $this;

            $this->after(
                function () use ($app) {
                    foreach (Lib::hackislyParseRegexTemplates($app['twig.loader.filesystem']) as $template) {
                        $app['twig.logger']->collectTemplateData($template);
                    }
                }
            );
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        }

        $this->after(array($this, 'afterHandler'));
    }

    /**
     * Global 'after' handler. Adds 'after' HTML-snippets and Meta-headers to the output.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function afterHandler(Request $request, Response $response)
    {
        // Start the 'stopwatch' for the profiler.
        $this['stopwatch']->start('bolt.app.after');

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Frame-Options', 'SAMEORIGIN');

        // true if we need to consider adding html snippets
        if (isset($this['htmlsnippets']) && ($this['htmlsnippets'] === true)) {
            // only add when content-type is text/html
            if (strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
                // Add our meta generator tag..
                $this['extensions']->insertSnippet(Extensions\Snippets\Location::AFTER_META, '<meta name="generator" content="Bolt">');

                // Perhaps add a canonical link..

                if ($this['config']->get('general/canonical')) {
                    $snippet = sprintf(
                        '<link rel="canonical" href="%s">',
                        htmlspecialchars($this['paths']['canonicalurl'], ENT_QUOTES)
                    );
                    $this['extensions']->insertSnippet(Extensions\Snippets\Location::AFTER_META, $snippet);
                }

                // Perhaps add a favicon..
                if ($this['config']->get('general/favicon')) {
                    $snippet = sprintf(
                        '<link rel="shortcut icon" href="//%s%s%s">',
                        htmlspecialchars($this['paths']['canonical'], ENT_QUOTES),
                        htmlspecialchars($this['paths']['theme'], ENT_QUOTES),
                        htmlspecialchars($this['config']->get('general/favicon'), ENT_QUOTES)
                    );
                    $this['extensions']->insertSnippet(Extensions\Snippets\Location::AFTER_META, $snippet);
                }

                // Do some post-processing.. Hooks, snippets..
                $html = $this['render']->postProcess($response);

                $response->setContent($html);
            }
        }

        // Stop the 'stopwatch' for the profiler.
        $this['stopwatch']->stop('bolt.app.after');
    }

    /**
     * Handle errors thrown in the application. Set up whoops, if set in conf
     *
     * @param  \Exception $exception
     * @return Response
     */
    public function errorHandler(\Exception $exception)
    {
        // If we are in maintenance mode and current user is not logged in, show maintenance notice.
        // @see Controllers\Frontend::before()
        if ($this['config']->get('general/maintenance_mode')) {
            $user = $this['users']->getCurrentUser();
            if ($user['userlevel'] < 2) {
                $template = $this['config']->get('general/maintenance_template');
                $body = $this['render']->render($template);

                return new Response($body, 503);
            }
        }

        $message = $exception->getMessage();

        $this['log']->add($message, 2, '', 'abort');

        $end = $this['config']->getWhichEnd();

        $trace = $exception->getTrace();

        // Set the twig Globals, like 'user' and 'theme'.
        $this->addTwigGlobals();

        foreach ($trace as $key => $value) {
            if (!empty($value['file']) && strpos($value['file'], '/vendor/') > 0) {
                unset($trace[$key]['args']);
            }

            // Don't display the full path..
            if (isset( $trace[$key]['file'])) {
                $trace[$key]['file'] = str_replace($this['resources']->getPath('root'), '[root]', $trace[$key]['file']);
            }
        }

        if (($exception instanceof HttpException) && ($end == 'frontend')) {
            if ($exception->getStatusCode() == 403) {
                $content = $this['storage']->getContent($this['config']->get('general/access_denied'), array('returnsingle' => true));
            } else {
                $content = $this['storage']->getContent($this['config']->get('general/notfound'), array('returnsingle' => true));
            }

            // Then, select which template to use, based on our 'cascading templates rules'
            if ($content instanceof \Bolt\Content && !empty($content->id)) {
                $template = $this['templatechooser']->record($content);

                return $this['render']->render($template, $content->getTemplateContext());
            }

            $message = "The page could not be found, and there is no 'notfound' set in 'config.yml'. Sorry about that.";
        }

        $context = array(
            'class' => get_class($exception),
            'message' => $message,
            'code' => $exception->getCode(),
            'trace' => $trace,
        );

        // Note: This uses the template from app/theme_defaults. Not app/view/twig.
        return $this['render']->render('error.twig', array('context' => $context));
    }

    /**
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return (array_key_exists($name, $this));
    }

    public function getVersion($long = true)
    {
        if ($long) {
            return $this['bolt_version'] . ' ' . $this['bolt_name'];
        }

        return $this['bolt_version'];
    }
}
