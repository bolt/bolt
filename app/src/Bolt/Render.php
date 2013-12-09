<?php

namespace Bolt;

use Silex;
use Symfony\Component\HttpFoundation\Response;


/**
 * Wrapper around Twig's render() function.
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 */
class Render
{

    /**
     * Set up the object.
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    public function render($template, $vars = array())
    {

        // Start the 'stopwatch' for the profiler.
        $this->app['stopwatch']->start('bolt.render', 'template');

        if ($html = $this->fetchCachedPage($template)) {

            // Do nothing.. The page is fetched from cache..

        } else {

            $html = $this->app['twig']->render($template, $vars);

            $this->cacheRenderedPage($template, $html);

        }

        // Stop the 'stopwatch' for the profiler.
        $this->app['stopwatch']->stop('bolt.render');


        return $html;

    }

    
    public function postProces(Response $response)
    {
        $html = $response->getContent();

        $html = $this->app['extensions']->processSnippetQueue($html);

        $this->cacheRequest($html);

        return $html;

    }


    public function fetchCachedPage($template)
    {
        $key = md5($template . $this->app['request']->getRequestUri());

        return $this->app['cache']->fetch($key, $html);

    }


    public function fetchCachedRequest()
    {
        $key = md5($this->app['request']->getRequestUri());

        return $this->app['cache']->fetch($key, $html);

    }

    public function cacheRenderedPage($template, $html)
    {

        if ($this->app['end'] == "frontend" && $this->app['config']->get('general/caching/templates')) {

            // Store it part-wise, with the correct template name..
            $key = md5($template . $this->app['request']->getRequestUri());
            $this->app['cache']->save($key, $html, 300);

        }

    }

    public function cacheRequest($html) {

        if ($this->app['end'] == "frontend" && $this->app['config']->get('general/caching/request')) {

            // This is where the magic happens.. We also store it with an empty 'template' name,
            // So we can later fetch it by its request..
            $key = md5($this->app['request']->getRequestUri());
            $this->app['cache']->save($key, $html, 300);

        }

    }

}
