<?php
namespace Bolt\Routing;

/**
 * Handles resolving callbacks from routing.yml that specify a class name
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class CallbackResolver extends \Silex\CallbackResolver
{
    protected $app;
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
            list($cls) = $name;
            if (is_object($cls)) {
                return false;
            }
        } elseif (is_string($name)) {
            $parts = explode('::', $name);
            $cls = reset($parts);
        } else {
            return false;
        }
        return isset($this->classmap[$cls]);
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
            $service = $this->classmap[$cls];
            $name = "$service:$method";
        } elseif (strpos($name, '::') > 0) {
            $parts = explode('::', $name);
            $cls = reset($parts);
            $method = end($parts);
            $service = $this->classmap[$cls];
            $name = "$service:$method";
        }
        return parent::convertCallback($name);
    }
}
