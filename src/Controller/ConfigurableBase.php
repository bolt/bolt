<?php
namespace Bolt\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Silex\Application;
use Silex\CallbackResolver;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add routes from the configuration file.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class ConfigurableBase extends Base
{
    /** @var CallbackResolver $callbackResolver */
    private $callbackResolver;

    public function connect(Application $app)
    {
        $this->callbackResolver = $app['callback_resolver'];

        return parent::connect($app);
    }

    public function before(Request $request)
    {
    }

    public function after(Request $request, Response $response)
    {
    }

    /**
     * Return routes (as arrays) that will be converted to route objects.
     *
     * @return array
     */
    abstract protected function getConfigurationRoutes();

    protected function addRoutes(ControllerCollection $c)
    {
        $routes = $this->getConfigurationRoutes();
        if (!is_array($routes)) {
            throw new \InvalidArgumentException('getConfigurationRoutes return an array');
        }

        foreach ($routes as $name => $config) {
            $this->addRoute($c, $name, $config);
        }
    }

    protected function addRoute(ControllerCollection $ctr, $name, array $config)
    {
        $config = new ArrayCollection($config);

        if (!$path = $config['path']) {
            return;
        }
        if (!$defaults = $config['defaults']) {
            return;
        }
        $defaults = new ArrayCollection($defaults);

        if (!$to = $defaults->remove('_controller')) {
            return;
        }

        $route = $ctr->match($path, $to);

        $before = $defaults->remove('_before') ?: '::before';
        $before = $this->resolveBefore($before);
        $route->before($before);

        $after = $defaults->remove('_after') ?: '::after';
        $after = $this->resolveAfter($after);
        $route->after($after);

        foreach ($defaults as $key => $value) {
            $route->value($key, $value);
        }

        foreach ($config['requirements'] ?: [] as $variable => $callback) {
            $callback = $this->callbackResolver->resolveCallback($callback);
            $requirement = is_callable($callback) ? call_user_func($callback) : $callback;
            $route->assert($variable, $requirement);
        }

        if ($host = $config['host']) {
            $route->getRoute()->setHost($host);
        }

        if ($methods = $config['methods']) {
            $route->getRoute()->setMethods($methods);
        }

        if ($schemes = $config['schemes']) {
            $route->getRoute()->setSchemes($schemes);
        }

        $route->bind($name);
    }

    /**
     * Returns a closure that will resolve the middleware callback
     * to call on kernel request and call it.
     *
     * @param array|string|null $before
     *
     * @return \Closure
     */
    protected function resolveBefore($before)
    {
        $getBefore = $this->resolveMiddleware($before);

        return function (Request $request, Application $app) use ($getBefore) {
            $callback = $getBefore($request);
            if (!is_callable($callback)) {
                return null;
            }

            return call_user_func($callback, $request, $app);
        };
    }

    /**
     * Returns a closure that will resolve the middleware callback
     * to call on kernel response and call it.
     *
     * @param array|string|null $after
     *
     * @return \Closure
     */
    protected function resolveAfter($after)
    {
        $getAfter = $this->resolveMiddleware($after);

        return function (Request $request, Response $response, Application $app) use ($getAfter) {
            $callback = $getAfter($request);
            if (!is_callable($callback)) {
                return null;
            }

            return call_user_func($callback, $request, $response, $app);
        };
    }

    /**
     * Returns a closure that will resolve the class to use
     * in middleware callback if one isn't specified
     *
     * @param array|string|null $callback
     *
     * @return \Closure Invoke to get middleware callback
     */
    protected function resolveMiddleware($callback)
    {
        $callbackResolver = $this->callbackResolver;

        return function (Request $request) use ($callback, $callbackResolver) {
            if (!is_string($callback) || substr($callback, 0, 2) !== '::') {
                return $callbackResolver->resolveCallback($callback);
            }

            $controller = $callbackResolver->resolveCallback($request->attributes->get('_controller'));
            if (is_array($controller)) {
                list($cls, $_) = $controller;
            } elseif (is_string($controller)) {
                if (strpos($controller, '::') !== false) {
                    list($cls, $_) = explode('::', $controller);
                } else {
                    $cls = $controller;
                }
            } else {
                return null;
            }
            $callback = [$cls, substr($callback, 2)];

            return $callback;
        };
    }
}
