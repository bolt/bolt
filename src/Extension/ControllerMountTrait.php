<?php

namespace Bolt\Extension;

use Bolt\Events\MountEvent;
use Pimple as Container;
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
     * Returns a list of frontend controllers to mount.
     *
     * <pre>
     *  return [
     *      '/foobar' => new FooController(),
     *  ];
     * </pre>
     *
     * @return ControllerCollection[]|ControllerProviderInterface[]
     */
    protected function registerFrontendControllers()
    {
        return [];
    }

    /**
     * Returns a list of frontend route callbacks to add.
     *
     * <pre>
     *  return [
     *      '/foobar' => $callable,
     *  ];
     * </pre>
     *
     * @return array
     */
    protected function registerFrontendRoutes()
    {
        return [];
    }

    /**
     * Returns a list of backend controllers to mount.
     *
     * Note: The backend prefix will be automatically prepended to prefixes defined here.
     *
     * <pre>
     *  return [
     *      '/foobar' => new FooController(),
     *  ];
     * </pre>
     *
     * @return ControllerCollection[]|ControllerProviderInterface[]
     */
    protected function registerBackendControllers()
    {
        return [];
    }

    /**
     * Returns a list of frontend route callbacks to add.
     *
     * <pre>
     *  return [
     *      '/foobar' => $callable,
     *  ];
     * </pre>
     *
     * @return array
     */
    protected function registerBackendRoutes()
    {
        return [];
    }

    /**
     * Mounts the controllers defined in registerControllers().
     *
     * @param MountEvent $event
     *
     * @internal
     */
    final public function onMountControllers(MountEvent $event)
    {
        foreach ($this->registerFrontendControllers() as $prefix => $collection) {
            $event->mount($prefix, $collection);
        }

        $app = $this->getContainer();
        $backendPrefix = $app['controller.backend.mount_prefix'];
        foreach ($this->registerBackendControllers() as $prefix => $collection) {
            $event->mount($backendPrefix . '/' . ltrim($prefix, '/'), $collection);
        }

        foreach ($this->registerFrontendRoutes() as $prefix => $callable) {
            $app->match($prefix, $callable);
        }

        foreach ($this->registerBackendRoutes() as $prefix => $callable) {
            $app->match($backendPrefix . '/' . ltrim($prefix, '/'), $callable);
        }
    }

    /** @return Container */
    abstract protected function getContainer();
}
