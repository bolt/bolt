<?php
// Twitter Button for Bolt, by Bob den Otter

namespace TwitterButton;

class Extension extends \Bolt\BaseExtension
{

    function info()
    {

        $data = array(
            'name' =>"Twitter Button",
            'description' => "A small extension to add a 'Twitter button' to your site, when using <code>{{ twitterbutton() }}</code> in your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "1.1",
            'type' => "Twig function",
            'first_releasedate' => "2012-10-10",
            'latest_releasedate' => "2013-01-27",
        );

        return $data;

    }

    function initialize()
    {

        $this->addTwigFunction('twitterbutton', 'TwitterButton\twitterButton');

        /*
        $function = new \Twig_SimpleFunction('twitterbutton', function() {
            // No $this in scope. :-(
        });
        $this->app['twig']->addFunction($function);

        call_user_func(array($this, 'twitterButton'));
        */


    }

}


function twitterButton()
{

    global $app;

    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    if (empty($config['via'])) { $config['via'] = ""; }
    if (!empty($config['count']) && $config['count']==false) {
        $config['count'] = 'data-count="none"';
    } else {
        $config['count'] = '';
    }
    if (empty($config['url'])) { 
        $config['url'] = $app['paths']['canonicalurl'];
    }

    // code from: https://twitter.com/about/resources/buttons#tweet

    $html = <<< EOM
    <a href="https://twitter.com/share" class="twitter-share-button" data-via="%via%" %count% data-url="%url%" data-dnt="true">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
EOM;


    $html = str_replace("%url%", $config['url'], $html);
    $html = str_replace("%via%", $config['via'], $html);
    $html = str_replace("%count%", $config['count'], $html);

    return new \Twig_Markup($html, 'UTF-8');

}





