<?php
// Twitter Button for Bolt

namespace TwitterButton;

function info()
{

    $data = array(
        'name' =>"Twitter Button",
        'description' => "A small extension to add a 'Twitter button' to your site, when using <code>{{ twitterbutton() }}</code> in your templates.",
        'author' => "Bob den Otter",
        'link' => "http://bolt.cm",
        'version' => 0.9,
        'required_bolt_version' => 0.8,
        'type' => "Twig function",
        'releasedate' => "2012-10-10"
    );

    return $data;

}

function init($app)
{

    $app['twig']->addFunction('twitterbutton', new \Twig_Function_Function('TwitterButton\twitterButton'));

}






function twitterButton(\Silex\Application $app)
{

    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    if (empty($config['via'])) { $config['via'] = ""; }
    if (!empty($config['count']) && $config['count']==false) {
        $config['count'] = 'data-count="none"';
    } else {
        $config['count'] = '';
    }

    $html = <<< EOM
    <a href="https://twitter.com/share" class="twitter-share-button" data-via="%via%" %count%  data-dnt="true">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
EOM;

    $html = str_replace("%via%", $config['via'], $html);
    $html = str_replace("%count%", $config['count'], $html);

    return $html;

}





