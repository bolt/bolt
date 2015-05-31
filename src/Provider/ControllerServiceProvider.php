<?php
namespace Bolt\Provider;

use Bolt\Controller;
use Bolt\Controllers;
use Bolt\Events\ControllerEvents;
use Bolt\Events\MountEvent;
use Bolt\Thumbs\ThumbnailProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ControllerServiceProvider implements ServiceProviderInterface, EventSubscriberInterface
{
    public function register(Application $app)
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
                return $app['config']->get('general/branding/path') . '/extend';
            };
        }
        if (!isset($app['controller.backend.upload.mount_prefix'])) {
            $app['controller.backend.upload.mount_prefix'] = function ($app) {
                return $app['config']->get('general/branding/path') . '/upload';
            };
        }

        $app['controller.backend.authentication'] = $app->share(function () {
            return new Controller\Backend\Authentication();
        });
        $app['controller.backend.extend'] = $app->share(function () {
            return new Controller\Backend\Extend();
        });
        $app['controller.backend.database'] = $app->share(function () {
            return new Controller\Backend\Database();
        });
        $app['controller.backend.file_manager'] = $app->share(function () {
            return new Controller\Backend\FileManager();
        });
        $app['controller.backend.general'] = $app->share(function () {
            return new Controller\Backend\General();
        });
        $app['controller.backend.log'] = $app->share(function () {
            return new Controller\Backend\Log();
        });
        $app['controller.backend.records'] = $app->share(function () {
            return new Controller\Backend\Records();
        });
        $app['controller.backend.upload'] = $app->share(function () {
            return new Controller\Backend\Upload();
        });
        $app['controller.backend.users'] = $app->share(function () {
            return new Controller\Backend\Users();
        });

        $app['controller.async.general'] = $app->share(function () {
            return new Controller\Async\General();
        });
        $app['controller.async.filesystem_manager'] = $app->share(function () {
            return new Controller\Async\FilesystemManager();
        });
        $app['controller.async.stack'] = $app->share(function () {
            return new Controller\Async\Stack();
        });
        $app['controller.async.system_checks'] = $app->share(function () {
            return new Controller\Async\SystemChecks();
        });

        $app['controller.frontend'] = $app->share(function () {
            return new Controller\Frontend();
        });
        $app['controller.requirement'] = $app->share(function ($app) {
            return new Controller\Requirement($app['config']);
        });
        $app['controller.requirement.deprecated'] = $app->share(function ($app) {
            return new Controllers\Routing($app['config']);
        });

        $app['controller.classmap'] = [
            'Bolt\\Controllers\\Frontend' => 'controller.frontend',
            'Bolt\\Controllers\\Routing'  => 'controller.requirement.deprecated',
        ];
    }

    public function boot(Application $app)
    {
        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $app['dispatcher'];
        $dispatcher->addSubscriber($this);

        /** @deprecated Since 2.3 and will be removed in Bolt v3.0 */
        $dispatcher->addListener(ControllerEvents::MOUNT, [$app, 'initMountpoints'], -10);

        $event = new MountEvent($app);
        $dispatcher->dispatch(ControllerEvents::MOUNT, $event);
        $event->finish();
    }

    public function mount(MountEvent $event)
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
            'filesystem_manager',
            'stack',
            'system_checks',
        ];
        foreach ($asyncKeys as $controller) {
            $event->mount($prefix, $app['controller.async.' . $controller]);
        }

        // Mount the Extend controller
        $prefix = $app['controller.backend.extend.mount_prefix'];
        $event->mount($prefix, $app['controller.backend.extend']);

        // Mount the Upload controller
        $prefix = $app['controller.backend.extend.mount_prefix'];
        $event->mount($prefix, $app['controller.backend.upload']);

        // Mount the 'thumbnail' provider on /thumbs.
        $event->mount('/thumbs', new ThumbnailProvider());

        // Mount the Frontend controller
        $event->mount('', $app['controller.frontend'], -50);
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
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        //$e = $event->getException();
    }

    public static function getSubscribedEvents()
    {
        return [
            ControllerEvents::MOUNT => 'mount',
            KernelEvents::REQUEST   => ['onKernelRequest', 32], // Higher than 32 and we don't know the controller
            KernelEvents::RESPONSE  => ['onKernelResponse', -128],
            KernelEvents::EXCEPTION => ['onKernelException', -128],
        ];
    }
}
