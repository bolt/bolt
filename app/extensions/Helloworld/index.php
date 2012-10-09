<?php
// Hello World Extension for Bolt
// Minimum version: 0.7

namespace Helloworld;

class Extension {

    function info() {

        $data = array(
            'author' => "Bob den Otter",
            'version' => 0.1,
            'required_bolt_version' => 0.7,
            'type' => "Twig function",
            'description' => "A small extension to add 'Hello, World!' to your templates, when using {{ helloworld }}."
        );

        return $data;

    }

    function init($app) {
        echo "<p>Tralalla</p>";
        $app['twig']->addFunction('helloworld', new \Twig_Function_Function('Helloworld\Extension::helloworld'));
    }

    function helloworld() {

        return "Hello, this World!";

    }

}

