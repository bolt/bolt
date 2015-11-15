<?php

namespace Bolt\Twig\Handler;

use Silex;

/**
 * Bolt specific Twig functions and filters for HTML
 *
 * @internal
 */
class WidgetHandler
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
     * Return the number of widgets in the queue for a given type / location.
     *
     * @param string $type     Either 'frontend' or 'backend'
     * @param string $location Location (e.g. 'dashboard_aside_top')
     *
     * @return integer
     */
    public function countWidgets($type, $location)
    {
        return $this->app['asset.queue.widget']->countItemsInQueue($type, $location);
    }

    /**
     * Gets a list of the registered widgets.
     *
     * @return array
     */
    public function getWidgets()
    {
        return $this->app['asset.queue.widget']->getQueue();
    }

    /**
     * Check if a type / location has widgets in the queue.
     *
     * @param string $type     Either 'frontend' or 'backend'
     * @param string $location Location (e.g. 'dashboard_aside_top')
     *
     * @return boolean
     */
    public function hasWidgets($type, $location)
    {
        return $this->app['asset.queue.widget']->hasItemsInQueue($type, $location);
    }

    /**
     * Renders a particular widget type on the given location.
     *
     * @param string $type     Either 'frontend' or 'backend'
     * @param string $location Location (e.g. 'dashboard_aside_top')
     *
     * @return \Twig_Markup|string
     */
    public function widgets($type, $location)
    {
        return $this->app['asset.queue.widget']->render($type, $location);
    }
}
