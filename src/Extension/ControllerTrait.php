<?php

namespace Bolt\Extension;

use Bolt\Events\MountEvent;
use Bolt\Routing\DefaultControllerClassAwareInterface;
use Pimple as Container;
use Silex\ControllerCollection;

/**
 * Controller routes trait for an extension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
trait ControllerTrait
{
    /**
     * Define frontend routes here.
     *
     * @param ControllerCollection $collection
     */
    protected function registerFrontendRoutes(ControllerCollection $collection)
    {
    }

    /**
     * Define backend routes here.
     *
     * @param ControllerCollection $collection
     */
    protected function registerBackendRoutes(ControllerCollection $collection)
    {
    }

    /**
     * Mounts the routes defined in registerFrontendRoutes() and registerBackendRoutes().
     *
     * @param MountEvent $event
     *
     * @internal
     */
    final public function onMountRoutes(MountEvent $event)
    {
        $app = $this->getContainer();

        $collection = $app['controllers_factory'];
        if ($collection instanceof DefaultControllerClassAwareInterface) {
            $collection->setDefaultControllerClass($this);
        }
        $this->registerFrontendRoutes($collection);
        $event->mount('/', $collection);

        $collection = $app['controllers_factory'];
        if ($collection instanceof DefaultControllerClassAwareInterface) {
            $collection->setDefaultControllerClass($this);
        }
        $this->registerBackendRoutes($collection);
        $event->mount($app['controller.backend.mount_prefix'], $collection);
    }

    /** @return Container */
    abstract protected function getContainer();
}
