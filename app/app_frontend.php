<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware function to check whether a user is logged on.
 */
$checkStuff = function(Request $request) use ($app) {
   
    // If there are no users in the users table, or the table doesn't exist. Repair 
    // the DB, and let's add a new user. 
    if (!$app['storage']->checkUserTableIntegrity() || !$app['users']->getUsers()) {
        $app['storage']->repairTables();
        $app['session']->setFlash('info', "There are no users in the database. Please create the first user.");    
        return $app->redirect('/pilex/users/edit/');
    }

};

/**
 * Homepage..
 */
$app->get("/", function(Silex\Application $app) {

    $template = !empty($app['config']['general']['homepage_template']) ? 
            $app['config']['general']['homepage_template'] : "index.twig";

    if (!empty($app['config']['general']['homepage_template'])) {
        $content = $app['storage']->getSingleContent($app['config']['general']['homepage']);
    } else {
        $content = false;
    }

    $body = $app['twig']->render('index.twig');
    return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));

})->before($checkStuff);




$app->get('/{contenttypeslug}/{slug}', function (Silex\Application $app, $contenttypeslug, $slug) {
    
    $contenttype = $app['storage']->getContentType($contenttypeslug);
    
    $slug = makeSlug($slug);
    
    if (!$contenttype) {
        $app->abort(404, "Page $contenttypeslug/$slug not found.");
    }
    
    $content = $app['storage']->getSingleContent($contenttype['slug'], array('where' => "slug = '$slug'"));

    if (!$content && is_numeric($slug)) {
        // try getting it by ID
        $content = $app['storage']->getSingleContent($contenttype['slug'], array('where' => "id = '$slug'"));
    }
    
    if (!$content) {
        $app->abort(404, "Page $contenttypeslug/$slug not found.");
    }
    
    if (!isset($contenttype['template'])) {
        $app->abort(404, "No template for '$contenttypeslug' defined.");
    } 
   

    $body = $app['twig']->render($contenttype['template'], array('content' => $content));
    return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));
    
});


