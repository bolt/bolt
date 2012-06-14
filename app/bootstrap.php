<?php

$app = new Silex\Application();

$app['debug'] = true;

$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile'       => __DIR__.'/debug.log',
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => dirname(__DIR__).'/view',
    'twig.options' => array('debug'=>true), 
));

$app['twig']->addExtension(new Twig_Extension_Debug());

$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options'            => array(
        'driver'    => 'pdo_mysql',
        'host'      => $config['db_host'],
        'dbname'    => $config['db_dbname'],
        'user'      => $config['db_user'],
        'password'  => $config['db_password'],
    )
));

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Foutmelding
 */
$app->error(function(Exception $e) use ($app) {

    $app['monolog']->addError(json_encode(array(
        'class' => get_class($e),
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'trace' => $e->getTrace()
        )));

    $twigvars = array();

    $twigvars['class'] = get_class($e);
    $twigvars['message'] = $e->getMessage();
    $twigvars['code'] = $e->getCode();

	$trace = $e->getTrace();;

	unset($trace[0]['args']);

    $twigvars['trace'] = print_r($trace[0], true);

    $twigvars['title'] = "Een error!";

    return $app['twig']->render('error.twig', $twigvars);

});
