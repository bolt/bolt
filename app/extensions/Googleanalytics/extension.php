<?php
// Google Analytics extension for Bolt

namespace GoogleAnalytics;


function info() {

    $data = array(
        'name' =>"Google Analytics",
        'description' => "A small extension to add the scripting for a Google Analytics tracker to your site.",
        'author' => "Bob den Otter",
        'link' => "http://bolt.cm",
        'version' => 0.1,
        'required_bolt_version' => 0.8,
        'type' => "Snippet",
        'releasedate' => "2012-10-10"
    );

    return $data;

}

function init($app) {

    // $app['extensions']->insertSnippet('beforeclosehead', function() { GoogleAnalytics\insertAnalytics(); });

}


function insertAnalytics() {

    $app->get("/pompidom", function(Silex\Application $app) {

        $app['twig.path'] = array($themepath, __DIR__.'/view');
        return $app['twig']->render('bla.twig');

    });

}

