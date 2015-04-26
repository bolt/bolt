<?php
namespace Bolt\Provider;

use Bolt\Controllers;
use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

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

        $app['controllers.routing'] = $app->share(function () {
            return new Controllers\Routing();
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

        $dispatcher->addListener(KernelEvents::REQUEST, array($this, 'onKernelRequest'), 32); // Higher than 32 and we don't know the controller
        $dispatcher->addListener(KernelEvents::RESPONSE, array($this, 'onKernelResponse'), -128);
        $dispatcher->addListener(KernelEvents::EXCEPTION, array($this, 'onKernelException'), -128);
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

        // Mount the Upload controller
        $prefix = $app['controllers.backend.extend.mount_prefix'];
        $event->mount($prefix, $app['controllers.backend.upload']);

        $event->mount('', $app['controllers.routing']);
    }

    /**
     * Initial request event.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
    }

    /**
     * Pre-send response event.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
    }

    /**
     * Response event upon exception.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        //$e = $event->getException();
    }

    public static function getSubscribedEvents()
    {
        return array(
            ControllerEvents::MOUNT => 'mount',
        );
    }
}
