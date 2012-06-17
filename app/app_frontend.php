<?php

/**
 * "root"
 */
$app->get("/", function(Silex\Application $app) {

    $twigvars = array();

    $twigvars['title'] = "Frontpage";

    $twigvars['content'] = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.";

    
    return $app['twig']->render('index.twig', $twigvars);


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
   

    
    return $app['twig']->render($contenttype['template'], array('content' => $content));
    

    
    
});
