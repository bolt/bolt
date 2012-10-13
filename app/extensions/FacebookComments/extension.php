<?php
// Facebook Like Extension for Bolt

namespace FacebookComments;

function info()
{

    $data = array(
        'name' =>"Facebook Comments",
        'description' => "An extension to place Facebook comment threads on your site, when using <code>{{ facebookcomments() }}</code> in your templates.",
        'author' => "Bob den Otter",
        'link' => "http://bolt.cm",
        'version' => 0.9,
        'required_bolt_version' => 0.8,
        'type' => "Twig function",
        'releasedate' => "2012-10-12"
    );

    return $data;

}

function init($app)
{

    // Make sure the script is inserted as well..
    $app['extensions']->insertSnippet('endofbody', 'FacebookLike\facebookScript');

    $app['twig']->addFunction('facebookcomments', new \Twig_Function_Function('FacebookComments\facebookcomments'));

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



function facebookcomments($title="")
{
    global $app;

    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    if (empty($config['width'])) { $config['width'] = "470"; }

    $html = <<< EOM
        <div class="fb-comments" data-href="%url%" data-num-posts="1" data-width="%width%"></div>
EOM;

    if ($title!="") {
        $title = "var disqus_title = '" . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . "';\n";
    } else {
        $title = "";
    }

    $html = str_replace("%width%", $config['width'], $html);
    $html = str_replace("%url%", $app['paths']['canonicalurl'], $html);

    return $html;

}





