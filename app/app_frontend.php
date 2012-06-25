<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    


});



$app->get('/weblog/{contenttypeslug}', function (Silex\Application $app, $contenttypeslug ) {
    
    $contenttype = $app['storage']->getContentType($contenttypeslug);
    
    
    if (!$contenttype) {
        $app->abort(404, "Page /weblog/$contenttypeslug not found.");
    }
    
    $contentgroup =  $app['storage']->getContent($contenttype['slug'], array('limit' => "10", 'order' => "datecreated DESC"));
    
    $body = $app['twig']->render('_sub_weblog.twig', array(
        'contentgroup' => $contentgroup, 
        'contenttype' => $contenttype
    ));
    return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));
    
    
});




$app->get('/linklist/{contenttypeslug}', function (Silex\Application $app, $contenttypeslug ) {
    
    $contenttype = $app['storage']->getContentType($contenttypeslug);
    
    
    if (!$contenttype) {
        $app->abort(404, "Page /linklist/$contenttypeslug not found.");
    }
    
    $contentgroup =  $app['storage']->getContent($contenttype['slug'], array('limit' => "10", 'order' => "datecreated DESC"));
    
    $body = $app['twig']->render('_sub_linklist.twig', array(
        'contentgroup' => $contentgroup, 
        'contenttype' => $contenttype
    ));
    
    return new Response($body, 200, array('Cache-Control' => 's-maxage=3600, public'));
    
    
});




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
    
    // return $app['twig']->render($contenttype['template'], array('content' => $content));
    

    
    
});


