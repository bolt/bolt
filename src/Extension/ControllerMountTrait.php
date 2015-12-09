<?php

namespace Bolt\Extension;

use Bolt\Events\MountEvent;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

/**
 * Controller mounting trait for an extension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
trait ControllerMountTrait
{
    /**
     * Returns a list of controllers to mount.
     *
     * <pre>
     *  return [
     *      '/foobar' => new FooController(),
     *  ];
     * </pre>
     *
     * @return ControllerCollection[]|ControllerProviderInterface[]
     */
    protected function getControllers()
    {
        return [];
    }

    /**
     * Mounts the controllers defined in getControllers().
     *
     * @param MountEvent $event
     */
    final public function onMount(MountEvent $event)
    {
        foreach ($this->getControllers() as $prefix => $collection) {
            $event->mount($prefix, $collection);
        }
    }
}
