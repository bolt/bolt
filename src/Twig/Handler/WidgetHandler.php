<?php

namespace Bolt\Twig\Handler;

use Bolt\Controller\Zone;
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
     * @param string $location Location (e.g. 'dashboard_aside_top')
     * @param string $zone     Either Zone::FRONTEND or Zone::BACKEND
     *
     * @return integer
     */
    public function countWidgets($location = null, $zone = Zone::FRONTEND)
    {
        if ($location === null && $this->app['twig.options']['strict_variables'] === true) {
            throw new \InvalidArgumentException('countwidgets() requires a location, none given');
        }

        return $this->app['asset.queue.widget']->countItemsInQueue($location, $zone);
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
     * @param string $location Location (e.g. 'dashboard_aside_top')
     * @param string $zone     Either Zone::FRONTEND or Zone::BACKEND
     *
     * @return boolean
     */
    public function hasWidgets($location = null, $zone = Zone::FRONTEND)
    {
        if ($location === null && $this->app['twig.options']['strict_variables'] === true) {
            throw new \InvalidArgumentException('haswidgets() requires a location, none given');
        }

        return $this->app['asset.queue.widget']->hasItemsInQueue($location, $zone);
    }

    /**
     * Renders a particular widget type on the given location.
     *
     * @param string $location Location (e.g. 'dashboard_aside_top')
     * @param string $zone     Either Zone::FRONTEND or Zone::BACKEND
     *
     * @return \Twig_Markup|string
     */
    public function widgets($location = null, $zone = Zone::FRONTEND, $wrapper = 'widgetwrapper.twig')
    {
        if ($location === null && $this->app['twig.options']['strict_variables'] === true) {
            throw new \InvalidArgumentException('widgets() requires a location, none given');
        }

        return $this->app['asset.queue.widget']->render($location, $zone, $wrapper);
    }
}
