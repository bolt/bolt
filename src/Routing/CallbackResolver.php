<?php
namespace Bolt\Routing;

/**
 * Handles resolving callbacks from routing.yml that specify a class name
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CallbackResolver extends \Silex\CallbackResolver
{
    /** @var \Pimple $app */
    protected $app;
    /** @var array $classmap */
    protected $classmap;

    /**
     * CallbackResolver Constructor.
     *
     * @param \Pimple $app
     * @param array   $classmap An array of class names as keys
     *                          mapped to their service name as values
     *                          Ex: 'Bolt\Controller\Frontend' => 'controller.frontend'
     */
    public function __construct(\Pimple $app, array $classmap)
    {
        $this->app = $app;
        $this->classmap = $classmap;
        parent::__construct($app);
    }

    /**
     * Returns true if the string is a valid service method representation or if
     * the string/array references a class contained in the resolver's classmap.
     *
     * @param string $name
     *
     * @return bool
     */
    public function isValid($name)
    {
        if (parent::isValid($name)) {
            return true;
        }
        if (is_array($name)) {
            list($cls, $method) = $name;
            if (is_object($cls)) {
                return false; // No need to convert
            }
            if (is_array($method)) {
                return true; // Need to convert
            }
        } elseif (is_string($name) && strpos($name, '::') > 0) {
            list($cls, $method) = explode('::', $name);
        } else {
            return false; // Can't handle this, maybe already callable
        }

        if (isset($this->classmap[$cls])) {
            return true; // Will use service definition
        }
        if (!class_exists($cls)) {
            return false; // Can't handle this
        }
        $refMethod = new \ReflectionMethod($cls, $method);
        if ($refMethod->isStatic()) {
            return false; // Already valid
        }
        $constructor = $refMethod->getDeclaringClass()->getConstructor();
        // We can create the class if no constructor params, else can't handle it
        return $constructor === null || $constructor->getNumberOfRequiredParameters() === 0;
    }

    /**
     * Converts:
     *
     * - Bolt\\Controller\\Frontend::hompeage to controller.frontend:homepage
     * - [Bolt\\Controller\\Frontend, homepage] to controller.frontend:homepage
     *
     * Those are then converted to valid callbacks with the controller object from application
     *
     * @param string $name
     *
     * @return array A callable array
     */
    public function convertCallback($name)
    {
        if (is_array($name)) {
            list($cls, $method) = $name;
            if (is_array($method)) {
                $params = $method;
                $callback = $this->resolveCallback($cls);

                return function () use ($callback, $params) {
                    return call_user_func_array($callback, $params);
                };
            }
        } elseif (strpos($name, '::') > 0) {
            $parts = explode('::', $name);
            $cls = reset($parts);
            $method = end($parts);
        } else {
            return parent::convertCallback($name);
        }

        if (isset($this->classmap[$cls])) {
            $service = $this->classmap[$cls];

            return parent::convertCallback("$service:$method");
        }

        return [$this->instantiateClass($cls), $method];
    }

    /**
     * Create a new instance of a class.
     *
     * @param string $class
     *
     * @return object
     */
    protected function instantiateClass($class)
    {
        return new $class();
    }
}
