<?php
// Facebook Like Extension for Bolt

namespace Disqus;

function info()
{

    $data = array(
        'name' =>"Disqus",
        'description' => "An extension to place Disqus comment threads on your site, when using <code>{{ disqus() }}</code> in your templates.",
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

    $app['twig']->addFunction('disqus', new \Twig_Function_Function('Disqus\disqus'));

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
    $html = str_replace("%url%", $app['paths']['url'], $html);
    $html = str_replace("%title%", $title, $html);


    return $html;

}





