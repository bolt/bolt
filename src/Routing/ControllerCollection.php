<?php
namespace Bolt\Routing;

/**
 * When mounting a controller class with a prefix most times you have a route
 * with a blank path (ex: Backend::dashboard). That is the only route that
 * flushes to include an (unwanted) trailing slash.
 *
 * This fixes that trailing slash.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ControllerCollection extends \Silex\ControllerCollection implements DefaultControllerClassAwareInterface
{
    /** @var string|object $defaultControllerClass */
    protected $defaultControllerClass;

    public function setDefaultControllerClass($class)
    {
        $this->defaultControllerClass = $class;
    }

    public function match($pattern, $to = null)
    {
        if ($this->defaultControllerClass && is_string($to) && method_exists($this->defaultControllerClass, $to)) {
            $to = [$this->defaultControllerClass, $to];
        }

        return parent::match($pattern, $to);
    }

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
