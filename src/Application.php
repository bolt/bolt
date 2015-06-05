<?php

namespace Bolt;

use Bolt\Exception\LowlevelException;
use Bolt\Helpers\Str;
use Bolt\Library as Lib;
use Bolt\Provider\LoggerServiceProvider;
use Bolt\Provider\PathServiceProvider;
use Bolt\Translation\Translator as Trans;
use Cocur\Slugify\Bridge\Silex\SlugifyServiceProvider;
use Doctrine\DBAL\DBALException;
use RandomLib;
use SecurityLib;
use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Stopwatch;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Provider\Silex\WhoopsServiceProvider;

class Application extends Silex\Application
{
    /**
     * The default locale, used as fallback.
     */
    const DEFAULT_LOCALE = 'en_GB';

    /**
     * @param array $values
     */
    public function __construct(array $values = array())
    {
        $values['bolt_version'] = '2.2.1';
        $values['bolt_name'] = '';
        $values['bolt_released'] = true; // `true` for stable releases, `false` for alpha, beta and RC.

        /** @internal Parameter to track a deprecated PHP version */
        $values['deprecated.php'] = version_compare(PHP_VERSION, '5.4.0', '<');

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
        $this->initSession();
        $this['resources']->initialize();

        $this['debug'] = $this['config']->get('general/debug', false);
        $this['debugbar'] = false;

        // Initialize the 'editlink' and 'edittitle'.
        $this['editlink'] = '';
        $this['edittitle'] = '';

        // Initialise the JavaScipt data gateway
        $this['jsdata'] = array();
    }

    protected function initConfig()
    {
        $this->register(new Provider\IntegrityCheckerProvider())
            ->register(new Provider\ConfigServiceProvider());
    }

    protected function initSession()
    {
        $this->register(
            new Silex\Provider\SessionServiceProvider(),
            array(
                'session.storage.options' => array(
                    'name'            => 'bolt_session',
                    'cookie_secure'   => $this['config']->get('general/enforce_ssl'),
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
    }

    public function initialize()
    {
        // Initialise logging
        $this->initLogger();

        // Set up locale and translations.
        $this->initLocale();

        // Initialize Twig and our rendering Provider.
        $this->initRendering();

        // Initialize Web Profiler Providers if enabled
        $this->initProfiler();

        // Initialize the Database Providers.
        $this->initDatabase();

        // Initialize the rest of the Providers.
        $this->initProviders();

        // Initialise the Mount points for 'frontend', 'backend' and 'async'.
        $this->initMountpoints();

        // Initialize enabled extensions before executing handlers.
        $this->initExtensions();

        $this->initMailCheck();

        // Initialise the global 'before' handler.
        $this->before(array($this, 'beforeHandler'));

        // Initialise the global 'after' handler.
        $this->after(array($this, 'afterHandler'));

        // Initialise the 'error' handler.
        $this->error(array($this, 'errorHandler'));
    }

    /**
     * Initialize the loggers.
     */
    public function initLogger()
    {
        $this->register(new LoggerServiceProvider(), array());

        // Debug log
        if ($this['config']->get('general/debuglog/enabled')) {
            $this->register(
                new Silex\Provider\MonologServiceProvider(),
                array(
                    'monolog.name'    => 'bolt',
                    'monolog.level'   => constant('Monolog\Logger::' . strtoupper($this['config']->get('general/debuglog/level'))),
                    'monolog.logfile' => $this['resources']->getPath('cache') . '/' . $this['config']->get('general/debuglog/filename')
                )
            );
        }
    }

    /**
     * Initialize the database providers.
     */
    public function initDatabase()
    {
        $this->register(
            new Silex\Provider\DoctrineServiceProvider(),
            array(
                'db.options' => $this['config']->get('general/database')
            )
        );
        $this->register(new Database\InitListener());

        $this->checkDatabaseConnection();

        $this->register(
            new Silex\Provider\HttpCacheServiceProvider(),
            array('http_cache.cache_dir' => $this['resources']->getPath('cache'))
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
            set_exception_handler(array('\Bolt\Exception\LowlevelException', 'nullHandler'));

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
     * Set up the profilers for the toolbar.
     */
    public function initProfiler()
    {
        // On 'after' attach the debug-bar, if debug is enabled.
        if (!($this['debug'] && ($this['session']->has('user') || $this['config']->get('general/debug_show_loggedoff')))) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED);

            return;
        }

        // Set the error_reporting to the level specified in config.yml
        error_reporting($this['config']->get('general/debug_error_level'));

        // Register Whoops, to handle errors for logged in users only.
        if ($this['config']->get('general/debug_enable_whoops')) {
            $this->register(new WhoopsServiceProvider());

            // Add a special handler to deal with AJAX requests
            if ($this['config']->getWhichEnd() == 'async') {
                $this['whoops']->pushHandler(new JsonResponseHandler());
            }
        }

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

        $this['twig.loader.filesystem'] = $this->share(
            $this->extend(
                'twig.loader.filesystem',
                function (\Twig_Loader_Filesystem $filesystem, Application $app) {
                    $filesystem->addPath(
                        $app['resources']->getPath('root') . '/vendor/symfony/web-profiler-bundle/Symfony/Bundle/WebProfilerBundle/Resources/views',
                        'WebProfiler'
                    );
                    $filesystem->addPath($app['resources']->getPath('app') . '/view', 'BoltProfiler');

                    return $filesystem;
                }
            )
        );

        // PHP 5.3 does not allow 'use ($this)' in closures.
        $app = $this;
        $this->after(
            function () use ($app) {
                foreach (Lib::parseTwigTemplates($app['twig.loader.filesystem']) as $template) {
                    $app['twig.logger']->collectTemplateData($template);
                }
            }
        );
    }

    public function initLocale()
    {
        $configLocale = $this['config']->get('general/locale', Application::DEFAULT_LOCALE);
        if (!is_array($configLocale)) {
            $configLocale = array($configLocale);
        }

        // $app['locale'] should only be a single value.
        $this['locale'] = reset($configLocale);

        // Set the default timezone if provided in the Config
        date_default_timezone_set($this['config']->get('general/timezone') ?: ini_get('date.timezone') ?: 'UTC');

        // for javascript datetime calculations, timezone offset. e.g. "+02:00"
        $this['timezone_offset'] = date('P');

        // Set default locale, for Bolt
        $locale = array();
        foreach ($configLocale as $value) {
            $locale = array_merge($locale, array(
                $value . '.UTF-8',
                $value . '.utf8',
                $value,
                Application::DEFAULT_LOCALE . '.UTF-8',
                Application::DEFAULT_LOCALE . '.utf8',
                Application::DEFAULT_LOCALE,
                substr(Application::DEFAULT_LOCALE, 0, 2)
            ));
        }

        setlocale(LC_ALL, array_unique($locale));

        $this->register(
            new Silex\Provider\TranslationServiceProvider(),
            array('locale_fallbacks' => array(Application::DEFAULT_LOCALE))
        );

        // Loading stub functions for when intl / IntlDateFormatter isn't available.
        if (!function_exists('intl_get_error_code')) {
            require_once $this['resources']->getPath('root/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/functions.php');
            require_once $this['resources']->getPath('root/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/IntlDateFormatter.php');
        }

        $this->register(new Provider\TranslationServiceProvider());
    }

    public function initProviders()
    {
        // Make sure we keep our current locale.
        $currentlocale = $this['locale'];

        // Setup Swiftmailer, with the selected Mail Transport options: smtp or `mail()`.
        $this->register(new Silex\Provider\SwiftmailerServiceProvider());

        if ($this['config']->get('general/mailoptions')) {
            // Use the preferred options. Assume it's SMTP, unless set differently.
            $this['swiftmailer.options'] = $this['config']->get('general/mailoptions');
        }

        if (is_bool($this['config']->get('general/mailoptions/spool'))) {
            // enable or disable the mail spooler.
            $this['swiftmailer.use_spool'] = $this['config']->get('general/mailoptions/spool');
        }

        if ($this['config']->get('general/mailoptions/transport') == 'mail') {
            // Use the 'mail' transport. Discouraged, but some people want it. ¯\_(ツ)_/¯
            $this['swiftmailer.transport'] = \Swift_MailTransport::newInstance();
        }

        // Set up our secure random generator.
        $factory = new RandomLib\Factory();
        $this['randomgenerator'] = $factory->getGenerator(new SecurityLib\Strength(SecurityLib\Strength::MEDIUM));

        $this
            ->register(new Silex\Provider\HttpFragmentServiceProvider())
            ->register(new Silex\Provider\UrlGeneratorServiceProvider())
            ->register(new Silex\Provider\FormServiceProvider())
            ->register(new Silex\Provider\ValidatorServiceProvider())
            ->register(new Provider\RoutingServiceProvider())
            ->register(new Silex\Provider\ServiceControllerServiceProvider()) // must be after Routing
            ->register(new Provider\PermissionsServiceProvider())
            ->register(new Provider\StorageServiceProvider())
            ->register(new Provider\UsersServiceProvider())
            ->register(new Provider\CacheServiceProvider())
            ->register(new Provider\ExtensionServiceProvider())
            ->register(new Provider\StackServiceProvider())
            ->register(new Provider\OmnisearchServiceProvider())
            ->register(new Provider\TemplateChooserServiceProvider())
            ->register(new Provider\CronServiceProvider())
            ->register(new Provider\FilePermissionsServiceProvider())
            ->register(new Provider\MenuServiceProvider())
            ->register(new Controllers\Upload())
            ->register(new Controllers\Extend())
            ->register(new Provider\FilesystemProvider())
            ->register(new Thumbs\ThumbnailProvider())
            ->register(new Provider\NutServiceProvider())
            ->register(new Provider\GuzzleServiceProvider())
            ->register(new Provider\PrefillServiceProvider())
            ->register(new SlugifyServiceProvider())
            ->register(new Provider\MarkdownServiceProvider());

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

    public function initExtensions()
    {
        $this['extensions']->checkLocalAutoloader();
        $this['extensions']->initialize();
    }

    /**
     * No Mail transport has been set. We should gently nudge the user to set the mail configuration.
     *
     * @see: the issue at https://github.com/bolt/bolt/issues/2908
     *
     * For now, we only pester the user, if an extension needs to be able to send
     * mail, but it's not been set up.
     */
    public function initMailCheck()
    {
        if (!$this['config']->get('general/mailoptions') && $this['extensions']->hasMailSenders()) {
            $error = "One or more installed extensions need to be able to send email. Please set up the 'mailoptions' in config.yml.";
            $this['session']->getFlashBag()->add('error', Trans::__($error));
        }
    }

    public function initMountpoints()
    {
        if ($proxies = $this['config']->get('general/trustProxies')) {
            Request::setTrustedProxies($proxies);
        }

        // Mount the 'backend' on the branding:path setting. Defaults to '/bolt'.
        $backendPrefix = $this['config']->get('general/branding/path');
        $this->mount($backendPrefix, new Controllers\Login());
        $this->mount($backendPrefix, new Controllers\Backend());

        // Mount the 'async' controllers on /async. Not configurable.
        $this->mount('/async', new Controllers\Async());

        // Mount the 'thumbnail' provider on /thumbs.
        $this->mount('/thumbs', new Thumbs\ThumbnailProvider());

        // Mount the 'upload' controller on /upload.
        $this->mount('/upload', new Controllers\Upload());

        // Mount the 'extend' controller on /branding/extend.
        $this->mount($backendPrefix . '/extend', $this['extend']);

        if ($this['config']->get('general/enforce_ssl')) {
            foreach ($this['routes'] as $route) {
                /** @var \Silex\Route $route */
                $route->requireHttps();
            }
        }

        // Mount the 'frontend' controllers, as defined in our Routing.yml
        $this->mount('', new Controllers\Routing());
    }

    public function beforeHandler(Request $request)
    {
        // Start the 'stopwatch' for the profiler.
        $this['stopwatch']->start('bolt.app.before');

        if ($response = $this['render']->fetchCachedRequest()) {
            // Stop the 'stopwatch' for the profiler.
            $this['stopwatch']->stop('bolt.app.before');

            // Short-circuit the request, return the HTML/response. YOLO.
            return $response;
        }

        // Stop the 'stopwatch' for the profiler.
        $this['stopwatch']->stop('bolt.app.before');
    }

    /**
     * Remove the 'bolt_session' cookie from the headers if it's about to be set.
     *
     * Note, we don't use $request->clearCookie (logs out a logged-on user) or
     * $request->removeCookie (doesn't prevent the header from being sent).
     *
     * @see https://github.com/bolt/bolt/issues/3425
     */
    public function unsetSessionCookie()
    {
        if (!headers_sent()) {
            $headersList = headers_list();
            foreach ($headersList as $header) {
                if (strpos($header, "Set-Cookie: bolt_session=") === 0) {
                    header_remove("Set-Cookie");
                }
            }
        }
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

        /*
         * Don't set 'bolt_session' cookie, if we're in the frontend or async.
         *
         * @see https://github.com/bolt/bolt/issues/3425
         */
        if ($this['config']->get('general/cookies_no_frontend', false) && $this['config']->getWhichEnd() !== 'backend') {
            $this->unsetSessionCookie();
        }

        // Set the 'X-Frame-Options' headers to prevent click-jacking, unless specifically disabled. Backend only!
        if ($this['config']->getWhichEnd() == 'backend' && $this['config']->get('general/headers/x_frame_options')) {
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('Frame-Options', 'SAMEORIGIN');
        }

        // true if we need to consider adding html snippets
        if (isset($this['htmlsnippets']) && ($this['htmlsnippets'] === true)) {
            // only add when content-type is text/html
            if (strpos($response->headers->get('Content-Type'), 'text/html') !== false) {
                // Add our meta generator tag.
                $this['extensions']->insertSnippet(Extensions\Snippets\Location::END_OF_HEAD, '<meta name="generator" content="Bolt">');

                // Perhaps add a canonical link.

                if ($this['config']->get('general/canonical')) {
                    $snippet = sprintf(
                        '<link rel="canonical" href="%s">',
                        htmlspecialchars($this['resources']->getUrl('canonicalurl'), ENT_QUOTES)
                    );
                    $this['extensions']->insertSnippet(Extensions\Snippets\Location::END_OF_HEAD, $snippet);
                }

                // Perhaps add a favicon.
                if ($this['config']->get('general/favicon')) {
                    $snippet = sprintf(
                        '<link rel="shortcut icon" href="%s%s%s">',
                        htmlspecialchars($this['resources']->getUrl('hosturl'), ENT_QUOTES),
                        htmlspecialchars($this['resources']->getUrl('theme'), ENT_QUOTES),
                        htmlspecialchars($this['config']->get('general/favicon'), ENT_QUOTES)
                    );
                    $this['extensions']->insertSnippet(Extensions\Snippets\Location::END_OF_HEAD, $snippet);
                }

                // Do some post-processing.. Hooks, snippets.
                $html = $this['render']->postProcess($response);

                $response->setContent($html);
            }
        }

        // Stop the 'stopwatch' for the profiler.
        $this['stopwatch']->stop('bolt.app.after');
    }

    /**
     * Handle errors thrown in the application. Set up whoops, if set in conf.
     *
     * @param \Exception $exception
     *
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

                return new Response($body, Response::HTTP_SERVICE_UNAVAILABLE);
            }
        }

        // Log the error message
        $message = $exception->getMessage();
        $this['logger.system']->critical($message, array('event' => 'exception', 'exception' => $exception));

        $trace = $exception->getTrace();
        foreach ($trace as $key => $value) {
            if (!empty($value['file']) && strpos($value['file'], '/vendor/') > 0) {
                unset($trace[$key]['args']);
            }

            // Don't display the full path.
            if (isset($trace[$key]['file'])) {
                $trace[$key]['file'] = str_replace($this['resources']->getPath('root'), '[root]', $trace[$key]['file']);
            }
        }

        $end = $this['config']->getWhichEnd();
        if (($exception instanceof HttpException) && ($end == 'frontend')) {
            $content = $this['storage']->getContent($this['config']->get('general/notfound'), array('returnsingle' => true));

            // Then, select which template to use, based on our 'cascading templates rules'
            if ($content instanceof Content && !empty($content->id)) {
                $template = $this['templatechooser']->record($content);

                return $this['render']->render($template, $content->getTemplateContext());
            }

            $message = "The page could not be found, and there is no 'notfound' set in 'config.yml'. Sorry about that.";
        }

        $context = array(
            'class'   => get_class($exception),
            'message' => $message,
            'code'    => $exception->getCode(),
            'trace'   => $trace,
        );

        // Note: This uses the template from app/theme_defaults. Not app/view/twig.
        return $this['render']->render('error.twig', array('context' => $context));
    }

    /**
     * @todo Can this be removed?
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
    public function generatePath($route, $parameters = array())
    {
        return $this['url_generator']->generate($route, $parameters);
    }
}
