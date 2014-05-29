<?php
// Pinterest Pin it Button Extension for Bolt

namespace PinIt;

use Bolt\Extensions\Snippets\Location as SnippetLocation;

class Extension extends \Bolt\BaseExtension
{

    public function info()
    {

        $data = array(
            'name' =>"Pinterest Pin it Button",
            'description' => "A small extension to add a Pinterest 'Pin it' button to your site, ".
                             "when using <code>{{ pinit() }}</code> in your templates.",
            'author' => "Gawain Lynch",
            'link' => "http://bolt.cm",
            'version' => "1.0",
            'required_bolt_version' => "1.4",
            'highest_bolt_version' => "2.0",
            'type' => "Twig function",
            'first_releasedate' => "2014-02-26",
            'latest_releasedate' => "2014-02-26",
            'allow_in_user_content' => true,
        );

        return $data;

    }

    public function initialize()
    {
        if (empty($this->config['color'])) {
            $this->config['color'] = "red";
        }
        if (empty($this->config['size']) || $this->config['size'] = 'small') {
            $this->config['size'] = "20";
        }
        elseif ($this->config['size'] == 'large') {
            $this->config['size'] = "28";
        }
        if (empty($this->config['language'])) {
            $this->config['language'] = "en";
        }
        if (empty($this->config['hover'])) {
            $this->config['hover'] = "on";
        }

        $this->insertSnippet(SnippetLocation::END_OF_BODY, 'pinitScript');
        $this->addTwigFunction('pinit', 'twigPinIt');
    }

    public function pinitScript()
    {

        if ( $this->config['hover'] == "on" )
            $hover = 'data-pin-hover="true"';

        $html = <<< EOM
            <script type="text/javascript" $hover>
            (function(d){
                var f = d.getElementsByTagName('SCRIPT')[0], p = d.createElement('SCRIPT');
                p.type = 'text/javascript';
                p.async = true;
                p.src = '//assets.pinterest.com/js/pinit.js';
                f.parentNode.insertBefore(p, f);
            }(document));
            </script>
EOM;
        return $html;

    }

    public function twigPinIt()
    {
        // http://business.pinterest.com/widget-builder/#do_pin_it_button
        $html = <<< EOM
            <a href="//www.pinterest.com/pin/create/button/" data-pin-do="buttonBookmark" data-pin-lang="%lang%" data-pin-color="%color%" data-pin-height="%size%" >
                <img src="//assets.pinterest.com/images/pidgets/pinit_fg_%lang%_rect_%color%_%size%.png" />
            </a>
EOM;

        $html = str_replace("%lang%", $this->config['language'], $html);
        $html = str_replace("%color%", $this->config['color'], $html);
        $html = str_replace("%size%", $this->config['size'], $html);

        return new \Twig_Markup($html, 'UTF-8');

    }
}
