<?php

/**
 * "root"
 */
$app->get("/pilex", function(Silex\Application $app) {

    $twigvars = array();

    $twigvars['title'] = "Silex skeleton app";

    $twigvars['content'] = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.";

    
    return $app['twig']->render('index.twig', $twigvars);


});

// todo: Move the error function to app_backend.php
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