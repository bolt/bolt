<?php

namespace Bolt\Twig\Handler;

use Silex;

/**
 * Bolt specific Twig functions and filters that provide routing functionality
 *
 * @internal
 */
class RoutingHandler
{
    /** @var \Silex\Application */
    private $app;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get canonical url for current request.
     *
     * @return string|null
     */
    public function canonical()
    {
        return $this->app['canonical']->getUrl();
    }
}
