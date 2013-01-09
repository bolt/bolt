<?php

// First, do some low level checks, like whether autoload is present, the cache
// folder is writable, if the minimum PHP version is present, etc.
require_once __DIR__.'/classes/lib.php';
require_once __DIR__.'/classes/lowlevelchecks.php';

$checker = new LowlevelChecks();
$checker->doChecks();

// Let's get on with the rest..
require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/classes/util.php';

// Start the timer:
$starttime=getMicrotime();

$config = getConfig();

// Finally, check if the app/database folder is writable, if it needs to be.
$checker->doDatabaseCheck($config);

$dboptions = getDBOptions($config);

$app = new Bolt\Application();

$app['debug'] = (!empty($config['general']['debug'])) ? $config['general']['debug'] : false;
$app['debugbar'] = false;
$app['config'] = $config;

$app->register(new Silex\Provider\SessionServiceProvider(), array(
    'session.storage.options' => array(
        'name' => 'bolt_session',
        'cookie_lifetime' => $config['general']['cookies_lifetime'],
        'cookie_domain' => $config['general']['cookies_domain']
    )
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => $config['twigpath'],
    'twig.options' => array(
        'debug'=>true,
        'cache' => __DIR__.'/cache/',
        'strict_variables' => $config['general']['strict_variables'],
        'autoescape' => true )
));
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $dboptions
));
$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => __DIR__.'/cache/',
));

$app->register(new Silex\Provider\SwiftmailerServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.messages' => array(),
));

// Loading stub functions for when intl / IntlDateFormatter isn't available.
if (!function_exists('intl_get_error_code')) {
    require_once __DIR__.'/../vendor/symfony/Locale/Symfony/Component/Locale/Resources/stubs/functions.php';
    require_once __DIR__.'/../vendor/symfony/Locale/Symfony/Component/Locale/Resources/stubs/IntlDateFormatter.php';
}

$app->register(new Bolt\LogServiceProvider(), array());
$app->register(new Bolt\StorageServiceProvider(), array());
$app->register(new Bolt\UsersServiceProvider(), array());
$app->register(new Bolt\CacheServiceProvider(), array());
$app->register(new Bolt\ExtensionServiceProvider(), array());

$app['paths'] = getPaths($config);
$app['twig']->addGlobal('paths', $app['paths']);

$app['end'] = getWhichEnd();
$app['twig']->addGlobal('frontend', false);
$app['twig']->addGlobal('backend', false);
$app['twig']->addGlobal('async', false);
$app['twig']->addGlobal($app['end'], true);

$app['editlink'] = "";

// Add the Bolt Twig functions, filters and tags.
$app['twig']->addExtension(new Bolt\TwigExtension($app));
$app['twig']->addTokenParser(new Bolt\SetcontentTokenParser());

// If debug is set, we set up the custom error handler..
if ($app['debug']) {
    ini_set("display_errors", "1");
    error_reporting (E_ALL );
    $old_error_handler = set_error_handler("userErrorHandler");
} else {
    error_reporting(E_ALL ^ E_NOTICE);
    // error_reporting( E_ALL ^ E_NOTICE ^ E_WARNING );
}

require __DIR__.'/app.php';

// Initialize enabled extensions.
$app['extensions']->initialize();
