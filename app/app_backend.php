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
 * Dashboard or "root".
 */
$app->get("/pilex", function(Silex\Application $app) {

    // Check DB-tables integrity
    if (!$app['storage']->checkTablesIntegrity()) {
        $app['session']->setFlash('error', "The database needs to be updated / repaired. Go to 'Settings' > 'Check Database' to do this now.");   
    }

    // get the 'latest' from each of the content types. 
    foreach ($app['config']['contenttypes'] as $key => $contenttype) { 
        $latest[$key] = $app['storage']->getContent($key, array('limit' => 5, 'order' => 'datechanged DESC'));   
    }

    return $app['twig']->render('dashboard.twig', array('latest' => $latest));


})->before($checkLogin);




/**
 * Login page.
 */
$app->match("/pilex/login", function(Silex\Application $app, Request $request) {

    if ($request->server->get('REQUEST_METHOD') == "POST") {
      
        $username = strtolower(trim($request->request->get('username')));
    
        if ($username == "admin" && $request->request->get('password') == "password") {
            
            $app['session']->start();
            $app['session']->set('user', array('username' => $request->request->get('username')));
            $app['session']->setFlash('success', "You've been logged on successfully.");    
            
            return $app->redirect('/pilex');
            
        } else {
            $app['session']->setFlash('error', 'Username or password not correct. Please check your input.');    
        }
    
    }
    
    return $app['twig']->render('login.twig');

})->method('GET|POST');


/**
 * Logout page.
 */
$app->get("/pilex/logout", function(Silex\Application $app) {

	$app['session']->setFlash('info', 'You have been logged out.');
    $app['session']->remove('user');
    
    return $app->redirect('/pilex/login');
        
});



/**
 * Check the database, create tables, add missing/new columns to tables
 */
$app->get("/pilex/dbupdate", function(Silex\Application $app) {
	
	$title = "Database check / update";

	$output = $app['storage']->repairTables();
	
	if (empty($output)) {
    	$content = "<p>Your database is already up to date.<p>";
	} else {
    	$content = "<p>Modifications made to the database:<p>";
    	$content .= implode("<br>", $output);
    	$content .= "<p>Your database is now up to date.<p>";
	}
	
	$content .= "<br><br><p><a href='/pilex/prefill'>Fill the database</a> with Loripsum.</p>";
	
	return $app['twig']->render('base.twig', array('title' => $title, 'content' => $content));
	
})->before($checkLogin);


/**
 * Generate some lipsum in the DB.
 */
$app->get("/pilex/prefill", function(Silex\Application $app) {
	
	$title = "Database prefill";

	$content = $app['storage']->preFill();
	
	return $app['twig']->render('base.twig', array('title' => $title, 'content' => $content));
	
})->before($checkLogin);


/**
 * Check the database, create tables, add missing/new columns to tables
 */
$app->get("/pilex/overview/{contenttypeslug}", function(Silex\Application $app, $contenttypeslug) {
	
    $contenttype = $app['storage']->getContentType($contenttypeslug);

	$multiplecontent = $app['storage']->getContent($contenttype['slug'], array('limit' => 100, 'order' => 'datechanged DESC'));

	return $app['twig']->render('overview.twig', array('contenttype' => $contenttype, 'multiplecontent' => $multiplecontent));
	
})->before($checkLogin);


/**
 * Edit a unit of content, or create a new one.
 */
$app->match("/pilex/edit/{contenttypeslug}/{id}", function($contenttypeslug, $id, Silex\Application $app, Request $request) {
        
    $twigvars = array();
    
    $contenttype = $app['storage']->getContentType($contenttypeslug);        
        
    if ($request->server->get('REQUEST_METHOD') == "POST") {
        
        // $app['storage']->saveContent($contenttypeslug)
        if ($app['storage']->saveContent($request->request->all(), $contenttype['slug'])) {
        
            if (!empty($id)) {
                $app['session']->setFlash('success', "The changes to this " . $contenttype['singular_name'] . " have been saved."); 
            } else {
                $app['session']->setFlash('success', "The new " . $contenttype['singular_name'] . " have been saved."); 
            }
            return $app->redirect('/pilex/overview/'.$contenttypeslug);
        
        } else {
        
            $app['session']->setFlash('error', "There was an error saving this " . $contenttype['singular_name'] . "."); 
        
        } 
    
    }      
      
	if (!empty($id)) {
      	$content = $app['storage']->getSingleContent($contenttypeslug, array('where' => 'id = '.$id));
	} else {
    	$content = $app['storage']->getEmptyContent($contenttypeslug);
	}

	// b0rken..
	// $form = $app['form.factory']->createBuilder('form', $content);

	return $app['twig']->render('editcontent.twig', array('contenttype' => $contenttype, 'content' => $content));
	
})->before($checkLogin)->assert('id', '\d*')->method('GET|POST');


use Symfony\Component\Validator\Constraints as Assert;

$app->match("/pilex/users/edit/{id}", function($id, Silex\Application $app, Request $request) {
    
    
    $data = array(
        'id' => '33',
        'username' => 'Your name',
        'password' => 'Your name',
        'password_verification' => 'Your name',
        'email' => 'Your email',
        'displayname' => 'Display name:',
        'userlevel' => 'Userlevel:',
        'enabled' => 'User is allowed to log in:',
        'lastseen' => '2012-06-18 10:10:20',
        'lastip' => '1.2.3.4'

    );

    
    
    $form = $app['form.factory']->createBuilder('form', $data)
        ->add('id', 'hidden')
        ->add('username', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(2))
        ))
        ->add('password')
        ->add('password_verification')
        ->add('email', 'text', array(
            'constraints' => new Assert\Email()
        ))
        ->add('displayname', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(2))
        ))
        ->add('userlevel')
        ->add('enabled', 'choice', array(
            'choices' => array(1 => 'yes', 0 => 'no'), 
            'expanded' => false,
            'constraints' => new Assert\Choice(array(0, 1)) 
        ))
        ->add('lastseen', 'text', array('disabled' => true))
        ->add('lastip', 'text', array('disabled' => true))
        ->getForm();

    return $app['twig']->render('edituser.twig', array('form' => $form->createView()));      
      
})->before($checkLogin)->assert('id', '\d*')->method('GET|POST');



/**
 * Check the database, create tables, add missing/new columns to tables
 */
$app->get("/pilex/users", function(Silex\Application $app) {
	
	$title = "Users";
    $users = $app['users']->getUsers();
    
	return $app['twig']->render('users.twig', array('users' => $users, 'title' => $title));
	
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

    $twigvars['title'] = "An error has occured!";

    return $app['twig']->render('error.twig', $twigvars);

});