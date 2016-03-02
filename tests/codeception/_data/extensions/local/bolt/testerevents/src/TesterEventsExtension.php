<?php

namespace Bolt\Extension\Bolt\TesterEvents;

use Bolt\Events\StorageEvents;
use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TesterEventsExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function subscribe(EventDispatcherInterface $dispatcher)
    {
        // Install the YAML configuration file.
        $this->getConfig();

        /** @var Application $app */
        $app = $this->getContainer();

        // Storage events test callback class
        $storageEventTests = new StorageEventTests($app['storage']);

        // Storage event listeners
        $dispatcher->addListener(StorageEvents::PRE_SAVE,    [$storageEventTests, 'eventPreSave']);
        $dispatcher->addListener(StorageEvents::POST_SAVE,   [$storageEventTests, 'eventPostSave']);
        $dispatcher->addListener(StorageEvents::PRE_DELETE,  [$storageEventTests, 'eventPreDelete']);
        $dispatcher->addListener(StorageEvents::POST_DELETE, [$storageEventTests, 'eventPreDelete']);
    }
}
