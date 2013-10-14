<?php
// PhpQuery for Bolt, by Bob den Otter
// See: https://code.google.com/p/phpquery/

namespace PhpQuery;

class Extension extends \Bolt\BaseExtension
{

    public function info()
    {

        $data = array(
            'name' =>"PhpQuery",
            'description' => "A small extension to add a PhpQuery to your site, ".
                             "when using <code>{{ 'something'|addclass('p:first', 'foo') }}</code> in your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "0.9",
            'type' => "Twig function",
            'first_releasedate' => "2013-04-22",
            'latest_releasedate' => "2013-04-22",
        );

        return $data;

    }

    public function init()
    {

        $this->addTwigFilter('addclass', 'addclass');

    }

    public function addclass($html, $selector, $class)
    {

        require(__DIR__ . '/phpQuery.php');

        $pq = \phpQuery::newDocument($html);
        $pq[$selector]->addClass($class);

        return new \Twig_Markup($pq, 'UTF-8');

    }

}
