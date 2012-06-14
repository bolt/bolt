<?php

use Symfony\Component\HttpFoundation\Request;

/**
 * Middleware function to check whether a user is logged on.
 */
$checkLogin = function(Request $request) use ($app) {
    
    if (!$app['session']->has('user')) {
        return $app->redirect('/pilex/login');
    }
    
};


/**
 * "root"
 */
$app->get("/pilex", function(Silex\Application $app) {

    $twigvars = array();

    $twigvars['title'] = "Silex skeleton app";

    $twigvars['content'] = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.";

    
    return $app['twig']->render('index.twig', $twigvars);


})->before($checkLogin);




/**
 * Login page
 */
$app->match("/pilex/login", function(Silex\Application $app, Request $request) {


    $twigvars = array();
    

    
    if ($request->request->get('username') == "admin" && $request->request->get('password') == "password") {
        
        $app['session']->start();
        $app['session']->set('user', array('username' => $request->request->get('username')));
        
        return $app->redirect('/pilex');
        
    } else {
        
        $twigvars['message'] = "Username or password not correct. Please check your input.";
        
    }
    

    return $app['twig']->render('login.twig', $twigvars);


})->method('GET|POST');


/**
 * Login page
 */
$app->get("/pilex/logout", function(Silex\Application $app) {


    $app['session']->remove('user');
    
    return $app->redirect('/pilex');
        
});



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