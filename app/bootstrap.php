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

        // Set the config folder location. If we haven't set the constant in index.php, use one of the
        // default values.
        if (!defined("BOLT_CONFIG_DIR")) {
            if (file_exists(__DIR__.'/config')) {
                // Default value, /app/config/..
                define('BOLT_CONFIG_DIR', __DIR__.'/config');
            } else {
                // otherwise use /config, outside of the webroot folder.
                define('BOLT_CONFIG_DIR', dirname(dirname(__DIR__)).'/config');
            }
        }
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
$starttime = getMicrotime();

$app = new Bolt\Application();

$app->register(new Silex\Provider\SessionServiceProvider(), array(
    'session.storage.options' => array(
        'name' => 'bolt_session'
    )
));
$app->register(new Bolt\ConfigServiceProvider());
$app->register(new Bolt\LogServiceProvider(), array());

// Finally, check if the app/database folder is writable, if it needs to be.
$checker->doDatabaseCheck($app['config']);

$dboptions = $app['config']->getDBOptions();

$app['debug'] = $app['config']->get('general/debug', false);
$app['debugbar'] = false;

list ($app['locale'], $app['territory']) = explode('_', $app['config']->get('general/locale'));

// Set The Timezone Based on the Config, fallback to UTC
date_default_timezone_set(
    $app['config']->get('general/timezone')?:'UTC'
);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => $app['config']->get('twigpath'),
    'twig.options' => array(
        'debug'=>true,
        'cache' => __DIR__.'/cache/',
        'strict_variables' => $app['config']->get('general/strict_variables'),
        'autoescape' => true )
));

// Add the string loader..
$loader = new Twig_Loader_String();
$app['twig.loader']->addLoader($loader);


$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $dboptions
));

if ($dboptions['driver']=="pdo_sqlite") {
    $app['db']->query("PRAGMA synchronous = OFF");
} else if ($dboptions['driver']=="pdo_mysql") {
    // https://groups.google.com/forum/?fromgroups=#!topic/silex-php/AR3lpouqsgs
    $app['db']->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
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
$app->register(new Bolt\StorageServiceProvider(), array());
$app->register(new Bolt\UsersServiceProvider(), array());
$app->register(new Bolt\CacheServiceProvider(), array());
$app->register(new Bolt\ExtensionServiceProvider(), array());

$app['paths'] = getPaths($app['config']);
$app['twig']->addGlobal('paths', $app['paths']);

$app['editlink'] = "";

// Add the Bolt Twig functions, filters and tags.
$app['twig']->addExtension(new Bolt\TwigExtension($app));
$app['twig']->addTokenParser(new Bolt\SetcontentTokenParser());


require __DIR__.'/app.php';

// Initialize enabled extensions.
$app['extensions']->initialize();
