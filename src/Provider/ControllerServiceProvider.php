<?php
namespace Bolt\Provider;

use Bolt\Controllers;
use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ControllerServiceProvider implements ServiceProviderInterface, EventSubscriberInterface
{
    public function register(Application $app)
    {
        $app['controllers.backend.mount_prefix'] = function ($app) {
            return $app['config']->get('general/branding/path');
        };
        $app['controllers.backend.extend.mount_prefix'] = function ($app) {
            return $app['config']->get('general/branding/path') . '/extend';
        };
        $app['controllers.backend.upload.mount_prefix'] = function ($app) {
            return $app['config']->get('general/branding/path') . '/upload';
        };

        $app['controllers.backend'] = $app->share(function () {
            return new Controllers\Backend\Backend();
        });
        $app['controllers.backend.authentication'] = $app->share(function () {
            return new Controllers\Backend\Authentication();
        });
        $app['controllers.backend.extend'] = $app->share(function () {
            return new Controllers\Backend\Extend();
        });
        $app['controllers.backend.database'] = $app->share(function () {
            return new Controllers\Backend\Database();
        });
        $app['controllers.backend.file_manager'] = $app->share(function () {
            return new Controllers\Backend\FileManager();
        });
        $app['controllers.backend.log'] = $app->share(function () {
            return new Controllers\Backend\Log();
        });
        $app['controllers.backend.records'] = $app->share(function () {
            return new Controllers\Backend\Records();
        });
        $app['controllers.backend.upload'] = $app->share(function () {
            return new Controllers\Backend\Upload();
        });
        $app['controllers.backend.users'] = $app->share(function () {
            return new Controllers\Backend\Users();
        });
    }

    public function boot(Application $app)
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($this);
        $dispatcher->addListener(ControllerEvents::MOUNT, array($app, 'initMountpoints'), -1);
        $event = new MountEvent($app);
        $dispatcher->dispatch(ControllerEvents::MOUNT, $event);
    }

    public function mount(MountEvent $event)
    {
        $app = $event->getApp();

        // Mount the standard collection of backend controllers
        $prefix = $app['controllers.backend.mount_prefix'];
        $controllerKeys = array(
            'backend',
            'backend.authentication',
            'backend.database',
            'backend.file_manager',
            'backend.log',
            'backend.records',
            'backend.users',
        );
        foreach ($controllerKeys as $controller) {
            $event->mount($prefix, $app['controllers.' . $controller]);
        }

        // Mount the Extend controller
        $prefix = $app['controllers.backend.extend.mount_prefix'];
        $event->mount($prefix, $app['controllers.backend.extend']);
        $app['controllers.backend.extend']->register($app);

        // Mount the Upload controller
        $prefix = $app['controllers.backend.extend.mount_prefix'];
        $event->mount($prefix, $app['controllers.backend.upload']);
    }

    public static function getSubscribedEvents()
    {
        return array(
            ControllerEvents::MOUNT => 'mount',
        );
    }
}
