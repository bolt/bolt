<?php
// RSS feeds extension for Bolt, by WeDesignIt, Patrick van Kouteren

namespace RSSFeed;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Extension extends \Bolt\BaseExtension
{


    /**
     * Info block for RSSFeed Extension.
     */
    function info()
    {

        $data = array(
            'name' => "RSSFeed",
            'description' => "Adds capability of providing RSS feeds of your content.",
            'author' => "WeDesignIt, Patrick van Kouteren, Gawain Lynch",
            'link' => "http://www.wedesignit.nl",
            'version' => "0.3",
            'required_bolt_version' => "1.0",
            'highest_bolt_version' => "1.5.5",
            'type' => "General",
            'first_releasedate' => "2012-11-06",
            'latest_releasedate' => "2014-04-08"
        );

        return $data;

    }

    function initialize()
    {
        // Sitewide feed
        $this->app->match('/rss/feed.{extension}', array($this, 'feed'))
        ->assert('extension', '(xml|rss)')
        ;

        // Contenttype specific feed(s)
        $this->app->match('/{contenttypeslug}/rss/feed.{extension}', array($this, 'feed'))
            ->assert('extension', '(xml|rss)')
            ->assert('contenttypeslug', $this->app['storage']->getContentTypeAssert())
        ;
    }

    public function feed($contenttypeslug = '')
    {
        $this->disableJquery();
        // Clear snippet list. There's no clear any more, so just set to null
        $this->app['extensions']->clearSnippetQueue();
        $this->app['debugbar'] = false;
        // Defaults for later
        $defaultFeedRecords = 5;
        $defaultContentLength = 100;
        $defaultTemplate = 'rss.twig';

        // If we're on the front page, use sitewide configuration
        if ($contenttypeslug == '') {
            $contenttypeslug = 'sitewide';
        }

        if (!isset($this->config[$contenttypeslug]['enabled']) ||
            $this->config[$contenttypeslug]['enabled'] != 'true'
        ) {
            $this->app->abort(404, "Feed for '$contenttypeslug' not found.");
        }

        // Better safe than sorry: abs to prevent negative values
        $amount = (int) abs((!empty($this->config[$contenttypeslug]['feed_records']) ?
            $this->config[$contenttypeslug]['feed_records'] : $defaultFeedRecords));
        // How much to display in the description. Value of 0 means full body!
        $contentLength = (int) abs(
            (!empty($this->config[$contenttypeslug]['content_length']) ?
                $this->config[$contenttypeslug]['content_length'] :
                0)
        );

        // Get our content
        if ($contenttypeslug == 'sitewide') {
            foreach ($this->config[$contenttypeslug]['content_types'] as $types ) {
                $contenttypes[] = $this->app['storage']->getContentType($types);
            }
        } else {
            $contenttypes[] = $this->app['storage']->getContentType($contenttypeslug);
        }

        // Get content for each contenttype we've selected as an assoc. array
        // by content type
        foreach ($contenttypes as $contenttype) {
            $content[$contenttype['slug']] = $this->app['storage']->getContent(
                $contenttype['slug'],
                array('limit' => $amount, 'order' => 'datepublish desc')
            );
        }

        // Now narrow our content array to $amount based on date
        foreach ($content as $slug => $recordid) {
            foreach ($recordid as $record) {
                $key = strtotime($record->values['datepublish']) . $slug;
                $tmp[$key] = $record;
            }
        }

        // Sort the array and return a reduced one
        krsort($tmp);
        $content = array_slice($tmp, 0, $amount);

        if (!$content) {
            $this->app->abort(404, "Feed for '$contenttypeslug' not found.");
        }

        // Then, select which template to use, based on our
        // 'cascading templates rules'
        if (!empty($this->config[$contenttypeslug]['feed_template'])) {
            $template = $this->config[$contenttypeslug]['feed_template'];
        } else {
            $template = $defaultTemplate;
        }

        $this->app['twig.loader.filesystem']->addPath(__DIR__.'/assets/');

        $body = $this->app['render']->render($template, array(
            'records' => $content,
            'content_length' => $contentLength,
            $contenttype['slug'] => $content,
        ));

        return new Response($body, 200,
            array('Content-Type' => 'application/rss+xml; charset=utf-8',
                'Cache-Control' => 's-maxage=3600, public',
            )
        );
    }

}
