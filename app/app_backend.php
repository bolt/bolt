<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware function to check whether a user is logged on.
 */
$checkLogin = function(Request $request) use ($app) {
   
    $route = $request->get('_route');
          
    // There's an active session, we're all good.
    if ($app['session']->has('user')) {
        return;
    } 
    
    // If the users table is present, but there are no users, and we're on /pilex/users/edit,
    // we let the user stay, because they need to set up the first user. 
    if ($app['storage']->checkUserTableIntegrity() && !$app['users']->getUsers() && $request->getPathInfo()=="/pilex/users/edit/") {
        return;
    } 

    // If there are no users in the users table, or the table doesn't exist. Repair 
    // the DB, and let's add a new user. 
    if (!$app['storage']->checkUserTableIntegrity() || !$app['users']->getUsers()) {
        $app['storage']->repairTables();
        $app['session']->setFlash('info', "There are no users in the database. Please create the first user.");    
        return $app->redirect('/pilex/users/edit');
    }

    $app['session']->setFlash('info', "Please log on.");
    return $app->redirect('/pilex/login');

};

use Silex\ControllerCollection;

$backend = new ControllerCollection($app['route_factory']);


/**
 * Dashboard or "root".
 */
$backend->get("/", function(Silex\Application $app) {

    // Check DB-tables integrity
    if (!$app['storage']->checkTablesIntegrity()) {
        $app['session']->setFlash('error', "The database needs to be updated / repaired. Go to 'Settings' > 'Check Database' to do this now.");   
    }

    // get the 'latest' from each of the content types. 
    foreach ($app['config']['contenttypes'] as $key => $contenttype) { 
        $latest[$key] = $app['storage']->getContent($key, array('limit' => 5, 'order' => 'datechanged DESC'));   
    }

    return $app['twig']->render('dashboard.twig', array('latest' => $latest));

})->before($checkLogin)->bind('dashboard');


/**
 * News.
 */
$backend->get("/dashboardnews", function(Silex\Application $app) {

    $guzzleclient = new Guzzle\Http\Client('http://news.pilex.net/');
        
    $news = $guzzleclient->get("/")->send()->getBody(true);

    $news = json_decode($news);

    // For now, just use the most current item.
    $news = current($news);

    $body = $app['twig']->render('dashboard-news.twig', array('news' => $news ));
    return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

})->before($checkLogin)->bind('dashboardnews');



/**
 * Latest {contenttype} to show a small listing in the sidebars..
 */
$backend->get("/lastmodified/{contenttypeslug}", function(Silex\Application $app, $contenttypeslug) {

    // Get the proper contenttype..
    $contenttype = $app['storage']->getContentType($contenttypeslug);
    
    // get the 'latest' from the requested contenttype. 
    $latest = $app['storage']->getContent($contenttype['slug'], array('limit' => 5, 'order' => 'datechanged DESC'));   

    $body = $app['twig']->render('sidebar-lastmodified.twig', array('latest' => $latest, 'contenttype' => $contenttype ));
    return new Response($body, 200, array('Cache-Control' => 's-maxage=60, public'));

})->before($checkLogin)->bind('lastmodified');






/**
 * Login page.
 */
$backend->match("/login", function(Silex\Application $app, Request $request) {

    if ($request->getMethod() == "POST") {
      
        $username = makeSlug($request->get('username'));
  
        // echo "<pre>\n" . print_r($request->get('username') , true) . "</pre>\n";
    
        $result = $app['users']->login($request->get('username'), $request->get('password'));
        
        if ($result) {
            return $app->redirect('/pilex');
        }
    
    }
    
    return $app['twig']->render('login.twig');

})->method('GET|POST')->bind('login');


/**
 * Logout page.
 */
$backend->get("/logout", function(Silex\Application $app) {

	$app['session']->setFlash('info', 'You have been logged out.');
    $app['session']->remove('user');
    
    return $app->redirect('/pilex/login');
        
})->bind('logout');



/**
 * Check the database, create tables, add missing/new columns to tables
 */
$backend->get("/dbupdate", function(Silex\Application $app) {
	
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
	
	return $app['twig']->render('base.twig', array(
	   'title' => $title, 
	   'content' => $content,
	   'active' => "settings"
	   ));
	
})->before($checkLogin)->bind('dbupdate');




/**
 * Clear the cache.
 */
$backend->get("/clearcache", function(Silex\Application $app) {
	
	$result = clearCache();
		
	$output = sprintf("Deleted %s files from cache.", $result['successfiles']);
	
	if (!empty($result['failedfiles'])) {
    	$output .= sprintf(" %s files could not be deleted. You should delete them manually.", $result['failedfiles']);
    	$app['session']->setFlash('error', $output);
	} else {
    	$app['session']->setFlash('success', $output);
	}
	
	return $app->redirect('/pilex/');
	
	
})->before($checkLogin)->bind('clearcache');



/**
 * Generate some lipsum in the DB.
 */
$backend->get("/prefill", function(Silex\Application $app) {
	
	$title = "Database prefill";

	$content = $app['storage']->preFill();
	
	return $app['twig']->render('base.twig', array('title' => $title, 'content' => $content));
	
})->before($checkLogin)->bind('prefill');


/**
 * Check the database, create tables, add missing/new columns to tables
 */
$backend->get("/overview/{contenttypeslug}", function(Silex\Application $app, $contenttypeslug) {
	
    $contenttype = $app['storage']->getContentType($contenttypeslug);
    
    $order = 'datechanged DESC';
    
    if (!empty($contenttype['sort'])) {
        $order = $contenttype['sort'];
    }

    $pager = array();

	$multiplecontent = $app['storage']->getContent($contenttype['slug'], array('limit' => 10, 'order' => $order, 'page' => 2), $pager);

	return $app['twig']->render('overview.twig', 
	       array('contenttype' => $contenttype, 'multiplecontent' => $multiplecontent, 'pager' => $pager)
	   );
	
})->before($checkLogin)->bind('overview');


/**
 * Edit a unit of content, or create a new one.
 */
$backend->match("/edit/{contenttypeslug}/{id}", function($contenttypeslug, $id, Silex\Application $app, Request $request) {
        
    $twigvars = array();
    
    $contenttype = $app['storage']->getContentType($contenttypeslug);        
        
    if ($request->getMethod() == "POST") {
        
        if ($app['storage']->saveContent($request->request->all(), $contenttype['slug'])) {
        
            if (!empty($id)) {
                $app['session']->setFlash('success', "The changes to this " . $contenttype['singular_name'] . " have been saved."); 
            } else {
                $app['session']->setFlash('success', "The new " . $contenttype['singular_name'] . " has been saved."); 
            }
            return $app->redirect('/pilex/overview/'.$contenttype['slug']);
        
        } else {
            $app['session']->setFlash('error', "There was an error saving this " . $contenttype['singular_name'] . "."); 
        } 
    
    }      
      
	if (!empty($id)) {
      	$content = $app['storage']->getSingleContent($contenttype['slug'], array('where' => 'id = '.$id));
	} else {
    	$content = $app['storage']->getEmptyContent($contenttype['slug']);
    }

	if (!empty($_GET['duplicate'])) {
    	$content['id']="";
    	$content['datecreated']="";
    	$content['datechanged']="";
    	$content['username']="";
    	$app['session']->setFlash('info', "Content was duplicated. Click 'Save " . $contenttype['singular_name'] . "' to finalize."); 
	}

	// Set the users and the current owner of this content.
	if (!empty($content['username'])) {
    	$contentowner = $content['username'];
	} else {
    	$user = $app['session']->get('user');
    	$contentowner = $user['username'];
	}

	return $app['twig']->render('editcontent.twig', array(
    	   'contenttype' => $contenttype, 
    	   'content' => $content, 
    	   'contentowner' => $contentowner
	   ));
	
})->before($checkLogin)->assert('id', '\d*')->method('GET|POST')->bind('editcontent');





/**
 * Perform actions on content.
 */
$backend->get("/content/{action}/{contenttypeslug}/{id}", function(Silex\Application $app, $action, $contenttypeslug, $id, Request $request) {

    $contenttype = $app['storage']->getContentType($contenttypeslug);    

    $content = "";

    $token = getToken();
    

    switch ($action) {
        
        case "depublish":
            if ($app['storage']->changeContent($contenttype['slug'], $id, 'status', 'depublished')) {
                $app['session']->setFlash('info', "Content 'pompidom' is depublished.");
            } else {
                $app['session']->setFlash('info', "Content 'pompidom' could not be depublished.");
            }
            return $app->redirect('/pilex/overview/'.$contenttype['slug']); 
            break;
        
        case "publish":
            if ($app['storage']->changeContent($contenttype['slug'], $id, 'status', 'published')) {
                $app['session']->setFlash('info', "Content 'pompidom' is published.");
            } else {
                $app['session']->setFlash('info', "Content 'pompidom' could not be published.");
            }
            return $app->redirect('/pilex/overview/'.$contenttype['slug']); 
            break;
            
        case "draft":
            if ($app['storage']->changeContent($contenttype['slug'], $id, 'status', 'draft')) {
                $app['session']->setFlash('info', "Content 'pompidom' is published.");
            } else {
                $app['session']->setFlash('info', "Content 'pompidom' could not be published.");
            }
            return $app->redirect('/pilex/overview/'.$contenttype['slug']); 
            break;            
                    
        case "delete":
            
            if (checkToken() && $app['storage']->deleteContent($contenttype['slug'], $id)) {
                $app['session']->setFlash('info', "Content 'pompidom' has been deleted.");
            } else {
                $app['session']->setFlash('info', "Content 'pompidom' could not be deleted.");    
            }
            return $app->redirect('/pilex/overview/'.$contenttype['slug']);         
            break;
                
        default:
            $app['session']->setFlash('error', "No such action for content.");
            return $app->redirect('/pilex/overview/'.$contenttype['slug']); 
        
    }

	
})->before($checkLogin)->bind('contentaction');





// use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\CallbackValidator;
use Symfony\Component\Validator\Constraints as Assert;

$backend->match("/users/edit/{id}", function($id, Silex\Application $app, Request $request) {
    
    // Get the user we want to edit (if any)    
    if (!empty($id)) {
        $user = $app['users']->getUser($id);
        $title = "Edit a user";
    } else {
        $user = $app['users']->getEmptyUser();
        $title = "Create a new user";
    }
    
    $userlevels = $app['users']->getUserLevels();
    $enabledoptions = array(1 => 'yes', 0 => 'no');
    // If we're creating the first user, we should make sure that we can only create
    // a user that's allowed to log on.
    if(!$app['users']->getUsers()) {
        $userlevels = array_slice($userlevels, -1);
        $enabledoptions = array(1 => 'yes');
        $title = "Create the first user";        
    }
    
    // Start building the form..
    $form = $app['form.factory']->createBuilder('form', $user)
        ->add('id', 'hidden')
        ->add('username', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(2))
        ));
        
    // If we're adding a new user, the password will be mandatory. If we're
    // editing an existing user, we can leave it blank
    if (empty($id)) {
        $form->add('password', 'password', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(6)),
            ))
            ->add('password_confirmation', 'password', array(
                'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(6)),
                'label' => "Password (confirmation)"
            ));
    } else {
        $form->add('password', 'password', array(
                'required' => false
            ))
            ->add('password_confirmation', 'password', array(
                'required' => false,
                'label' => "Password (confirmation)"
            ));        
    }
        
    // Contiue with the rest of the fields.
    $form->add('email', 'text', array(
            'constraints' => new Assert\Email(),
        ))
        ->add('displayname', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(2))
        ))
        ->add('userlevel', 'choice', array(
            'choices' => $userlevels, 
            'expanded' => false,
            'constraints' => new Assert\Choice(array_keys($userlevels)) 
        ))
        ->add('enabled', 'choice', array(
            'choices' => $enabledoptions, 
            'expanded' => false,
            'constraints' => new Assert\Choice(array_keys($enabledoptions)), 
            'label' => "User is enabled",
        ))
        ->add('lastseen', 'text', array('disabled' => true))
        ->add('lastip', 'text', array('disabled' => true));
        
    // Make sure the passwords are identical with a custom validator..
    $form->addValidator(new CallbackValidator(function($form) {
    
        $pass1 = $form['password']->getData();
        $pass2 = $form['password_confirmation']->getData();
    
        // Some checks for the passwords.. 
        if (!empty($pass1) && strlen($pass1)<6 ) {
            $form['password']->addError(new FormError('This value is too short. It should have 6 characters or more.'));
        } else if ($pass1 != $pass2 ) {
            $form['password_confirmation']->addError(new FormError('Passwords must match.'));
        }
                    
    }));
        
    $form = $form->getForm();
       
    // Check if the form was POST-ed, and valid. If so, store the user.
    if ($request->getMethod() == "POST") {
        //$form->bindRequest($request);
        $form->bind($app['request']->get($form->getName()));

        if ($form->isValid()) {
            
            $user = $form->getData();
        
            $res = $app['users']->saveUser( $user );
            
            if ($res) {
                $app['session']->setFlash('success', "User " . $user['username'] . " has been saved."); 
            } else {
                $app['session']->setFlash('error', "User " . $user['username'] . " could not be saved, or nothing was changed."); 
            }
            
            return $app->redirect('/pilex/users');
            
        }
    }

    return $app['twig']->render('edituser.twig', array(
        'form' => $form->createView(),
        'title' => $title
        ));      
      
})->before($checkLogin)->assert('id', '\d*')->method('GET|POST')->bind('useredit');


/**
 * Show the 'about' page */
$backend->get("/about", function(Silex\Application $app) {
	   
	return $app['twig']->render('about.twig');
	
})->before($checkLogin)->bind('about');




/**
 * Show a list of all available users.
 */
$backend->get("/users", function(Silex\Application $app) {
	
	$title = "Users";
    $users = $app['users']->getUsers();
    
	return $app['twig']->render('users.twig', array('users' => $users, 'title' => $title));
	
})->before($checkLogin)->bind('users');


/**
 * Perform actions on users.
 */
$backend->get("/user/{action}/{id}", function(Silex\Application $app, $action, $id) {


    $user = $app['users']->getUser($id);
    
    if (!$user) {
        $app['session']->setFlash('error', "No such user.");
        return $app->redirect('/pilex/users'); 
    }

    switch ($action) {
        
        case "disable":
            if ($app['users']->setEnabled($id, 0)) {
                $app['session']->setFlash('info', "User '{$user['displayname']}' is disabled.");
            } else {
                $app['session']->setFlash('info', "User '{$user['displayname']}' could not be disabled.");
            }
            return $app->redirect('/pilex/users'); 
            break;
        
        case "enable":
            if ($app['users']->setEnabled($id, 1)) {
                $app['session']->setFlash('info', "User '{$user['displayname']}' is enabled.");
            } else {
                $app['session']->setFlash('info', "User '{$user['displayname']}' could not be enabled.");        
            }
            return $app->redirect('/pilex/users'); 
            break;
                    
        case "delete":
            
            if (checkToken() && $app['users']->deleteUser($id)) {    
                $app['session']->setFlash('info', "User '{$user['displayname']}' is deleted.");
            } else {
                $app['session']->setFlash('info', "User '{$user['displayname']}' could not be deleted.");    
            }
            return $app->redirect('/pilex/users');         
            break;
                
        default:
            $app['session']->setFlash('error', "No such action for user '{$user['displayname']}'.");
            return $app->redirect('/pilex/users'); 
        
    }

	
})->before($checkLogin)->bind('useraction');



$backend->get("/files/{path}", function($path, Silex\Application $app, Request $request) {

    $files = array();
    $folders = array();
    
    $basefolder = __DIR__."/../";
    $path = stripTrailingSlash(str_replace("..", "", $path));
    $currentfolder = realpath($basefolder.$path);
    
    $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");
    
    // Get the pathsegments, so we can show the path..
    $pathsegments = array();
    $cumulative = "";
    if (!empty($path)) {
        foreach(explode("/", $path) as $segment) {
            $cumulative .= $segment . "/";
            $pathsegments[ $cumulative ] = $segment;
        }
    }
    
    
    if (file_exists($currentfolder)) {
    
        $d = dir($currentfolder);
    
        while (false !== ($entry = $d->read())) {
           
            if (in_array($entry, $ignored)) { continue; }
            
            $fullfilename = $currentfolder."/".$entry;
                        
            if (is_file($fullfilename)) {
                $files[$entry] = array(
                    'path' => $path,
                    'filename' => $entry,
                    'newpath' => $path . "/" . $entry,
                    'writable' => is_writable($fullfilename),
                    'type' => getExtension($entry),
                    'filesize' => formatFilesize(filesize($fullfilename)),
                    'modified' => date("Y/m/d H:i:s", filemtime($fullfilename)),
                    'permissions' => getFilePermissions($fullfilename)
                );   
                
                if (in_array(getExtension($entry), array('gif', 'jpg', 'png', 'jpeg'))) {
                    $size = getimagesize($fullfilename);
                    $files[$entry]['imagesize'] = sprintf("%s × %s", $size[0], $size[1]);
                }
            }
            
            if (is_dir($fullfilename)) {
                $folders[$entry] = array(
                    'path' => $path,
                    'foldername' => $entry,
                    'newpath' => $path . "/" . $entry,
                    'writable' => is_writable($fullfilename),
                    'modified' => date("Y/m/d H:i:s", filemtime($fullfilename))
                );      
            }
                  
           
        }
        
        $d->close();
    
    
    } else {
        $result['log'] .= "Folder $currentfolder doesn't exist.<br>";
        $app['session']->setFlash('error', "File '" .$file.".yml' could not be saved: not valid YAML."); 
    }


    return $app['twig']->render('files.twig', array(
        'path' => $path,
        'files' => $files,
        'folders' => $folders,
        'pathsegments' => $pathsegments
        ));          

})->before($checkLogin)->assert('path', '.+')->bind('files');



$backend->match("/file/edit/{file}", function($file, Silex\Application $app, Request $request) {
    
    $title = "Edit file '$file'.";

    $filename = realpath(__DIR__."/../".$file);
    $type = getExtension($filename);
    
    if (!file_exists($filename) || !is_readable($filename)) {
        $error = sprintf("file '%s/config/%s' doesn't exist, or is not readable." , basename(__DIR__), $file);
        $app->abort(404, $error);
    }

    if (!is_writable($filename)) {
        $error = sprintf("file '%s/config/%s.yml' is not writable." , basename(__DIR__), $file);
        $app->abort(404, $error);
    }
    

    $data['contents'] = file_get_contents($filename);

    $form = $app['form.factory']->createBuilder('form', $data)
        ->add('contents', 'textarea', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\MinLength(10))
        ));
        
    $form = $form->getForm();
       
    // Check if the form was POST-ed, and valid. If so, store the user.
    if ($request->getMethod() == "POST") {
        //$form->bindRequest($request);
        $form->bind($app['request']->get($form->getName()));

        if ($form->isValid()) {
            
            $data = $form->getData();
            $contents = str_replace("\t", "    ", $data['contents']);
            
            $ok = true;
            
            if ($type == "yml") {
                $yamlparser = new Symfony\Component\Yaml\Parser();
                try {
                    $ok = $yamlparser->parse($contents);
                } catch (Exception $e) {
                    $ok = false;
                    $app['session']->setFlash('error', "File '" .$file."' could not be saved: not valid YAML.");
                }
            }
            
            if ($ok) {
                if (file_put_contents($filename, $contents)) {
                    $app['session']->setFlash('success', "File '" .$file."' has been saved."); 
                } else {
                    $app['session']->setFlash('error', "File '" .$file."' could not be saved, for some reason."); 
                }
            }
            
            return $app->redirect('/pilex/file/edit/'. $file);
            
        }
    }
    

    return $app['twig']->render('editconfig.twig', array(
        'form' => $form->createView(),
        'title' => $title,
        'filetype' => $type
        ));      
      
})->before($checkLogin)->assert('file', '.+')->method('GET|POST')->bind('fileedit');





$backend->get("/filesautocomplete", function(Silex\Application $app, Request $request) {

    $term = $request->get('term');


    $files = findFiles($term, 'jpg,jpeg,gif,png');
    
    $app['debug'] = false;
 
    return $app->json($files);

})->before($checkLogin);


// Temporary hack. Silex should start session on demand.
$app->before(function() use ($app) {
    global $pilex_name, $pilex_version;
    
    $app['session']->start();
    
    $app['twig']->addGlobal('pilex_name', $pilex_name);
    $app['twig']->addGlobal('pilex_version', $pilex_version);
    $app['twig']->addGlobal('users', $app['users']->getUsers());
    $app['twig']->addGlobal('config', $app['config']);
    
});


// On 'finish' attach the debug-bar, if debug is enabled..
if ($app['debug']) {
    
    // http://srcmvn.com/blog/2011/11/10/doctrine-dbal-query-logging-with-monolog-in-silex/

    $logger = new Doctrine\DBAL\Logging\DebugStack();
    $app['db.config']->setSQLLogger($logger);
    $app->error(function(\Exception $e, $code) use ($app, $logger) {
        if ( $e instanceof PDOException and count($logger->queries) ) {
            // We want to log the query as an ERROR for PDO exceptions!
            $query = array_pop($logger->queries);
            $app['monolog']->err($query['sql'], array(
                'params' => $query['params'],
                'types' => $query['types']
            ));
        }
    });
        
    
    $app->finish(function(Request $request, Response $response) use ($app, $logger) {

        // Make sure debug is _still_ enabled..
        if (!$app['debug']) {
            return "";
        }

        $queries = array();
        $querycount = 0;
        $querytime = 0;
           
        foreach ( $logger->queries as $query ) {
            $queries[] = array(
                'query' => $query['sql'], 
                'params' => $query['params'],
                'types' => $query['types'], 
                'duration' => sprintf("%0.2f", $query['executionMS'])
            );
            
            $querycount++;
            $querytime += $query['executionMS'];
            
        }    


        $twig = $app['twig.loader'];
        $templates = hackislyParseRegexTemplates($twig);
        
        $route = $request->get('_route') ;
        $route_params = $request->get('_route_params') ;
        
        
        $servervars = array(
            'cookies <small>($_COOKIES)</small>' => $request->cookies->all(),
            'headers' => makeValuepairs($request->headers->all(), '', '0'),
            'query <small>($_GET)</small>' => $request->query->all(),
            'request <small>($_POST)</small>' => $request->request->all(),
            'session <small>($_SESSION)</small>' => $request->getSession()->all(),
            'server <small>($_SERVER)</small>' => $request->server->all(),
            'response' => makeValuepairs($response->headers->all(), '', '0'),
            'statuscode' => $response->getStatusCode()
        );
        
     
        echo $app['twig']->render('debugbar.twig', array(
            'timetaken' => timeTaken(),
            'memtaken' => getMem(),
            'memtaken' => getMaxMem(),
            'querycount' => $querycount,
            'querytime' => sprintf("%0.2f", $querytime),
            'queries' => $queries,
            'servervars' => $servervars,
            'templates' => $templates,
            'route' => "/".$route,
            'route_params' => json_encode($route_params)
        ));
    
    

    });

} 



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


$app->mount('/pilex', $backend);

