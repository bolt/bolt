<?php

namespace Bolt\Twig\Runtime;

use Bolt\Asset\Widget\Queue;
use Bolt\Controller\Zone;

/**
 * Bolt specific Twig functions and filters for HTML
 *
 * @internal
 */
class WidgetRuntime
{
    /** @var Queue */
    private $widgetQueue;
    /** @var bool */
    private $strictVariables;

    /**
     * Constructor.
     *
     * @param Queue $widgetQueue
     * @param bool  $strictVariables
     */
    public function __construct(Queue $widgetQueue, $strictVariables)
    {
        $this->widgetQueue = $widgetQueue;
        $this->strictVariables = $strictVariables;
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
        if ($location === null && $this->strictVariables === true) {
            throw new \InvalidArgumentException('countwidgets() requires a location, none given');
        }

        return $this->widgetQueue->countItemsInQueue($location, $zone);
    }

    /**
     * Gets a list of the registered widgets.
     *
     * @return array
     */
    public function getWidgets()
    {
        return $this->widgetQueue->getQueue();
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
        if ($location === null && $this->strictVariables === true) {
            throw new \InvalidArgumentException('haswidgets() requires a location, none given');
        }

        return $this->widgetQueue->hasItemsInQueue($location, $zone);
    }

    /**
     * Renders a particular widget type on the given location.
     *
     * @param string $location Location (e.g. 'dashboard_aside_top')
     * @param string $zone     Either Zone::FRONTEND or Zone::BACKEND
     * @param string $wrapper
     *
     * @return string|\Twig_Markup
     */
    public function widgets($location = null, $zone = Zone::FRONTEND, $wrapper = 'widgetwrapper.twig')
    {
        if ($location === null && $this->strictVariables === true) {
            throw new \InvalidArgumentException('widgets() requires a location, none given');
        }

        return $this->widgetQueue->render($location, $zone, $wrapper);
    }
}
