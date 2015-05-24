<?php

namespace Bolt;

use Bolt\Response\BoltResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wrapper around Twig's render() function. Handles the following responsibilities:.
 *
 * - Calls twig's render
 * - Stores a page in cache, if needed
 * - Store template (partials) in cache, if needed
 * - Fetches pages or template (partials) from cache
 *
 * @author Bob den Otter, bob@twokings.nl
 */
class Render
{
    public $app;
    public $safe;

    /**
     * Set up the object.
     *
     * @param \Bolt\Application|\Silex\Application $app
     * @param bool                                 $safe
     */
    public function __construct(Application $app, $safe = false)
    {
        $this->app = $app;
        $this->safe = $safe;
        if ($safe) {
            $this->twigKey = 'safe_twig';
        } else {
            $this->twigKey = 'twig';
        }
    }

    /**
     * Render a template, possibly store it in cache. Or, if applicable, return the cached result.
     *
     * @param string $template the template name
     * @param array  $vars     array of context variables
     * @param array  $globals  array of global variables
     *
     * @return mixed
     */
    public function render($template, $vars = array(), $globals = array())
    {
        // Start the 'stopwatch' for the profiler.
        $this->app['stopwatch']->start('bolt.render', 'template');

        $response = BoltResponse::create(
            $this->app[$this->twigKey]->loadTemplate($template),
            $vars,
            $globals
        );

        // Stop the 'stopwatch' for the profiler.
        $this->app['stopwatch']->stop('bolt.render');

        return $response;
    }

    /**
     * Postprocess the rendered HTML: insert the snippets, and stuff.
     *
     * @param Response $response
     *
     * @return string
     */
    public function postProcess(Response $response)
    {
        $html = $response->getContent();
        $html = $this->app['extensions']->processSnippetQueue($html);
        $html = $this->app['extensions']->processAssets($html);
        $this->cacheRequest($html);

        return $html;
    }

    /**
     * Retrieve a fully cached page from cache.
     *
     * @return mixed
     */
    public function fetchCachedRequest()
    {
        $result = null;
        if ($this->checkCacheConditions('request', true)) {
            $key = md5($this->app['request']->getPathInfo() . $this->app['request']->getQueryString());

            $result = $this->app['cache']->fetch($key);

            // If we have a result, prepare a Response.
            if (!empty($result)) {
                // Note that we set the cache-control header to _half_ the maximum duration,
                // otherwise a proxy/cache might keep the cache twice as long in the worst case
                // scenario, and now it's only 50% max, but likely less
                $headers = array(
                    'Cache-Control' => 's-maxage=' . ($this->cacheDuration() / 2),
                );
                $result = new Response($result, Response::HTTP_OK, $headers);
            }
        }

        return $result;
    }

    /**
     * Store a fully rendered (and postprocessed) page to cache.
     *
     * @param $html
     */
    public function cacheRequest($html)
    {
        if ($this->checkCacheConditions('request')) {
            // This is where the magic happens.. We also store it with an empty 'template' name,
            // So we can later fetch it by its request.
            $key = md5($this->app['request']->getPathInfo() . $this->app['request']->getQueryString());
            $this->app['cache']->save($key, $html, $this->cacheDuration());
        }
    }

    /**
     * Get the duration (in seconds) for the cache.
     *
     * @return int;
     */
    public function cacheDuration()
    {
        // in minutes.
        $duration = $this->app['config']->get('general/caching/duration', 10);

        // in seconds.
        return intval($duration) * 60;
    }

    /**
     * Check if the current conditions are suitable for caching.
     *
     * @param string $type
     * @param bool   $checkoverride
     *
     * @return bool
     */
    public function checkCacheConditions($type = 'template', $checkoverride = false)
    {
        // Do not cache in "safe" mode: we don't want to accidentally bleed
        // sensitive data from a previous unsafe run.
        if ($this->safe) {
            return false;
        }

        // Only cache pages in the frontend.
        if ($this->app['config']->getWhichEnd() !== 'frontend') {
            return false;
        }

        // Only cache for 'get' requests.
        if ($this->app['request']->getMethod() !== 'GET') {
            return false;
        }

        // Don't use the cache, if not enabled in the config.
        if (!$this->app['config']->get('general/caching/' . $type)) {
            return false;
        }

        // Don't use the cache, if we're currently logged in. (unless explicitly enabled in config.yml
        if (!$this->app['config']->get('general/caching/authenticated') &&
            $this->app['users']->getCurrentUsername() !== null) {
            return false;
        }

        // if we've added 'force_refresh=1', we don't use the cache. Note, in most cases,
        // we don't _fetch_ from the cache, but we do allow _saving_ to the cache.
        if ($checkoverride && $this->app['request']->get('force_refresh') == 1) {
            return false;
        }

        // All's well!
        return true;
    }
}
