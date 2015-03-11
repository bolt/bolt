<?php
namespace Bolt\Routing;

/**
 * When mounting a controller class with a prefix most times you have a route
 * with a blank path (ex: Backend::dashboard). That is the only route that
 * flushes to include an (unwanted) trailing slash.
 *
 * This fixes that trailing slash.
 */
class ControllerCollection extends \Silex\ControllerCollection
{
    public function flush($prefix = '')
    {
        $routes = parent::flush($prefix);
        foreach ($routes->all() as $name => $route) {
            if (substr($route->getPath(), -1) === '/') {
                $route->setPath(rtrim($route->getPath(), '/'));
            }
        }
        return $routes;
    }
}
