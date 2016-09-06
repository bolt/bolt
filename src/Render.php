<?php

namespace Bolt;

use Bolt\Response\BoltResponse;
use Silex;
use Symfony\Component\HttpFoundation\Request;
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
    /** @var boolean */
    public $safe;
    /** @var string */
    public $twigKey;

    /**
     * Set up the object.
     *
     * @param \Silex\Application $app
     * @param bool               $safe
     */
    public function __construct(Silex\Application $app, $safe = false)
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
     * @return \Bolt\Response\BoltResponse
     */
    public function render($template, $vars = [], $globals = [])
    {
        $response = BoltResponse::create(
            $this->app[$this->twigKey]->loadTemplate($template),
            $vars,
            $globals
        );
        $response->setStopwatch($this->app['stopwatch']);

        return $response;
    }

    /**
     * Check if the template exists.
     *
     * @param string $template The name of the template.
     *
     * @return bool
     */
    public function hasTemplate($template)
    {
        /** @var \Twig_Environment $env */
        $env = $this->app[$this->twigKey];
        $loader = $env->getLoader();

        /*
         * Twig_ExistsLoaderInterface is getting merged into
         * Twig_LoaderInterface in Twig 2.0. Check for this
         * instead once we are there, and remove getSource() check.
         */
        if ($loader instanceof \Twig_ExistsLoaderInterface) {
            return $loader->exists($template);
        }

        try {
            $loader->getSource($template);
        } catch (\Twig_Error_Loader $e) {
            return false;
        }

        return true;
    }

    /**
     * Postprocess the rendered HTML: insert the snippets, and stuff.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function postProcess(Request $request, Response $response)
    {
        /** @var \Bolt\Asset\QueueInterface $queue */
        if (!$this->app['request_stack']->getCurrentRequest()->isXmlHttpRequest()) {
            foreach ($this->app['asset.queues'] as $queue) {
                $queue->process($request, $response);
            }
        }
        $this->cacheRequest($response);
    }

    /**
     * Retrieve a fully cached page from cache.
     *
     * @return \Symfony\Component\HttpFoundation\Response|boolean
     */
    public function fetchCachedRequest()
    {
        $response = false;
        if ($this->checkCacheConditions('request', true)) {
            $key = md5($this->app['request']->getPathInfo() . $this->app['request']->getQueryString());

            $result = $this->app['cache']->fetch($key);

            // If we have a result, prepare a Response.
            if (!empty($result)) {
                $response = new Response($result, Response::HTTP_OK);

                // Note that we set the cache-control header to _half_ the
                // maximum duration, otherwise a proxy/cache might keep the
                // cache twice as long in the worst case scenario, and now it's
                // only 50% max, but likely less
                // 's_maxage' sets the cache for shared caches.
                // max_age sets it for regular browser caches

                $age = $this->cacheDuration() / 2;

                $response->setMaxAge($age)->setSharedMaxAge($age);
            }
        }

        return $response;
    }

    /**
     * Store a fully rendered (and post-processed) page to cache.
     *
     * @param Response $response
     */
    public function cacheRequest(Response $response)
    {
        if ($this->checkCacheConditions('request')) {
            $html = $response->getContent();

            // This is where the magic happens.. We also store it with an empty
            // 'template' name, so we can later fetch it by its request.
            $key = md5($this->app['request']->getPathInfo() . $this->app['request']->getQueryString());
            $this->app['cache']->save($key, $html, $this->cacheDuration());
        }
    }

    /**
     * Get the duration (in seconds) for the cache.
     *
     * @return integer
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
