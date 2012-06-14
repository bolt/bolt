<?php

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo "<p>The file <tt>vendor/autoload.php</tt> doesn't exist. Make sure you've installed the Silex/Pilex components with Composer. See the README.md file.</p>";
    die();
}

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/lib.php';


// Read the config
$yamlparser = new Symfony\Component\Yaml\Parser();
$config = $yamlparser->parse(file_get_contents(__DIR__.'/config/config.yml'));

// echo "<pre>";
// print_r($config);
// echo "</pre>";


$app = new Silex\Application();

$app['debug'] = true;

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile'       => __DIR__.'/debug.log',
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__.'/view',
    'twig.options' => array('debug'=>true), 
));

$app['twig']->addExtension(new Twig_Extension_Debug());


$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options'            => array(
        'driver'    => (isset($config['database']['driver']) ? $config['database']['driver'] : 'pdo_mysql'),
        'host'      => (isset($config['database']['host']) ? $config['database']['host'] : 'localhost'),
        'dbname'    => $config['database']['databasename'],
        'user'      => $config['database']['username'],
        'password'  => $config['database']['password'],
        'port'      => (isset($config['database']['host']) ? $config['database']['host'] : '3306'),
    )
));



require_once __DIR__.'/app_backend.php';

require_once __DIR__.'/app_frontend.php';
