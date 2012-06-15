<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    if (!$app['storage']->checkTablesIntegrity()) {
        $app['session']->setFlash('error', "The database needs to be updated / repaired. Go to 'Settings' > 'Check Database' to do this now.");   
    }

    
    return $app['twig']->render('index.twig', $twigvars);


})->before($checkLogin);




/**
 * Login page
 */
$app->match("/pilex/login", function(Silex\Application $app, Request $request) {

    $twigvars = array();
    

    if ($request->server->get('REQUEST_METHOD') == "POST") {
    
	    if ($request->request->get('username') == "admin" && $request->request->get('password') == "password") {
	        
	        $app['session']->start();
	        $app['session']->set('user', array('username' => $request->request->get('username')));
	        $app['session']->setFlash('success', "You've been logged on successfully.");    
	        
	        return $app->redirect('/pilex');
	        
	    } else {
	        $app['session']->setFlash('error', 'Username or password not correct. Please check your input.');    
	    }
    
    }
    
    return $app['twig']->render('login.twig', $twigvars);

})->method('GET|POST');


/**
 * Logout page
 */
$app->get("/pilex/logout", function(Silex\Application $app) {

	$app['session']->setFlash('info', 'You have been logged out.');
    $app['session']->remove('user');
    
    return $app->redirect('/pilex/login');
        
});




$app->get("/pilex/dbupdate", function(Silex\Application $app) {
	
	
	$twigvars = array();
	
	$twigvars['title'] = "Database check / update";
	
	$twigvars['content'] = "If you didn't get any errors, the DB should be up to date now. ";
	
	$app['storage']->repairTables();
	
	return $app['twig']->render('base.twig', $twigvars);
	
})->before($checkLogin);



/**
 * Error page.
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