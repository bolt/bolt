<?php
// Disqus comment thread Extension for Bolt

namespace Disqus;

function info()
{

    $data = array(
        'name' =>"Disqus",
        'description' => "An extension to place Disqus comment threads on your site, when using <code>{{ disqus() }}</code> in your templates.",
        'author' => "Bob den Otter",
        'link' => "http://bolt.cm",
        'version' => "0.9",
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

    $app['twig']->addFunction('disqus', new \Twig_Function_Function('Disqus\disqus'));
    $app['twig']->addFunction('disquslink', new \Twig_Function_Function('Disqus\disquslink'));

}




function disqus($title="")
{
    global $app;

    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    if (empty($config['disqus_name'])) { $config['disqus_name'] = "No name set"; }

    $html = <<< EOM
        <div id="disqus_thread"></div>
        <script type="text/javascript">
            var disqus_shortname = '%shortname%';
            %title%var disqus_url = '%url%';

            (function() {
                var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
                dsq.src = 'http://' + disqus_shortname + '.disqus.com/embed.js';
                (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
            })();
        </script>
        <noscript>Please enable JavaScript to view the <a href="http://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>
        <a href="http://disqus.com" class="dsq-brlink">comments powered by <span class="logo-disqus">Disqus</span></a>

EOM;

    if ($title!="") {
        $title = "var disqus_title = '" . htmlspecialchars($title, ENT_QUOTES, "UTF-8") . "';\n";
    } else {
        $title = "";
    }

    // echo "<pre>\n" . \util::var_dump($app['paths'], true) . "</pre>\n";

    $html = str_replace("%shortname%", $config['disqus_name'], $html);
    $html = str_replace("%url%", $app['paths']['canonicalurl'], $html);
    $html = str_replace("%title%", $title, $html);


    return new \Twig_Markup($html, 'UTF-8');

}



function disquslink($link)
{
    global $app;

    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__.'/config.yml'));

    if (empty($config['disqus_name'])) { $config['disqus_name'] = "No name set"; }

    $script = <<< EOM
<script type="text/javascript">
var disqus_shortname = '%shortname%'; 
(function () {
var s = document.createElement('script'); s.async = true;
s.type = 'text/javascript';
s.src = 'http://' + disqus_shortname + '.disqus.com/count.js';
(document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(s);
}());
</script>

EOM;

    $script = str_replace("%shortname%", $config['disqus_name'], $script);

    $app['extensions']->insertSnippet('endofbody', $script);


    // echo "<pre>\n" . \util::var_dump($app['paths'], true) . "</pre>\n";

    $html = '%hosturl%%link%#disqus_thread';

    $html = str_replace("%hosturl%", $app['paths']['hosturl'], $html);
    $html = str_replace("%link%", $link, $html);

    return new \Twig_Markup($html, 'UTF-8');

}



