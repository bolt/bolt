<?php
// Testing Snippets extension for Bolt

namespace TestSnippets;

class Extension extends \Bolt\BaseExtension
{

    function info() {

        $data = array(
            'name' =>"Snippets Tester",
            'description' => "A developer extension to add snippets to all available locations in a sensible HTML document.",
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

        $this->insertSnippet('endofhead', 'TestSnippets\callback', "endofhead");
        $this->insertSnippet('endofhead', "<!-- inserted string snippet endofhead -->");

        $this->insertSnippet('aftermeta', 'TestSnippets\callback', "aftermeta");
        $this->insertSnippet('aftermeta', "<!-- inserted string snippet aftermeta -->");

        $this->insertSnippet('aftercss', 'TestSnippets\callback', "aftercss");
        $this->insertSnippet('aftercss', "<!-- inserted string snippet aftercss -->");

        $this->insertSnippet('startofhead', 'TestSnippets\callback', "startofhead");
        $this->insertSnippet('startofhead', "<!-- inserted string snippet startofhead -->");

        $this->insertSnippet('startofbody', 'TestSnippets\callback', "startofbody");
        $this->insertSnippet('startofbody', "<!-- inserted string snippet startofbody -->");

        $this->insertSnippet('endofbody', 'TestSnippets\callback', "endofbody");
        $this->insertSnippet('endofbody', "<!-- inserted string snippet endofbody -->");

        $this->insertSnippet('endofhtml', 'TestSnippets\callback', "endofhtml");
        $this->insertSnippet('endofhtml', "<!-- inserted string snippet endofhtml -->");

        $this->insertSnippet('afterhtml', 'TestSnippets\callback', "afterhtml");
        $this->insertSnippet('afterhtml', "<!-- inserted string snippet afterhtml -->");

    }


    function callback($var) {

        $html = "<!-- snippet inserted via callback with parameter '$var'.. -->";

        return new \Twig_Markup($html, 'UTF-8');

    }

}
