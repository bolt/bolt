<?php
// Disqus comment thread Extension for Bolt

namespace Disqus;

use Bolt\Extensions\Snippets\Location as SnippetLocation;

class Extension extends \Bolt\BaseExtension
{
    function info()
    {

        $data = array(
            'name' =>"Disqus",
            'description' => "An extension to place Disqus comment threads on your site, when using <code>{{ disqus() }}</code> in your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "1.2",
            'required_bolt_version' => "1.0",
            'highest_bolt_version' => "1.0",
            'type' => "Twig function",
            'first_releasedate' => "2012-10-10",
            'latest_releasedate' => "2013-01-28",
        );

        return $data;

    }

    function initialize()
    {

        $this->addTwigFunction('disqus', 'disqus');
        $this->addTwigFunction('disquslink', 'disquslink');

        if (empty($this->config['disqus_name'])) { $this->config['disqus_name'] = "No name set"; }

    }


function disqus($title="")
    {

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

        $html = str_replace("%shortname%", $this->config['disqus_name'], $html);
        $html = str_replace("%url%", $this->app['paths']['canonicalurl'], $html);
        $html = str_replace("%title%", $title, $html);

        return new \Twig_Markup($html, 'UTF-8');

    }



    function disquslink($link)
    {

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

        $script = str_replace("%shortname%", $this->config['disqus_name'], $script);

        $this->addSnippet(SnippetLocation::END_OF_BODY, $script);

        $html = '%hosturl%%link%#disqus_thread';

        $html = str_replace("%hosturl%", $this->app['paths']['hosturl'], $html);
        $html = str_replace("%link%", $link, $html);

        return new \Twig_Markup($html, 'UTF-8');

    }


}






