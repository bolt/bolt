<?php

namespace Bolt\Routing;

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
        if ($refCls->implementsInterface('\Bolt\Extension\ExtensionInterface')) {
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
