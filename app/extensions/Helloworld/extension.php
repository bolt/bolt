<?php
// Hello World Extension for Bolt
// Minimum version: 0.7

namespace Helloworld;

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



    $app['twig']->addFunction('helloworld', new \Twig_Function_Function('Helloworld\twigHelloworld'));
}


function twigHelloworld($name="") {

    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    // if $name isn't set, use the one from the config.yml. Unless that's empty too, then use "world".
    if (empty($name)) {
        if (!empty($config['name'])) {
            $name = $config['name'];
        } else {
            $name = "World";
        }
    }

    return "Hello, ". $name ."!";

}



