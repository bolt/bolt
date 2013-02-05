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
            'description' => "Adds capability of providing RSS feeds of your content types.",
            'author' => "WeDesignIt, Patrick van Kouteren",
            'link' => "http://www.wedesignit.nl",
            'version' => "0.2",
            'required_bolt_version' => "1.0 RC",
            'highest_bolt_version' => "1.0 RC",
            'type' => "General",
            'first_releasedate' => "2012-11-06",
            'latest_releasedate' => "2013-02-04"
        );

        return $data;

    }

    function initialize()
    {
        $this->app->match('/{contenttypeslug}/rss/feed.{extension}', array($this, 'feed'))
            ->assert('extension', '(xml|rss)')
            ->assert('contenttypeslug', $this->app['storage']->getContentTypeAssert())
        ;
    }

    public function feed($contenttypeslug)
    {
        $this->disableJquery();
        // Clear snippet list. There's no clear any more, so just set to null
        $this->app['extensions']->clearSnippetQueue();
        $this->app['debugbar'] = false;
        // Defaults for later
        $defaultFeedRecords = 5;
        $defaultContentLength = 100;
        $defaultTemplate = 'rss.twig';

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
                $defaultContentLength)
        );
        $contenttype = $this->app['storage']->getContentType($contenttypeslug);

        $content = $this->app['storage']->getContent(
            $contenttype['slug'],
            array('limit' => $amount, 'order' => 'datepublish desc')
        );

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

        $body = $this->app['twig']->render('RSSFeed/assets/' . $template, array(
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