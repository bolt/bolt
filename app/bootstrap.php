<?php

if (!defined( 'BOLT_PROJECT_ROOT_DIR')) {
    if (substr(__DIR__, -21) == '/vendor/bolt/bolt/app') { // installed bolt with composer
        define('BOLT_COMPOSER_INSTALLED', true);
        define('BOLT_PROJECT_ROOT_DIR', substr(__DIR__, 0, -21));
        define('BOLT_WEB_DIR', BOLT_PROJECT_ROOT_DIR.'/web');
        define('BOLT_CONFIG_DIR', BOLT_PROJECT_ROOT_DIR.'/config');
    } else {
        define('BOLT_COMPOSER_INSTALLED', false);
        define('BOLT_PROJECT_ROOT_DIR', dirname(__DIR__));
        define('BOLT_WEB_DIR', BOLT_PROJECT_ROOT_DIR);
        define('BOLT_CONFIG_DIR', __DIR__.'/config');
    }
}

// First, do some low level checks, like whether autoload is present, the cache
// folder is writable, if the minimum PHP version is present, etc.
require_once __DIR__.'/classes/lib.php';
require_once __DIR__.'/classes/lowlevelchecks.php';

$checker = new LowlevelChecks();
$checker->doChecks();

// Let's get on with the rest..
require_once BOLT_PROJECT_ROOT_DIR.'/vendor/autoload.php';
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

list ($app['locale'], $app['territory']) = explode('_', $config['general']['locale']);

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

if ($dboptions['driver']=="pdo_sqlite") {
    $app['db']->query("PRAGMA synchronous = OFF");
}

$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => __DIR__.'/cache/',
));

$app->register(new Silex\Provider\SwiftmailerServiceProvider());
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array());

// Loading stub functions for when intl / IntlDateFormatter isn't available.
if (!function_exists('intl_get_error_code')) {
    require_once BOLT_PROJECT_ROOT_DIR.'/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/functions.php';
    require_once BOLT_PROJECT_ROOT_DIR.'/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/IntlDateFormatter.php';
}

$app->register(new Bolt\TranslationServiceProvider());
$app->register(new Bolt\LogServiceProvider(), array());
$app->register(new Bolt\StorageServiceProvider(), array());
$app->register(new Bolt\UsersServiceProvider(), array());
$app->register(new Bolt\CacheServiceProvider(), array());
$app->register(new Bolt\ExtensionServiceProvider(), array());

$app['paths'] = getPaths($config);
$app['twig']->addGlobal('paths', $app['paths']);

$app['end'] = getWhichEnd();

$app['editlink'] = "";

// Add the Bolt Twig functions, filters and tags.
$app['twig']->addExtension(new Bolt\TwigExtension($app));
$app['twig']->addTokenParser(new Bolt\SetcontentTokenParser());

// Add the string loader..
$loader = new Twig_Loader_String();
$app['twig.loader']->addLoader($loader);



require __DIR__.'/app.php';

// Initialize enabled extensions.
$app['extensions']->initialize();
