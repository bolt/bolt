<?php
// Socialite.js extension for Bolt

namespace Socialite;

use Bolt\Extensions\Snippets\Location as SnippetLocation;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Extension extends \Bolt\BaseExtension
{

    public function info() {

        $data = array(
            'name' =>"Socialite",
            'description' => "",
            'author' => "Gawain Lynch",
            'link' => "http://bolt.cm",
            'version' => "1.0",
            'required_bolt_version' => "1.5",
            'highest_bolt_version' => "2.0",
            'type' => "Twig function",
            'first_releasedate' => "2014-03-26",
            'latest_releasedate' => "2014-03-26",
        );

        return $data;
    }

    public function initialize() {

        // Define the path to us
        $this->config['path'] = $this->app['paths']['extensions'] . $this->namespace;
        $this->config['url'] = $this->app['paths']['canonicalurl'];

        // If we're set to actviate by scroll, add a class to <body> that gets
        // caught in socialite.load.js
        if (empty($this->config['activation']) || $this->config['activation'] = 'scroll') {
            $html = '<script type="text/javascript">document.body.className += "socialite-scroll";</script>';
        }

        // Insert out JS late so that we are more likely to work with a late
        // jQuery insertion
        $html .= '
            <script type="text/javascript" defer src="' . $this->config['path'] . '/js/bolt.socialite.min.js"></script>
            ';
        $this->insertSnippet(SnippetLocation::END_OF_HTML, $html);

        // Catch the TWIG function
        $this->addTwigFunction('socialite', 'twigSocialite');
    }


    public function twigSocialite($buttons, $sep = '')
    {
        // Store the record in config
        $this->getRecord();

        // We allow either a ('string') or (['an', 'array']) of parameters, so
        // for simplicity just make everything an array
        if (!is_array($buttons)) {
            $buttons = array($buttons);
        }

        // Insert a <div><a> for each module called this time
        foreach ($buttons as $key => $value) {

            if (is_numeric($key) && method_exists($this, $value)) {
                return call_user_method($value, $this);

            } elseif (method_exists($this, $key)) {
                return call_user_method($key, $this, $value);
            }

        }
    }

    private function getRecord()
    {
        if (isset($this->record)) {
            return $this->record;
        }

        $globalTwigVars = $this->app['twig']->getGlobals('record');

        if (isset($globalTwigVars['record'])) {
            $this->record = $globalTwigVars['record'];
        } else {
            $this->record = false;
        }
    }

    private function BufferAppButton($args = false) {
        if (is_array($this->record->values['image'])) {
            $image = $this->app['paths']['rooturl'] . $this->app['paths']['files'] . $this->record->values['image']['file'];
        } else {
            $image = $this->app['paths']['rooturl'] . $this->app['paths']['files'] . $this->record->values['image'];
        }

        $html = '
            <div class="social-buttons cf">
                <a
                    href="http://bufferapp.com/add"
                    class="socialite bufferapp-button"
                    data-text="' . $this->record->values['title'] . '"
                    data-url="' . $this->config['url'] . '"
                    data-count="' . $this->config['bufferapp_count'] . '"
                    data-via="' . $this->config['bufferapp_twitter_user'] . '"
                    data-picture="' . $image . '"
                    rel="nofollow" target="_blank">
                        <span class="vhidden">Buffer it</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function FacebookLike() {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="http://www.facebook.com/sharer.php?u=' . $this->config['url'] . '&t=' . $this->record->values['title'] . '"
                	class="socialite facebook-like"
                    data-href="' . $this->config['url'] . '"
                	data-send="false"
                	data-action="' . $this->config['facebook_like_action'] . '"
                	data-colorscheme="' . $this->config['facebook_like_colorscheme'] . '"
            	    data-kid_directed_site="' . $this->config['facebook_like_kid_directed_site'] . '"
                	data-show-faces="' . $this->config['facebook_like_show_faces'] . '"
        	        data-layout="' . $this->config['facebook_like_layout'] . '"
                	data-width="' . $this->config['facebook_like_width'] . '"
                	rel="nofollow" target="_blank">
                	   <span class="vhidden">Share on Facebook</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function FacebookFollow($args = false) {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="' . $args . '"
                	class="socialite facebook-like"
                    data-href="' . $args . '"
                	data-send="false"
                	data-action="' . $this->config['facebook_follow_action'] . '"
                	data-colorscheme="' . $this->config['facebook_follow_colorscheme'] . '"
            	    data-kid_directed_site="' . $this->config['facebook_follow_kid_directed_site'] . '"
                	data-show-faces="' . $this->config['facebook_follow_show_faces'] . '"
        	        data-layout="' . $this->config['facebook_follow_layout'] . '"
                	data-width="' . $this->config['facebook_follow_width'] . '"
                	rel="nofollow" target="_blank">
                	   <span class="vhidden">Share on Facebook</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function FacebookFacepile($args = false) {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="' . $args . '"
                	class="socialite facebook-facepile"
                    data-href="' . $args . '"
                	data-max-rows="' . $this->config['facebook_facepile_max_rows'] . '"
                    data-colorscheme="' . $this->config['facebook_facepile_colorscheme'] . '"
                	data-size="' . $this->config['facebook_facepile_size'] . '"
                    data-show-count="' . $this->config['facebook_facepile_count'] . '"
                	rel="nofollow" target="_blank">
                	   <span class="vhidden">Facebook Facepile</span>
                </a>
            </div>';
//data-max-rows="2" data-colorscheme="light" data-size="small" data-show-count="true"
        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterShare() {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="http://twitter.com/share"
                    class="socialite twitter-share"
                    data-text="' . $this->record->values['title'] . '"
                    data-url="' . $this->config['url'] . '"
                    data-align="' . $this->config['twitter_share_align'] . '"
                    data-count="' . $this->config['twitter_share_count'] . '"
                    data-size="' . $this->config['twitter_share_size'] . '"
                    rel="nofollow" target="_blank">
                        <span class="vhidden">Share on Twitter</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterFollow() {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="http://twitter.com/PopAnth"
                    class="socialite twitter-follow"
                    data-text="' . $this->record->values['title'] . '"
                    data-url="' . $this->config['url'] . '"
                    data-align="' . $this->config['twitter_follow_align'] . '"
                    data-count="' . $this->config['twitter_follow_count'] . '"
                    data-size="' . $this->config['twitter_follow_size'] . '"
                    rel="nofollow" target="_blank">
                        <span class="vhidden">Follow on Twitter</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterMention () {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="https://twitter.com/intent/tweet?screen_name=PopAnth"
                    class="socialite twitter-mention"
                    data-text="' . $this->record->values['title'] . '"
                    data-url="' . $this->config['url'] . '"
                    data-align="' . $this->config['twitter_mention_align'] . '"
                    data-size="' . $this->config['twitter_mention_size'] . '"
                    rel="nofollow" target="_blank">
                        <span class="vhidden">Mention on Twitter</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterHashtag($args = false) {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="https://twitter.com/intent/tweet?button_hashtag=' . $args . '"
                    class="socialite twitter-hashtag"
                    data-text="' . $this->record->values['title'] . '"
                    data-url="' . $this->config['url'] . '"
                    data-align="' . $this->config['twitter_hashtag_align'] . '"
                    data-size="' . $this->config['twitter_hashtag_size'] . '"
                    rel="nofollow" target="_blank">
                        <span class="vhidden">Hashtag on Twitter</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterEmbed($args = false) {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="' . $args . '"
                    class="socialite twitter-embed"
                    rel="nofollow" target="_blank">
                        <span class="vhidden">Embed from Twitter</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterTimeline() {
        if ( $this->config['twitter_handle'] == '' || $this->config['twitter_data_widget_id'] == '' ) {
            return;
        }

        $twitter_url = "https://twitter.com/" . str_replace( '@', '', $this->config['twitter_handle'] );

        $html = '
            <div class="social-buttons cf">
                <a
                    href="' . $twitter_url . '"
                    class="socialite twitter-timeline"
                    data-widget-id="' . $this->config['twitter_data_widget_id'] . '"
                    data-chrome="' . $this->config['twitter_data_chrome'] . '"
                    rel="nofollow" target="_blank">
                        <span class="vhidden">PopAnth on Twitter</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GooglePlusFollow($args = false) {
        if ($this->config['google_plus_follow_size'] == 'small') {
            $this->config['google_plus_follow_size'] = 15;
        } elseif ($this->config['google_plus_follow_size'] == 'medium') {
            $this->config['google_plus_follow_size'] = 20;
        } elseif ($this->config['google_plus_follow_size'] == 'large') {
            $this->config['google_plus_follow_size'] = 24;
        }

        $html = '
            <div class="social-buttons cf">
                <a
                    href="' . $args . '"
                	class="socialite googleplus-follow"
                    data-annotation="' . $this->config['google_plus_follow_annotation'] . '"
                	data-height="' . $this->config['google_plus_follow_size'] . '"
                	data-href="' . $args . '"
            	    data-rel="' . $this->config['google_plus_follow_relationship'] . '"
                	rel="nofollow" target="_blank">
                        <span class="vhidden">Follow on Google+</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GooglePlusOne() {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="https://plus.google.com/share?url=' . $this->config['url'] . '"
                	class="socialite googleplus-one"
                	data-size="tall"
                	data-href="' . $this->config['url'] . '" rel="nofollow" target="_blank">
                        <span class="vhidden">+1 on Google+</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GooglePlusShare() {
        if ($this->config['google_plus_share_annotation'] == 'bubble' ||
            $this->config['google_plus_share_annotation'] == 'vertical-bubble') {
            $this->config['google_plus_share_size'] = '';
        } else {
            if ($this->config['google_plus_share_size'] == 'small') {
                $this->config['google_plus_share_size'] = 15;
            } elseif ($this->config['google_plus_share_size'] == 'medium') {
                $this->config['google_plus_share_size'] = 20;
            } elseif ($this->config['google_plus_share_size'] == 'large') {
                $this->config['google_plus_share_size'] = 24;
            }
        }
        $html = '
            <div class="social-buttons cf">
                <a
                    href="https://plus.google.com/share?url=' . $this->config['url'] . '"
                	class="socialite googleplus-share"
                    data-action="share"
                    data-annotation="' . $this->config['google_plus_share_annotation'] . '"
                    data-height="' . $this->config['google_plus_share_size'] . '"
                	data-href="' . $this->config['url'] . '"
                	rel="nofollow" target="_blank">
                	   <span class="vhidden">Share on Google+</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GooglePlusBadge($args) {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="' . $args . '"
                	class="socialite googleplus-badge"
                    data-href="' . $args . '"
                    data-layout="' . $this->config['google_plus_badge_layout'] . '"
                    data-width="' . $this->config['google_plus_badge_width'] . '"
                    data-theme="' . $this->config['google_plus_badge_theme'] . '"
                    data-showcoverphoto="' . $this->config['google_plus_badge_photo'] . '"
                    data-showtagline="' . $this->config['google_plus_badge_tagline'] . '"
                    data-rel="' . $this->config['google_plus_badge_relationship'] . '"
                	rel="nofollow" target="_blank">
                        <span class="vhidden">Follow on Google+</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function LinkedinShare() {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="http://www.linkedin.com/shareArticle?mini=true&url=' . $this->config['url'] . '&title=' . $this->record->values['title'] . '"
                	class="socialite linkedin-share"
                	data-url="' . $this->config['url'] . '"
                	data-counter="top" rel="nofollow" target="_blank">
                	   <span class="vhidden">Share on LinkedIn</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function LinkedinRecommend() {
        $html = '
            <div class="social-buttons cf">
                <a
                    href="http://www.linkedin.com/shareArticle?mini=true&url=' . $this->config['url'] . '&title=' . $this->record->values['title'] . '"
                	class="socialite linkedin-recommend"
                    data-url="' . $this->config['url'] . '"
                	data-counter="top"
                	rel="nofollow" target="_blank">
                	   <span class="vhidden">Share on LinkedIn</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function PinterestPinit() {
        if (empty($this->config['pinterest_pinit_color'])) {
            $this->config['pinterest_pinit_color'] = "red";
        }
        if (empty($this->config['pinterest_pinit_size']) || $this->config['pinterest_pinit_size'] = 'small') {
            $this->config['pinterest_pinit_size'] = "20";
        }
        elseif ($this->config['pinterest_pinit_size'] == 'large') {
            $this->config['pinterest_pinit_size'] = "28";
        }
        if (empty($this->config['pinterest_pinit_language'])) {
            $this->config['pinterest_pinit_language'] = "en";
        }
        if (empty($this->config['pinterest_pinit_hover'])) {
            $this->config['pinterest_pinit_hover'] = "on";
        }

        $html = '
            <div class="social-buttons cf" style="margin-top: 41px;">
                <a
                    href="//www.pinterest.com/pin/create/button/"
                    class="socialite pinterest-pinit"
                    data-pin-do="buttonBookmark"
                    data-pin-lang="' . $this->config['pinterest_pinit_language'] . '"
                    data-pin-color="' . $this->config['pinterest_pinit_color'] . '"
                    data-pin-height="' . $this->config['pinterest_pinit_size'] . '"
                    data-pin-config="' . $this->config['pinterest_pinit_config'] . '"
                    rel="nofollow" target="_blank">
                        <span class="vhidden">PinIt on Pinterest</span>
                </a>
            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function SpotifyPlay() {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function HackerNewsShare() {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function HithubWatch() {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GithubFork() {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GithubFollow() {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function DzoneSubmit() {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }
}
