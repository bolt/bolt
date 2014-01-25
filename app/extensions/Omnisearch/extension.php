<?php
namespace Omnisearch;

class Extension extends \Bolt\BaseExtension
{

    function info() {

        $data = array(
            'name' => "Omnisearch",
            'description' => "Omnisearch test thing",
            'author' => "Xiao-Hu Tai",
            'link' => "http://bolt.cm",
            'version' => "0.1",
            'required_bolt_version' => "1.3",
            'highest_bolt_version' => "1.3",
            'type' => "UX",
            'first_releasedate' => "2014-01-25",
            'latest_releasedate' => "2014-01-25",
            'dependencies' => "",
            'priority' => 10
        );

        return $data;

    }

    function initialize() {

        $root = $this->app['config']->get('general/branding/path');

        $bolt = $this->app['paths']['bolt'];
        $omnisearch = 'omnisearch';

        $this->app->match($root.'/'.$omnisearch, array($this, 'omnisearch'));
        $this->addMenuOption("Omnisearch", $bolt.$omnisearch, "icon-exchange"); //, \Bolt\Users::DEVELOPER); // throws exception

    }

    function omnisearch() {

        if ('backend' != $this->app['end']) {
            return;
        }

        // $this->requireUserLevel(\Bolt\Users::DEVELOPER);
        $title = "Omnisearch";
        $content = "<p>It works!</p>";
        $this->app['twig.loader.filesystem']->addPath(__DIR__.'/assets/');
        $html = $this->app['twig']->render('omnisearch.twig', array(
            'title' => $title,
            'content' => $content
        ));
 
        return $html;
    }

}
