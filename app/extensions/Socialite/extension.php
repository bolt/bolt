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
            'version' => "1.2",
            'required_bolt_version' => "1.5",
            'highest_bolt_version' => "2.0",
            'type' => "Twig function",
            'first_releasedate' => "2014-03-26",
            'latest_releasedate' => "2014-04-26",
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

        if (empty($this->config['template'])) {
            $this->config['template'] = 'assets/socialite.twig';
        }

        // Insert out JS late so that we are more likely to work with a late
        // jQuery insertion
        $html .= '
            <script type="text/javascript" defer src="' . $this->config['path'] . '/js/bolt.socialite.min.js"></script>
            ';
        $this->insertSnippet(SnippetLocation::END_OF_HTML, $html);

        // Add ourselves to the Twig filesystem path
        $this->app['twig.loader.filesystem']->addPath(__DIR__);

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

    private function BufferAppButton($args = false)
    {
        if (is_array($this->record->values['image'])) {
            $image = $this->app['paths']['rooturl'] . $this->app['paths']['files'] . $this->record->values['image']['file'];
        } else {
            $image = $this->app['paths']['rooturl'] . $this->app['paths']['files'] . $this->record->values['image'];
        }

        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'BufferAppButton',
            'text' => $this->record->values['title'],
            'url' => $this->config['url'],
            'count' => $this->config['bufferapp_count'],
            'via' => $this->config['bufferapp_twitter_user'],
            'picture' => $image
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function FacebookLike()
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'FacebookLike',
            'url' => $this->config['url'],
            'title' => $this->record->values['title'],
            'action' => $this->config['facebook_like_action'],
            'colorscheme' => $this->config['facebook_like_colorscheme'],
            'kid_directed_site' => $this->config['facebook_like_kid_directed_site'],
            'showfaces' => $this->config['facebook_like_show_faces'],
            'layout' => $this->config['facebook_like_layout'],
            'width' => $this->config['facebook_like_width']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function FacebookFollow($args = false)
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'FacebookFollow',
            'url' => $args,
            'action' => $this->config['facebook_follow_action'],
            'colorscheme' => $this->config['facebook_follow_colorscheme'],
            'kid_directed_site' => $this->config['facebook_follow_kid_directed_site'],
            'showfaces' => $this->config['facebook_follow_show_faces'],
            'layout' => $this->config['facebook_follow_layout'],
            'width' => $this->config['facebook_follow_width']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function FacebookFacepile($args = false)
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'FacebookFacepile',
            'url' => $args,
            'maxrows' => $this->config['facebook_facepile_max_rows'],
            'colorscheme' => $this->config['facebook_facepile_colorscheme'],
            'size' => $this->config['facebook_facepile_size'],
            'count' => $this->config['facebook_facepile_count']
        ));

//data-max-rows="2" data-colorscheme="light" data-size="small" data-show-count="true"

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterShare()
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'TwitterShare',
            'title' => $this->record->values['title'],
            'url' => $this->config['url'],
            'align' => $this->config['twitter_share_align'],
            'count' => $this->config['twitter_share_count'],
            'size' => $this->config['twitter_share_size']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterFollow()
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'TwitterFollow',
            'twitter_handle' => $this->record->values['twitter_handle'],
            'title' => $this->record->values['title'],
            'url' => $this->config['url'],
            'align' => $this->config['twitter_follow_align'],
            'count' => $this->config['twitter_follow_count'],
            'size' => $this->config['twitter_follow_size']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterMention ()
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'TwitterFollow',
            'twitter_handle' => $this->record->values['twitter_handle'],
            'title' => $this->record->values['title'],
            'url' => $this->config['url'],
            'align' => $this->config['twitter_mention_align'],
            'size' => $this->config['twitter_mention_size']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterHashtag($args = false)
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'TwitterHashtag',
            'hashtag' => $args,
            'title' => $this->record->values['title'],
            'url' => $this->config['url'],
            'align' => $this->config['twitter_hashtag_align'],
            'size' => $this->config['twitter_hashtag_size']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function TwitterEmbed($args = false)
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'TwitterEmbed',
            'url' => $args
        ));
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

    private function TwitterTimeline()
    {
        if ( $this->config['twitter_handle'] == '' || $this->config['twitter_data_widget_id'] == '' ) {
            return;
        }

        $twitter_handle = str_replace( '@', '', $this->config['twitter_handle'] );

        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'TwitterHashtag',
            'twitter_handle' => $twitter_handle,
            'widget_id' => $this->config['twitter_data_widget_id'],
            'chrome' => $this->config['twitter_data_chrome']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GooglePlusFollow($args = false)
    {
        if ($this->config['google_plus_follow_size'] == 'small') {
            $this->config['google_plus_follow_size'] = 15;
        } elseif ($this->config['google_plus_follow_size'] == 'medium') {
            $this->config['google_plus_follow_size'] = 20;
        } elseif ($this->config['google_plus_follow_size'] == 'large') {
            $this->config['google_plus_follow_size'] = 24;
        }

        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'GooglePlusFollow',
            'url' => $args,
            'annotation' => $this->config['google_plus_follow_annotation'],
            'height' => $this->config['google_plus_follow_size'],
            'rel' => $this->config['google_plus_follow_relationship']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GooglePlusOne()
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'GooglePlusOne',
            'url' => $this->config['url']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GooglePlusShare()
    {
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

        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'GooglePlusShare',
            'url' => $this->config['url'],
            'annotation' => $this->config['google_plus_share_annotation'],
            'height' => $this->config['google_plus_share_size']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GooglePlusBadge($args)
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'GooglePlusBadge',
            'url' => $args,
            'layout' => $this->config['google_plus_badge_layout'],
            'width' => $this->config['google_plus_badge_width'],
            'theme' => $this->config['google_plus_badge_theme'],
            'showcoverphoto' => $this->config['google_plus_badge_photo'],
            'showtagline' => $this->config['google_plus_badge_tagline'],
            'rel' => $this->config['google_plus_badge_relationship'],
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function LinkedinShare()
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'LinkedinShare',
            'url' => $this->config['url'],
            'title' => $this->record->values['title']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function LinkedinRecommend()
    {
        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'LinkedinRecommend',
            'url' => $this->config['url'],
            'title' => $this->record->values['title']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function PinterestPinit()
    {
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

        $html = $this->app['render']->render($this->config['template'], array(
            'socialite' => 'PinterestPinit',
            'lang' => $this->config['pinterest_pinit_language'],
            'color' => $this->config['pinterest_pinit_color'],
            'height' => $this->config['pinterest_pinit_size'],
            'config' => $this->config['pinterest_pinit_config']
        ));

        return new \Twig_Markup($html, 'UTF-8');
    }
/*
    private function SpotifyPlay()
    {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function HackerNewsShare()
    {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GithubWatch()
    {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GithubFork()
    {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function GithubFollow()
    {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }

    private function DzoneSubmit()
    {
        $html = '
            <div class="social-buttons cf">

            </div>';

        return new \Twig_Markup($html, 'UTF-8');
    }
*/
}
