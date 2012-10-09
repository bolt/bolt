<?php
// Google Analytics extension for Bolt

namespace Googleanalytics;

class Extension {

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
        echo "POMPIDOM..";

        //$app['twig']->addFunction('otherFunction', new Twig_Function_Method($this, 'someMethod'));

    }

}