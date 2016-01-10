<?php

namespace Bolt\Routing;

use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Silex\Application;
use Silex\Route;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This is the root controller collection.
 *
 * When $app->flush() is called, the controller mount event is dispatched.
 *
 * This allows the controllers to be built up right before they are needed.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class RootControllerCollection extends ControllerCollection
{
    /** @var Application */
    protected $app;
    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * Constructor.
     *
     * @param Application              $app
     * @param EventDispatcherInterface $dispatcher
     * @param Route                    $defaultRoute
     */
    public function __construct(Application $app, EventDispatcherInterface $dispatcher, Route $defaultRoute)
    {
        parent::__construct($defaultRoute);
        $this->app = $app;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function flush($prefix = '')
    {
        $event = new MountEvent($this->app, $this);

        $this->dispatcher->dispatch(ControllerEvents::MOUNT, $event);

        return parent::flush($prefix);
    }
}
