<?php
// Google Analytics extension for Bolt

namespace Googleanalytics;


function info() {

    $data = array(
        'author' => "Bob den Otter",
        'version' => 0.2,
        'required_bolt_version' => '0.7.4',
        'type' => "Snippet",
        'description' => "A small extension to add the scripting for a Google Analytics tracker to your site."
    );

    return $data;

}

function init($app) {

    $app['extension']->insertSnippet('beforeclosehead', function() { Googleanalytics\insertAnalytics(); });

}


function insertAnalytics() {

    $app->get("/pompidom", function(Silex\Application $app) {

        $app['twig.path'] = array($themepath, __DIR__.'/view');
        return $app['twig']->render('bla.twig');

    });

}

