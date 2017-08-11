<?php

namespace Bolt\Routing;

use Bolt\Common\Deprecated;
use Bolt\Extension\ExtensionInterface;
use Silex;

/**
 * Resolves extensions being used as a controller.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ControllerResolver extends Silex\ControllerResolver
{
    /**
     * If class name passed in is an extension then return
     * the extension instance instead of creating a new class.
     *
     * @param string $class
     *
     * @return object
     */
    protected function instantiateController($class)
    {
        $refCls = new \ReflectionClass($class);
        if ($refCls->implementsInterface(ExtensionInterface::class)) {
            Deprecated::warn('Using Extension class name', 3.3, 'Use controller object in route definition or make the controller a service and use the service name instead.');

            /** @var \Bolt\Extension\ResolvedExtension[] $extensions */
            $extensions = $this->app['extensions']->all();

            foreach ($extensions as $extension) {
                if ($class === get_class($extension)) {
                    return $extension;
                }
            }
        }

        return parent::instantiateController($class);
    }
}
