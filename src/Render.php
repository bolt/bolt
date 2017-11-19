<?php

namespace Bolt;

use Bolt\Common\Deprecated;
use Bolt\Response\TemplateResponse;
use Silex;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Loader\ExistsLoaderInterface;

/**
 * Wrapper around Twig's render() function. Handles the following responsibilities:.
 *
 * - Calls twig's render
 * - Stores a page in cache, if needed
 * - Store template (partials) in cache, if needed
 * - Fetches pages or template (partials) from cache
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 * @deprecated Since 3.3, will be removed in 4.0.
 */
class Render
{
    private $app;
    /** @var bool */
    private $safe;

    /**
     * Set up the object.
     *
     * @param \Silex\Application $app
     * @param bool               $safe
     */
    public function __construct(Silex\Application $app, $safe = false)
    {
        Deprecated::method(3.3);

        $this->app = $app;
        $this->safe = $safe;
    }

    public function __get($property)
    {
        Deprecated::method(3.3);

        return $this->$property;
    }

    public function __set($property, $value)
    {
        Deprecated::method(3.3);

        $this->$property = $value;
    }

    /**
     * Render a template, possibly store it in cache. Or, if applicable, return the cached result.
     *
     * @param string|string[] $templateName Template name(s)
     * @param array           $context      Context variables
     * @param array           $globals      Global variables
     *
     * @return TemplateResponse
     *
     * @deprecated Since 3.3, will be removed in 4.0.
     */
    public function render($templateName, $context = [], $globals = [])
    {
        Deprecated::method(3.3);

        $template = $this->app['twig']->resolveTemplate($templateName);

        foreach ($globals as $name => $value) {
            $this->app['twig']->addGlobal($name, $value);
        }

        $html = twig_include($this->app['twig'], $context, $template, [], true, false, $this->safe);

        $response = new TemplateResponse($template->getTemplateName(), $context, $html);

        return $response;
    }

    /**
     * @deprecated Since 3.3, will be removed in 4.0.
     *
     * Check if the template exists.
     *
     * @param string $template the name of the template
     *
     * @return bool
     */
    public function hasTemplate($template)
    {
        Deprecated::method(3.3);

        $loader = $this->app['twig']->getLoader();

        /*
         * Twig_ExistsLoaderInterface is getting merged into
         * Twig_LoaderInterface in Twig 2.0. Check for this
         * instead once we are there, and remove getSource() check.
         */
        if ($loader instanceof ExistsLoaderInterface) {
            return $loader->exists($template);
        }

        try {
            $loader->getSource($template);
        } catch (LoaderError $e) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve a fully cached page from cache.
     *
     * @deprecated Deprecated since 3.1, to be removed in 4.0. @see \Silex\HttpCache
     *
     * @return \Symfony\Component\HttpFoundation\Response|bool
     */
    public function fetchCachedRequest()
    {
        Deprecated::method(3.3);

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
     * @deprecated Deprecated since 3.1, to be removed in 4.0. @see \Silex\HttpCache
     *
     * @param Response $response
     */
    public function cacheRequest(Response $response)
    {
        Deprecated::method(3.3);

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
     * @deprecated Deprecated since 3.1, to be removed in 4.0. @see \Silex\HttpCache
     *
     * @return int
     */
    public function cacheDuration()
    {
        Deprecated::method(3.3);

        // in minutes.
        $duration = $this->app['config']->get('general/caching/duration', 10);

        // in seconds.
        return (int) $duration * 60;
    }

    /**
     * Check if the current conditions are suitable for caching.
     *
     * @deprecated Deprecated since 3.1, to be removed in 4.0. @see \Silex\HttpCache
     *
     * @param string $type
     * @param bool   $checkoverride
     *
     * @return bool
     */
    public function checkCacheConditions($type = 'template', $checkoverride = false)
    {
        Deprecated::method(3.3);

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
