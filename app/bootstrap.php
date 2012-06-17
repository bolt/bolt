<?php

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo "<p>The file <tt>vendor/autoload.php</tt> doesn't exist. Make sure you've installed the Silex/Pilex components with Composer. See the README.md file.</p>";
    die();
}

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/lib.php';
require_once __DIR__.'/storage.php';

// Read the config
$yamlparser = new Symfony\Component\Yaml\Parser();
$config = array();
$config['general'] = $yamlparser->parse(file_get_contents(__DIR__.'/config/config.yml'));
$config['taxonomy'] = $yamlparser->parse(file_get_contents(__DIR__.'/config/taxonomy.yml'));
$config['contenttypes'] = $yamlparser->parse(file_get_contents(__DIR__.'/config/contenttypes.yml'));


$app = new Silex\Application();

$app['debug'] = true;

$app['config'] = $config;

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile'       => __DIR__.'/debug.log',
));

// I don't think i can set Twig's path in runtime, so we have to resort to hackishness to set the 
// path.. if the request URI starts with '/pilex' in the URL, we assume we're in the Backend.. Yeah..
if (strpos($_SERVER['REQUEST_URI'], "/pilex") === 0) {
    $twigpath = __DIR__.'/view';
} else {
    $twigpath = __DIR__.'/../view';
}


$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => $twigpath,
    'twig.options' => array('debug'=>true), 
));

// Add the Pilex Twig functions, filters and tags.
require_once __DIR__.'/twig_pilex.php';

$configdb = $config['general']['database'];

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'    => (isset($configdb['driver']) ? $configdb['driver'] : 'pdo_mysql'),
        'host'      => (isset($configdb['host']) ? $configdb['host'] : 'localhost'),
        'dbname'    => $configdb['databasename'],
        'user'      => $configdb['username'],
        'password'  => $configdb['password'],
        'port'      => (isset($configdb['host']) ? $configdb['host'] : '3306'),
    )
));

$app['storage'] = new Storage($app);


use Silex\Provider\FormServiceProvider;

$app->register(new FormServiceProvider());


require_once __DIR__.'/app_backend.php';

require_once __DIR__.'/app_frontend.php';
