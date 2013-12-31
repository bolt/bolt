<?php

namespace Bolt;

use RandomLib;
use SecurityLib;
use Silex;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Stopwatch;
use Whoops\Provider\Silex\WhoopsServiceProvider;

class Application extends Silex\Application
{
    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '1.4.3';
        $values['bolt_name'] = '';

        parent::__construct($values);

        // Initialize the config. Note that we do this here, on 'construct'.
        // All other initialisation is triggered from bootstrap.php
        $this->initConfig();

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
        $this->register(new Silex\Provider\SessionServiceProvider(), array(
            'session.storage.options' => array(
                'name'            => 'bolt_session',
                'cookie_secure'   => $this['config']->get('general/cookies_https_only'),
                'cookie_httponly' => true
            )
        ));

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

        // Initialize the rest of the Providers.
        $this->initProviders();

        // Initialise the Mount points for 'frontend', 'backend' and 'async'.
        $this->initMountpoints();

        // Initialise the global 'before' handler.
        $this->before(array($this, 'BeforeHandler'));

        // Initialise the global 'after' handlers.
        $this->initAfterHandler();

        // Initialise the 'error' handler.
        $this->error(array($this, 'ErrorHandler'));
    }

    /**
     * Initialize the database providers.
     */
    public function initDatabase()
    {
        $dboptions = $this['config']->getDBOptions();

        $this->register(new Silex\Provider\DoctrineServiceProvider(), array(
            'db.options' => $dboptions
        ));

        if ($dboptions['driver'] == 'pdo_sqlite') {
            $this['db']->query('PRAGMA synchronous = OFF');
        } elseif ($dboptions['driver'] == 'pdo_mysql') {
            /**
             * @link https://groups.google.com/forum/?fromgroups=#!topic/silex-php/AR3lpouqsgs
             */
            $this['db']->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            // set utf8 on names and connection as all tables has this charset
            $this['db']->query("SET NAMES 'utf8';");
            $this['db']->query("SET CHARACTER SET 'utf8';");
            $this['db']->query("SET CHARACTER_SET_CONNECTION = 'utf8';");
        }

        $this->register(new Silex\Provider\HttpCacheServiceProvider(), array(
            'http_cache.cache_dir' => __DIR__ . '/cache/',
        ));
    }

    public function initRendering()
    {
        $this->register(new Silex\Provider\TwigServiceProvider(), array(
            'twig.path'    => $this['config']->get('twigpath'),
            'twig.options' => array(
                'debug'            => true,
                'cache'            => __DIR__ . '/../../cache/',
                'strict_variables' => $this['config']->get('general/strict_variables'),
                'autoescape'       => true,
            )
        ));

        $this->register(new Provider\RenderServiceProvider());
    }

    public function initLocale()
    {
        list ($this['locale'], $this['territory']) = explode('_', $this['config']->get('general/locale'));

        // Set The Timezone Based on the Config, fallback to UTC
        date_default_timezone_set(
            $this['config']->get('general/timezone') ?: 'UTC'
        );

        // Set default locale
        $locale = array(
            $this['config']->get('general/locale') . '.utf8',
            $this['config']->get('general/locale'),
            'en_GB.utf8', 'en_GB', 'en'
        );
        setlocale(LC_ALL, $locale);

        $this->register(new Silex\Provider\TranslationServiceProvider(), array());

        // Loading stub functions for when intl / IntlDateFormatter isn't available.
        if (!function_exists('intl_get_error_code')) {
            require_once BOLT_PROJECT_ROOT_DIR . '/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/functions.php';
            require_once BOLT_PROJECT_ROOT_DIR . '/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/IntlDateFormatter.php';
        }

        $this->register(new Provider\TranslationServiceProvider());
    }

    public function initProviders()
    {
        // Setup Swiftmailer, with optional SMTP settings. If no settings are provided in config.yml, mail() is used.
        $this->register(new Silex\Provider\SwiftmailerServiceProvider());
        if ($this['config']->get('general/mailoptions')) {
            $this['swiftmailer.options'] = $this['config']->get('general/mailoptions');
        }

        // Set up our secure random generator.
        $factory = new RandomLib\Factory;
        $this['randomgenerator'] = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

        $this->register(new Silex\Provider\UrlGeneratorServiceProvider())
            ->register(new Silex\Provider\FormServiceProvider())
            ->register(new Silex\Provider\ValidatorServiceProvider())
            ->register(new Provider\PermissionsServiceProvider())
            ->register(new Provider\StorageServiceProvider())
            ->register(new Provider\UsersServiceProvider())
            ->register(new Provider\CacheServiceProvider())
            ->register(new Provider\ExtensionServiceProvider())
            ->register(new Provider\StackServiceProvider());

        $this['paths'] = getPaths($this['config']);
        $this['twig']->addGlobal('paths', $this['paths']);

        // Add the Bolt Twig functions, filters and tags.
        $this['twig']->addExtension(new TwigExtension($this));
        $this['twig']->addTokenParser(new SetcontentTokenParser());

        // Initialize enabled extensions.
        $this['extensions']->initialize();

        // @todo: make a provider for the Integrity checker and Random generator..

        // Set up the integrity checker for the Database, to periodically check if the Database
        // is up to date, and if needed: repair it.
        $this['integritychecker'] = new Database\IntegrityChecker($this);
    }

    public function initMountpoints()
    {
        $request = Request::createFromGlobals();
        if ($proxies = $this['config']->get('general/trustProxies')) {
            $request->setTrustedProxies($proxies);
        }

        // Mount the 'backend' on the branding:path setting. Defaults to '/bolt'.
        $this->mount($this['config']->get('general/branding/path'), new Controllers\Backend());

        // Mount the 'async' controllers on /async. Not configurable.
        $this->mount('/async', new Controllers\Async());

        if ($this['config']->get('general/enforce_ssl')) {
            foreach ($this['routes']->getIterator() as $route) {
                $route->requireHttps();
            }
        }

        // Mount the 'frontend' controllers, ar defined in our Routing.yml
        $this->mount('', new Controllers\Routing());
    }

    public function BeforeHandler(Request $request)
    {
        // Start the 'stopwatch' for the profiler.
        $this['stopwatch']->start('bolt.app.before');

        $this['twig']->addGlobal('bolt_name', $this['bolt_name']);
        $this['twig']->addGlobal('bolt_version', $this['bolt_version']);

        $this['twig']->addGlobal('frontend', false);
        $this['twig']->addGlobal('backend', false);
        $this['twig']->addGlobal('async', false);
        $this['twig']->addGlobal($this['config']->getWhichEnd(), true);

        $this['twig']->addGlobal('user', $this['users']->getCurrentUser());
        $this['twig']->addGlobal('users', $this['users']->getUsers());
        $this['twig']->addGlobal('config', $this['config']);

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
                $this->register(new WhoopsServiceProvider);
            }

            $this->register(new Silex\Provider\ServiceControllerServiceProvider);

            // Register the Silex/Symfony web debug toolbar.
            $this->register(new Silex\Provider\WebProfilerServiceProvider(), array(
                'profiler.cache_dir'    => __DIR__ . '/../../cache/profiler',
                'profiler.mount_prefix' => '/_profiler', // this is the default
            ));

            // Register the toolbar item for our Database query log.
            $this->register(new Provider\DatabaseProfilerServiceProvider());

            // Register the toolbar item for our bolt nipple.
            $this->register(new Provider\BoltProfilerServiceProvider());

            // Register the toolbar item for the Twig toolbar item.
            $this->register(new Provider\TwigProfilerServiceProvider());

            $this['twig.loader.filesystem']->addPath(
                BOLT_PROJECT_ROOT_DIR . '/vendor/symfony/web-profiler-bundle/Symfony/Bundle/WebProfilerBundle/Resources/views',
                'WebProfiler'
            );
            $this['twig.loader.filesystem']->addPath(__DIR__ . '/../../view', 'BoltProfiler');

            // PHP 5.3 does not allow 'use ($this)' in closures.
            $app = $this;

            $this->after(function () use ($app) {
                foreach (hackislyParseRegexTemplates($app['twig.loader.filesystem']) as $template) {
                    $app['twig.logger']->collectTemplateData($template);
                }
            });
        } else {
            // Even if debug is not enabled,
            $this['stopwatch'] = $this->share(function () {
                return new Stopwatch\Stopwatch();
            });

            error_reporting(E_ALL &~ E_NOTICE &~ E_DEPRECATED &~ E_USER_DEPRECATED);
        }

        $this->after(array($this, 'afterHandler'));
    }

    /**
     * Global 'after' handler. Adds 'after' HTML-snippets and Meta-headers to the output.
     *
     * @param Request $request
     * @param Response $response
     */
    public function afterHandler(Request $request, Response $response)
    {
        // Start the 'stopwatch' for the profiler.
        $this['stopwatch']->start('bolt.app.after');

        // true if we need to consider adding html snippets
        if (isset($this['htmlsnippets']) && ($this['htmlsnippets'] === true)) {
            // only add when content-type is text/html
            if (strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
                // Add our meta generator tag..
                $this['extensions']->insertSnippet(Extensions\Snippets\Location::AFTER_META, '<meta name="generator" content="Bolt">');

                // Perhaps add a canonical link..

                if ($this['config']->get('general/canonical')) {
                    $snippet = sprintf('<link rel="canonical" href="%s">', $this['paths']['canonicalurl']);
                    $this['extensions']->insertSnippet(Extensions\Snippets\Location::AFTER_META, $snippet);
                }

                // Perhaps add a favicon..
                if ($this['config']->get('general/favicon')) {
                    $snippet = sprintf(
                        '<link rel="shortcut icon" href="//%s%s%s">',
                        $this['paths']['canonical'],
                        $this['paths']['theme'],
                        $this['config']->get('general/favicon')
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
     * @param \Exception $exception
     * @return Response
     */
    public function ErrorHandler(\Exception $exception)
    {
        // If we are in maintenance mode and current user is not logged in, show maintenance notice.
        // @see /app/src/Bolt/Controllers/Frontend.php, Frontend::before()
        if ($this['config']->get('general/maintenance_mode')) {
            $user = $this['users']->getCurrentUser();
            if ($user['userlevel'] < 2) {
                $template = $this['config']->get('general/maintenance_template');
                $body = $this['render']->render($template);
                return new Response($body, 503);
            }
        }

        $paths = getPaths($this['config']);

        $twigvars = array();

        $twigvars['class'] = get_class($exception);
        $twigvars['message'] = $exception->getMessage();
        $twigvars['code'] = $exception->getCode();
        $twigvars['paths'] = $paths;

        $this['log']->add($twigvars['message'], 2, '', 'abort');

        $end = $this['config']->getWhichEnd();

        $trace = $exception->getTrace();

        foreach ($trace as $key => $value) {

            if (!empty($value['file']) && strpos($value['file'], '/vendor/') > 0) {
                unset($trace[$key]['args']);
            }

            // Don't display the full path..
            if (isset( $trace[$key]['file'])) {
                $trace[$key]['file'] = str_replace(BOLT_PROJECT_ROOT_DIR, '[root]', $trace[$key]['file']);
            }
        }

        $twigvars['trace'] = $trace;
        $twigvars['title'] = 'An error has occurred!';

        if (($exception instanceof HttpException) && ($end == 'frontend')) {
            $content = $this['storage']->getContent($this['config']->get('general/notfound'), array('returnsingle' => true));

            // Then, select which template to use, based on our 'cascading templates rules'
            if ($content instanceof \Bolt\Content && !empty($content->id)) {
                $template = $content->template();

                return $this['render']->render($template, array(
                    'record' => $content,
                    $content->contenttype['singular_slug'] => $content // Make sure we can also access it as {{ page.title }} for pages, etc.
                ));
            }

            $twigvars['message'] = "The page could not be found, and there is no 'notfound' set in 'config.yml'. Sorry about that.";
        }

        return $this['render']->render('error.twig', $twigvars);
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
