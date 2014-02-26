<?php
// RSS Aggregator Extension for Bolt, by Sebastian Klier

namespace RSSAggregator;

class Extension extends \Bolt\BaseExtension
{

    /**
     * Info block for RSS Aggregator Extension.
     */
    function info()
    {

        $data = array(
            'name' => "RSS Aggregator",
            'description' => "Shows feed items of external RSS feeds anywhere on your site.",
            'keywords' => "bolt, rss, feed, aggregator",
            'author' => "Sebastian Klier",
            'link' => "http://github.com/sekl/bolt-rssaggregator",
            'version' => "0.1",
            'required_bolt_version' => "1.0.2",
            'highest_bolt_version' => "1.1.4",
            'type' => "General",
            'first_releasedate' => "2013-08-29",
            'latest_releasedate' => "2013-08-29",
            'dependencies' => "",
            'priority' => 10
        );

        return $data;

    }

    /**
     * Initialize RSS Aggregator. Called during bootstrap phase.
     */
    function init()
    {

        // If yourextension has a 'config.yml', it is automatically loaded.
        // $foo = $this->config['bar'];

        // Add CSS file
        $this->addCSS("assets/rssaggregator.css");

        // Initialize the Twig function
        $this->addTwigFunction('rss_aggregator', 'twigRss_aggregator');

    }

    /**
     * Twig function {{ rss_aggregator() }} in RSS Aggregator extension.
     */
    function twigRss_aggregator($url = false, $options = array())
    {

        if(!$url) {
            return new \Twig_Markup('External feed could not be loaded! No URL specified.', 'UTF-8'); 
        }

        // Construct a cache handle from the URL
        $handle = preg_replace('/[^A-Za-z0-9_-]+/', '', $url);
        $handle = str_replace('httpwww', '', $handle);
        $cachedir = BOLT_CACHE_DIR . '/rssaggregator/';
        $cachefile = $cachedir.'/'.$handle.'.cache';

        // default options
        $defaultLimit = 5;
        $defaultShowDesc = false;
        $defaultShowDate = false;
        $defaultDescCutoff = 100;
        $defaultCacheMaxAge = 15;

        // Handle options parameter

        if(!array_key_exists('limit', $options)) {
            $options['limit'] = $defaultLimit;
        }
        if(!array_key_exists('showDesc', $options)) {
            $options['showDesc'] = $defaultShowDesc;
        }
        if(!array_key_exists('showDate', $options)) {
            $options['showDate'] = $defaultShowDate;
        }
        if(!array_key_exists('descCutoff', $options)) {
            $options['descCutoff'] = $defaultDescCutoff;
        }
        if(!array_key_exists('cacheMaxAge', $options)) {
            $options['cacheMaxAge'] = $defaultCacheMaxAge;
        }

        // Create cache directory if it does not exist
        if (!file_exists($cachedir)) {
            mkdir($cachedir, 0777, true);
        }
        

        // Use cache file if possible
        if (file_exists($cachefile)) {
            $now = time();
            $cachetime = filemtime($cachefile);
            if ($now - $cachetime < $options['cacheMaxAge'] * 60) {
                return new \Twig_Markup(file_get_contents($cachefile), 'UTF-8');
            }
        }

        // Make sure we are sending a user agent header with the request
        $streamOpts = array(
            'http' => array(
                'user_agent' => 'libxml',
            )
        );

        libxml_set_streams_context(stream_context_create($streamOpts));

        $doc = new \DOMDocument();

        // Load feed and suppress errors to avoid a failing external URL taking down our whole site
        if (!@$doc->load($url)) {
            return new \Twig_Markup('External feed could not be loaded!', 'UTF-8');
        }

        // Parse document
        $feed = array();

        foreach($doc->getElementsByTagName('item') as $node) {
            $item = array(
                'title' => $node->getElementsByTagName('title')->item(0)->nodeValue,
                'desc' => $node->getElementsByTagName('description')->item(0)->nodeValue,
                'link' => $node->getElementsByTagName('link')->item(0)->nodeValue,
                'date' => $node->getElementsByTagName('pubDate')->item(0)->nodeValue,
            );
            array_push($feed, $item);
        }

        $items = array();

        // if limit is set higher than the actual amount of items in the feed, adjust limit
        $limit = $options['limit'] > count($feed) ? count($feed) : $options['limit'];

        for($i = 0; $i < $limit; $i++) {
                $title = htmlentities(strip_tags($feed[$i]['title']), ENT_QUOTES, "UTF-8");
                $link = htmlentities(strip_tags($feed[$i]['link']), ENT_QUOTES, "UTF-8");
                $desc = htmlentities(strip_tags($feed[$i]['desc']), ENT_QUOTES, "UTF-8");
                // if cutOff is set higher than the actual length of the description, adjust it
                $cutOff = $options['descCutoff'] > strlen($desc) ? strlen($desc) : $options['descCutoff'];
                $desc = substr($desc, 0, strpos($desc, ' ', $cutOff));
                $desc = str_replace('&amp;nbsp;', '', $desc);
                $desc .= '...';
                $date = date('l F d, Y', strtotime($feed[$i]['date']));
                array_push($items, array(
                    'title' => $title,
                    'link'  => $link,
                    'desc'  => $desc,
                    'date'  => $date,
                ));
        }

        $html = '<div class="rss-aggregator"><ul>';

        foreach ($items as $item) {
            $html .= '<li>';
            $html .= '<a href="' . $item['link'] . '" class="rss-aggregator-title" rel="nofollow">' . $item['title'] . '</a><br />';
            if ($options['showDesc']) {
                $html .= '<span class="rss-aggregator-desc">' . $item['desc'] . '</span>';
            }
            if ($options['showDate']) {
                $html .= '<span class="rss-aggregator-date">' . $item['date'] . '</span>';
            }
            $html .= '</li>';
        }

        $html .= '</ul></div>';

        // create or refresh cache file
        file_put_contents($cachefile, $html);

        return new \Twig_Markup($html, 'UTF-8');
    }
}