<?php
namespace Bolt\Events;

use Silex\Application;
use Symfony\Component\EventDispatcher\Event;

class MountEvent extends Event
{
    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getApp()
    {
        return $this->app;
    }

    public function mount($prefix, $controllers)
    {
        $this->app->mount($prefix, $controllers);
    }
}
