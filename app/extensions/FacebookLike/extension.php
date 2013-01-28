<?php
// Facebook Like Extension for Bolt

namespace FacebookLike;

class Extension extends \Bolt\BaseExtension
{

    function info()
    {

        $data = array(
            'name' =>"Facebook Like Button",
            'description' => "A small extension to add a 'Facebook Like'-button to your site, when using <code>{{ facebooklike() }}</code> in your templates.",
            'author' => "Bob den Otter",
            'link' => "http://bolt.cm",
            'version' => "1.0",
            'required_bolt_version' => "1.0",
            'highest_bolt_version' => "1.0",
            'type' => "Twig function",
            'first_releasedate' => "2012-10-10",
            'latest_releasedate' => "2013-01-27",
        );

        return $data;

    }

    function initialize()
    {

        if (empty($this->config['style'])) { $this->config['style'] = "standard"; }
        if (empty($this->config['width'])) { $this->config['width'] = "350px"; }
        if (empty($this->config['verb'])) { $this->config['verb'] = "like"; }
        if (empty($this->config['scheme'])) { $this->config['scheme'] = "light"; }
        if (empty($this->config['url'])) { $this->config['url'] = $this->app['paths']['canonicalurl']; }

        $this->insertSnippet('endofbody', 'facebookScript');
        $this->addTwigFunction('facebooklike', 'facebookLike');

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

        // code from http://developers.facebook.com/docs/reference/plugins/like/

        $html = <<< EOM
    <div class="fb-like" data-href="%url%" data-send="false" data-layout="%style%" data-width="%width%"
    data-show-faces="false" data-action="%verb%" data-colorscheme="%scheme%"></div>
EOM;
        // data-href="http://example.org"

        $html = str_replace("%url%", $this->config['url'], $html);
        $html = str_replace("%style%", $this->config['style'], $html);
        $html = str_replace("%width%", $this->config['width'], $html);
        $html = str_replace("%verb%", $this->config['verb'], $html);
        $html = str_replace("%scheme%", $this->config['scheme'], $html);

        return new \Twig_Markup($html, 'UTF-8');

    }


}





