<?php

namespace Bolt\Twig\Runtime;

use Bolt\Asset\Widget\Queue;
use Bolt\Controller\Zone;
use Twig\Environment;
use Twig\Markup;

/**
 * Bolt specific Twig functions and filters for HTML.
 *
 * @internal
 */
class WidgetRuntime
{
    /** @var Queue */
    private $widgetQueue;

    /**
     * Constructor.
     *
     * @param Queue $widgetQueue
     */
    public function __construct(Queue $widgetQueue)
    {
        $this->widgetQueue = $widgetQueue;
    }

    /**
     * Return the number of widgets in the queue for a given type / location.
     *
     * @param Environment $env
     * @param string      $location Location (e.g. 'dashboard_aside_top')
     * @param string      $zone     Either Zone::FRONTEND or Zone::BACKEND
     *
     * @return int
     */
    public function countWidgets(Environment $env, $location = null, $zone = Zone::FRONTEND)
    {
        if ($location === null && $env->isStrictVariables()) {
            throw new \InvalidArgumentException('countwidgets() requires a location, none given');
        }

        return $this->widgetQueue->count($location, $zone);
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
     * @param Environment $env
     * @param string      $location Location (e.g. 'dashboard_aside_top')
     * @param string      $zone     Either Zone::FRONTEND or Zone::BACKEND
     *
     * @return bool
     */
    public function hasWidgets(Environment $env, $location = null, $zone = Zone::FRONTEND)
    {
        if ($location === null && $env->isStrictVariables()) {
            throw new \InvalidArgumentException('haswidgets() requires a location, none given');
        }

        return $this->widgetQueue->has($location, $zone);
    }

    /**
     * Renders a particular widget type on the given location.
     *
     * @param Environment $env
     * @param string      $location Location (e.g. 'dashboard_aside_top')
     * @param string      $zone     Either Zone::FRONTEND or Zone::BACKEND
     * @param string      $wrapper
     *
     * @return string|Markup
     */
    public function widgets(Environment $env, $location = null, $zone = Zone::FRONTEND, $wrapper = 'widgetwrapper.twig')
    {
        if ($location === null && $env->isStrictVariables()) {
            throw new \InvalidArgumentException('widgets() requires a location, none given');
        }

        return $this->widgetQueue->render($location, $zone, $wrapper);
    }
}
