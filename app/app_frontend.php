<?php

/**
 * "root"
 */
$app->get("/", function(Silex\Application $app) {

    $twigvars = array();

    $twigvars['title'] = "Silex skeleton app";

    $twigvars['content'] = "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.";

    
    return $app['twig']->render('index.twig', $twigvars);


});

