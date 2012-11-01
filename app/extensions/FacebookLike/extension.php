<?php
// Facebook Like Extension for Bolt

namespace FacebookLike;

function info()
{

    $data = array(
        'name' =>"Facebook Like Button",
        'description' => "A small extension to add a 'Facebook Like'-button to your site, when using <code>{{ facebooklike() }}</code> in your templates.",
        'author' => "Bob den Otter",
        'link' => "http://bolt.cm",
        'version' => "1.0",
        'required_bolt_version' => "0.8",
        'highest_bolt_version' => "0.8",
        'type' => "Twig function",
        'first_releasedate' => "2012-10-10",
        'latest_releasedate' => "2012-10-19",
    );

    return $data;

}

function init($app)
{

    // Make sure the script is inserted as well..
    $app['extensions']->insertSnippet('endofbody', 'FacebookLike\facebookScript');

    $app['twig']->addFunction('facebooklike', new \Twig_Function_Function('FacebookLike\facebookLike'));

}


function facebookScript()
{

    $html = <<< EOM
    <div id="fb-root"></div>
    <script>(function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
      fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));</script>
EOM;

    return $html;

}



function facebookLike()
{
    global $app;
    
    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    if (empty($config['style'])) { $config['style'] = "standard"; }
    if (empty($config['width'])) { $config['width'] = "350px"; }
    if (empty($config['verb'])) { $config['verb'] = "like"; }
    if (empty($config['scheme'])) { $config['scheme'] = "light"; }
    if (empty($config['url'])) { 
        $config['url'] = $app['paths']['canonicalurl'];
    }

    // code from http://developers.facebook.com/docs/reference/plugins/like/

    $html = <<< EOM
    <div class="fb-like" data-href="%url%" data-send="false" data-layout="%style%" data-width="%width%"
    data-show-faces="false" data-action="%verb%" data-colorscheme="%scheme%"></div>
EOM;
    // data-href="http://example.org"

    $html = str_replace("%url%", $config['url'], $html);
    $html = str_replace("%style%", $config['style'], $html);
    $html = str_replace("%width%", $config['width'], $html);
    $html = str_replace("%verb%", $config['verb'], $html);
    $html = str_replace("%scheme%", $config['scheme'], $html);

    return $html;

}





