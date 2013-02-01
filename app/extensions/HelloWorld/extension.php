<?php
// Hello World Extension for Bolt
// Minimum version: 0.7

namespace HelloWorld;

class Extension extends \Bolt\BaseExtension
{

    function info() {

        $data = array(
            'name' =>"Hello, World!",
            'description' => "A small extension to add a 'Hello, World!'-greeting to your site, when using <code>{{ helloworld() }}</code> in your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "1.1",
            'required_bolt_version' => "1.0",
            'highest_bolt_version' => "1.0",
            'type' => "Twig function",
            'first_releasedate' => "2012-10-10",
            'latest_releasedate' => "2013-01-27",
        );

        return $data;

    }

    function initialize() {

        $this->addTwigFunction('helloworld', 'twigHelloworld');

    }

    function twigHelloworld($name="") {

        // if $name isn't set, use the one from the config.yml. Unless that's empty too, then use "world".
        if (empty($name)) {
            if (!empty($this->config['name'])) {
                $name = $this->config['name'];
            } else {
                $name = "World";
            }
        }

        $html = "Hello, ". $name ."!";

        return new \Twig_Markup($html, 'UTF-8');

    }

}






