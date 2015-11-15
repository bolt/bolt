<?php

namespace Bolt\Twig\Handler;

use Bolt\Helpers\Html;
use Bolt\Helpers\Str;
use Bolt\Legacy\Content;
use Maid\Maid;
use Silex;

/**
 * Bolt specific Twig functions and filters for HTML
 *
 * @internal
 */
class WidgetsHandler
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
     * Gets a list of the registered widgets.
     *
     * @return array
     */
    public function getwidgets()
    {
        return $this->app['asset.queue.widget']->getQueue();
    }

    /**
     * Renders a particular widget type on the given location.
     *
     * @param string $type     Either 'frontend' or 'backend'
     * @param string $location Location (e.g. 'dashboard_aside_top')
     *
     * @return \Twig_Markup|string
     */
    public function widgets($type = '', $location = '')
    {
        return $this->app['asset.queue.widget']->render($type, $location);
    }
}
