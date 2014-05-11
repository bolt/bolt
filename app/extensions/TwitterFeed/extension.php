<?php
// TwitterFeed extension for Bolt
// Minimum version: 1.4

namespace TwitterFeed;

class Extension extends \Bolt\BaseExtension
{

    function info() {

        $data = array(
            'name' =>"Twitter Feed",
            'description' => "An extension to add a Twitter feed to your site when using <code>{{ twitterfeed() }}</code> in your templates.",
            'author' => "Gawain Lynch",
            'link' => "http://bolt.cm",
            'version' => "1.0",
            'required_bolt_version' => "1.4",
            'highest_bolt_version' => "2.0",
            'type' => "Twig function",
            'first_releasedate' => "2014-02-26",
            'latest_releasedate' => "2014-02-26",
        );

        return $data;
    }

    function initialize() {
        $this->addTwigFunction('twitterfeed', 'twigTwitterFeed');
    }

    function twigTwitterFeed() {
        if ( $this->config['twitter_handle'] == '' || $this->config['data_widget_id'] == '' ) {
            return;
        }

        $twitter_url = "https://twitter.com/" . str_replace( '@', '', $this->config['twitter_handle'] );

        $html = '<a class="twitter-timeline" href="' . $twitter_url . '"
                    data-widget-id="' . $this->config['data_widget_id'] . '"
                    data-chrome="' . $this->config['data_chrome'] . '">' . $this->config['link_text'] . '</a>';
        $html .= <<< EOM
                <script>
                    !function(d, s, id) {
                        var js, fjs = d.getElementsByTagName(s)[0], p = /^http:/.test(d.location) ? 'http' : 'https';
                        if (!d.getElementById(id)) {
                            js = d.createElement(s);
                            js.id = id;
                            js.src = p + '://platform.twitter.com/widgets.js';
                            fjs.parentNode.insertBefore(js, fjs);
                        }
                    }(document, 'script', 'twitter-wjs');
                </script>
EOM;

        return new \Twig_Markup($html, 'UTF-8');
    }
}
