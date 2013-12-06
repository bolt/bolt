<?php

mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

if (!defined( 'BOLT_PROJECT_ROOT_DIR')) {
    if (substr(dirname(__FILE__), -21) == '/vendor/bolt/bolt/app') { // installed bolt with composer
        define('BOLT_COMPOSER_INSTALLED', true);
        define('BOLT_PROJECT_ROOT_DIR', substr(dirname(__FILE__), 0, -21));
        define('BOLT_WEB_DIR', BOLT_PROJECT_ROOT_DIR.'/web');
        define('BOLT_CONFIG_DIR', BOLT_PROJECT_ROOT_DIR.'/config');
    } else {
        define('BOLT_COMPOSER_INSTALLED', false);
        define('BOLT_PROJECT_ROOT_DIR', dirname(dirname(__FILE__)));
        define('BOLT_WEB_DIR', BOLT_PROJECT_ROOT_DIR);

        // Set the config folder location. If we haven't set the constant in index.php, use one of the
        // default values.
        if (!defined("BOLT_CONFIG_DIR")) {
            if (file_exists(dirname(__FILE__).'/config')) {
                // Default value, /app/config/..
                define('BOLT_CONFIG_DIR', dirname(__FILE__).'/config');
            } else {
                // otherwise use /config, outside of the webroot folder.
                define('BOLT_CONFIG_DIR', dirname(dirname(dirname(__FILE__))).'/config');
            }
        }
    }
}

// First, do some low level checks, like whether autoload is present, the cache
// folder is writable, if the minimum PHP version is present, etc.
require_once dirname(__FILE__).'/classes/lib.php';
require_once dirname(__FILE__).'/classes/lowlevelchecks.php';

$checker = new LowlevelChecks();
$checker->doChecks();

// Let's get on with the rest..
require_once BOLT_PROJECT_ROOT_DIR.'/vendor/autoload.php';
require_once __DIR__.'/classes/util.php';

// Start the timer:
$starttime = getMicrotime();

$app = new Bolt\Application();

$app->register(new Bolt\Provider\ConfigServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider(), array(
    'session.storage.options' => array(
        'name' => 'bolt_session',
        'cookie_secure' => $app['config']->get('general/cookies_https_only'),
        'cookie_httponly' => true
    )
));
$app->register(new Bolt\Provider\LogServiceProvider());

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

// Set default locale
$locale = array(
    $app['config']->get('general/locale') . '.utf8',
    $app['config']->get('general/locale'),
    'en_GB.utf8', 'en_GB', 'en'
);
setlocale(LC_ALL, $locale);

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => $app['config']->get('twigpath'),
    'twig.options' => array(
        'debug'=>true,
        'cache' => __DIR__.'/cache/',
        'strict_variables' => $app['config']->get('general/strict_variables'),
        'autoescape' => true )
));

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => $dboptions
));

if ($dboptions['driver']=="pdo_sqlite") {
    $app['db']->query("PRAGMA synchronous = OFF");
} else if ($dboptions['driver']=="pdo_mysql") {
    // https://groups.google.com/forum/?fromgroups=#!topic/silex-php/AR3lpouqsgs
    $app['db']->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    // set utf8 on names and connection as all tables has this charset
    $app['db']->query("SET NAMES 'utf8';");
    $app['db']->query("SET CHARACTER SET 'utf8';");
    $app['db']->query("SET CHARACTER_SET_CONNECTION = 'utf8';");
}

$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => __DIR__.'/cache/',
));


// Setup Swiftmailer, with optional SMTP settings. If no settings are provided in config.yml, mail() is used.
$app->register(new Silex\Provider\SwiftmailerServiceProvider());
if ($app['config']->get('general/mailoptions')) {
    $app['swiftmailer.options'] = $app['config']->get('general/mailoptions');
}

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array());

// Loading stub functions for when intl / IntlDateFormatter isn't available.
if (!function_exists('intl_get_error_code')) {
    require_once BOLT_PROJECT_ROOT_DIR.'/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/functions.php';
    require_once BOLT_PROJECT_ROOT_DIR.'/vendor/symfony/locale/Symfony/Component/Locale/Resources/stubs/IntlDateFormatter.php';
}

$app->register(new Bolt\Provider\TranslationServiceProvider());
$app->register(new Bolt\Provider\StorageServiceProvider());
$app->register(new Bolt\Provider\UsersServiceProvider());
$app->register(new Bolt\Provider\CacheServiceProvider());
$app->register(new Bolt\Provider\ExtensionServiceProvider());
$app->register(new Bolt\Provider\StackServiceProvider());

$app['paths'] = getPaths($app['config']);
$app['twig']->addGlobal('paths', $app['paths']);

// Initialize the 'editlink' and 'edittitle'..
$app['editlink'] = "";
$app['edittitle'] = "";

// Set the Krumo default configuration.
\Krumo::setConfig(array(
    'skin' => array(
        'selected' => "stylish"
    ),
    'display' => array(
        'show_version' => false,
        'show_call_info' => false,
        'cascade' => array(10,5,1),
        'truncate_length' => 70,
        'sort_arrays' => false
    ),
    'dont_traverse' => array(
        'objects' => array('Bolt\Application')
    )
));

// Add the Bolt Twig functions, filters and tags.
$app['twig']->addExtension(new Bolt\TwigExtension($app));
$app['twig']->addTokenParser(new Bolt\SetcontentTokenParser());

require __DIR__.'/app.php';

// Initialize enabled extensions.
$app['extensions']->initialize();
