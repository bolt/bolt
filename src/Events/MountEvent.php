<?php
namespace Bolt\Events;

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
    /**
     * @var Application
     */
    protected $app;

    /**
     * Controllers grouped by priorities
     *
     * @var array
     */
    protected $priorities = [];

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
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
                $this->app->mount($prefix, $controllers);
            }
        }

        $this->stopPropagation();
    }
}
