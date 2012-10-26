<?php
// Testing Snippets extension for Bolt

namespace TestSnippets;


function info() {

    $data = array(
        'name' =>"Snippets Tester",
        'description' => "A developer extension to add snippets to all available locations in a sensible HTML document.",
        'author' => "Bob den Otter",
        'link' => "http://bolt.cm",
        'version' => "1.0",
        'required_bolt_version' => "0.7.8",
        'highest_bolt_version' => "0.8",
        'type' => "Snippet",
        'first_releasedate' => "2012-10-10",
        'latest_releasedate' => "2012-10-19",
    );

    return $data;

}

function init($app) {

    $app['extensions']->insertSnippet('endofhead', 'TestSnippets\callback', "endofhead");
    $app['extensions']->insertSnippet('endofhead', "<!-- inserted string snippet endofhead -->");

    $app['extensions']->insertSnippet('aftermeta', 'TestSnippets\callback', "aftermeta");
    $app['extensions']->insertSnippet('aftermeta', "<!-- inserted string snippet aftermeta -->");

    $app['extensions']->insertSnippet('aftercss', 'TestSnippets\callback', "aftercss");
    $app['extensions']->insertSnippet('aftercss', "<!-- inserted string snippet aftercss -->");

    $app['extensions']->insertSnippet('startofhead', 'TestSnippets\callback', "startofhead");
    $app['extensions']->insertSnippet('startofhead', "<!-- inserted string snippet startofhead -->");

    $app['extensions']->insertSnippet('startofbody', 'TestSnippets\callback', "startofbody");
    $app['extensions']->insertSnippet('startofbody', "<!-- inserted string snippet startofbody -->");

    $app['extensions']->insertSnippet('endofbody', 'TestSnippets\callback', "endofbody");
    $app['extensions']->insertSnippet('endofbody', "<!-- inserted string snippet endofbody -->");

    $app['extensions']->insertSnippet('endofhtml', 'TestSnippets\callback', "endofhtml");
    $app['extensions']->insertSnippet('endofhtml', "<!-- inserted string snippet endofhtml -->");

    $app['extensions']->insertSnippet('afterhtml', 'TestSnippets\callback', "afterhtml");
    $app['extensions']->insertSnippet('afterhtml', "<!-- inserted string snippet afterhtml -->");

}


function callback(\Silex\Application $app, $var) {

    return "<!-- snippet inserted via callback with parameter '$var'.. -->";

}

