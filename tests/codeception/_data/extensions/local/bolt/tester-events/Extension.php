<?php

namespace Bolt\Extension\Bolt\TesterEvents;

use Bolt\Events\StorageEvents;

class Extension extends \Bolt\BaseExtension
{
    /** StorageEventTests */
    private $storageEventTests;

    public function getName()
    {
        return 'TesterEvents';
    }

    public function initialize()
    {
        // Include class files
        require_once __DIR__ . '/src/StorageEventTests.php';

        // Storage events test callback class
        $this->storageEventTests = new StorageEventTests($this->app);

        // Storage event listeners
        $this->app['dispatcher']->addListener(StorageEvents::PRE_SAVE,    [$this->storageEventTests, 'eventPreSave']);
        $this->app['dispatcher']->addListener(StorageEvents::POST_SAVE,   [$this->storageEventTests, 'eventPostSave']);
        $this->app['dispatcher']->addListener(StorageEvents::PRE_DELETE,  [$this->storageEventTests, 'eventPreDelete']);
        $this->app['dispatcher']->addListener(StorageEvents::POST_DELETE, [$this->storageEventTests, 'eventPreDelete']);
    }
}
