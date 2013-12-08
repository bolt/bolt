<?php

namespace Bolt;

Use Silex;

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
        $this->app['stopwatch']->start('bolt.rendertemplate');

        $html = $this->app['twig']->render($template, $vars);

        // Stop the 'stopwatch' for the profiler.
        $this->app['stopwatch']->stop('bolt.rendertemplate');

        return $html;

    }

}
