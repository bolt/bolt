<?php
namespace Bolt\Events;

use LogicException;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Controllers should be mounted to this event,
 * which will then mount them to the application.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class MountEvent extends Event
{
    /** @var Application */
    protected $app;

    /** @var ControllerCollection */
    protected $collection;

    /** @var array Controllers grouped by priorities */
    protected $priorities = [];

    /**
     * @param Application $app
     * @param ControllerCollection $collection
     */
    public function __construct(Application $app, ControllerCollection $collection)
    {
        $this->app = $app;
        $this->collection = $collection;
    }

    /**
     * @return Application
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Mounts controllers under the given route prefix.
     *
     * @param string                                           $prefix      The route prefix
     * @param ControllerCollection|ControllerProviderInterface $controllers A ControllerCollection or a ControllerProviderInterface instance
     * @param int                                              $priority    Priority at which they should be mounted
     */
    public function mount($prefix, $controllers, $priority = 0)
    {
        $controllers = $this->verifyCollection($controllers);
        $this->priorities[$priority][] = [$prefix, $controllers];
    }

    /**
     * Finish mounting process by sorting them and mounting them to application
     */
    public function finish()
    {
        if ($this->isPropagationStopped()) {
            return;
        }

        krsort($this->priorities);
        foreach ($this->priorities as $priority) {
            foreach ($priority as $list) {
                list($prefix, $controllers) = $list;
                $this->collection->mount($prefix, $controllers);
            }
        }

        $this->stopPropagation();
    }

    /**
     * Verifies collection is correct type and calls connect on providers.
     *
     * Note: This is the same code as {@see Silex\Application::mount}
     *
     * @param ControllerProviderInterface|ControllerCollection $collection
     *
     * @throws LogicException If controllers is not an instance of ControllerProviderInterface or ControllerCollection
     *
     * @return ControllerCollection
     */
    protected function verifyCollection($collection)
    {
        if ($collection instanceof ControllerProviderInterface) {
            $connectedControllers = $collection->connect($this->app);

            if (!$connectedControllers instanceof ControllerCollection) {
                throw new LogicException(
                    sprintf(
                        'The method "%s::connect" must return a "ControllerCollection" instance. Got: "%s"',
                        get_class($collection),
                        is_object($connectedControllers) ?
                            get_class($connectedControllers) : gettype($connectedControllers)
                    )
                );
            }

            $collection = $connectedControllers;
        } elseif (!$collection instanceof ControllerCollection) {
            throw new LogicException(
                'The "mount" method takes either a "ControllerCollection" or a "ControllerProviderInterface" instance.'
            );
        }

        return $collection;
    }
}
