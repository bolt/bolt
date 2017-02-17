<?php

namespace Bolt\Provider;

use Bolt\Controller;
use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ControllerServiceProvider implements ServiceProviderInterface, BootableProviderInterface, EventSubscriberInterface
{
    public function register(Container $app)
    {
        if (!isset($app['controller.backend.mount_prefix'])) {
            $app['controller.backend.mount_prefix'] = function ($app) {
                return $app['config']->get('general/branding/path');
            };
        }
        if (!isset($app['controller.async.mount_prefix'])) {
            $app['controller.async.mount_prefix'] = '/async';
        }
        if (!isset($app['controller.backend.extend.mount_prefix'])) {
            $app['controller.backend.extend.mount_prefix'] = function ($app) {
                return $app['config']->get('general/branding/path') . '/extensions';
            };
        }
        if (!isset($app['controller.backend.upload.mount_prefix'])) {
            $app['controller.backend.upload.mount_prefix'] = function ($app) {
                return $app['config']->get('general/branding/path') . '/upload';
            };
        }

        $app['controller.backend.authentication'] = function () {
            return new Controller\Backend\Authentication();
        };
        $app['controller.backend.extend'] = function () {
            return new Controller\Backend\Extend();
        };
        $app['controller.backend.database'] = function () {
            return new Controller\Backend\Database();
        };
        $app['controller.backend.file_manager'] = function () {
            return new Controller\Backend\FileManager();
        };
        $app['controller.backend.general'] = function () {
            return new Controller\Backend\General();
        };
        $app['controller.backend.log'] = function () {
            return new Controller\Backend\Log();
        };
        $app['controller.backend.records'] = function () {
            return new Controller\Backend\Records();
        };
        $app['controller.backend.upload'] = function () {
            return new Controller\Backend\Upload();
        };
        $app['controller.backend.users'] = function () {
            return new Controller\Backend\Users();
        };

        $app['controller.async.general'] = function () {
            return new Controller\Async\General();
        };
        $app['controller.async.filesystem_manager'] = function () {
            return new Controller\Async\FilesystemManager();
        };
        $app['controller.async.embed'] = function () {
            return new Controller\Async\Embed();
        };
        $app['controller.async.records'] = function () {
            return new Controller\Async\Records();
        };
        $app['controller.async.stack'] = function () {
            return new Controller\Async\Stack();
        };
        $app['controller.async.widget'] = function () {
            return new Controller\Async\Widget();
        };

        $app['controller.frontend'] = function () {
            return new Controller\Frontend();
        };
        $app['controller.requirement'] = function ($app) {
            return new Controller\Requirement($app['config']);
        };
    }

    public function boot(Application $app)
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($this);
    }

    public function onMountFrontend(MountEvent $event)
    {
        $app = $event->getApp();
        $event->mount('', $app['controller.frontend']);
    }

    public function onMountBackend(MountEvent $event)
    {
        $app = $event->getApp();

        // Mount the standard collection of backend and controllers
        $prefix = $app['controller.backend.mount_prefix'];
        $backendKeys = [
            'authentication',
            'database',
            'file_manager',
            'general',
            'log',
            'records',
            'users',
        ];
        foreach ($backendKeys as $controller) {
            $event->mount($prefix, $app['controller.backend.' . $controller]);
        }

        // Mount the Async controllers
        $prefix = $app['controller.async.mount_prefix'];
        $asyncKeys = [
            'general',
            'embed',
            'filesystem_manager',
            'records',
            'stack',
            'widget',
        ];
        foreach ($asyncKeys as $controller) {
            $event->mount($prefix, $app['controller.async.' . $controller]);
        }

        // Mount the Extend controller
        $prefix = $app['controller.backend.extend.mount_prefix'];
        $event->mount($prefix, $app['controller.backend.extend']);

        // Mount the Upload controller
        $prefix = $app['controller.backend.upload.mount_prefix'];
        $event->mount($prefix, $app['controller.backend.upload']);
    }

    public static function getSubscribedEvents()
    {
        return [
            ControllerEvents::MOUNT => [
                ['onMountFrontend', -50],
                ['onMountBackend'],
            ],
        ];
    }
}
