<?php
namespace Bolt\Provider;

use Bolt\Controller;
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
        $app['controller.backend.mount_prefix'] = function ($app) {
            return $app['config']->get('general/branding/path');
        };
        $app['controller.backend.extend.mount_prefix'] = function ($app) {
            return $app['config']->get('general/branding/path') . '/extend';
        };
        $app['controller.backend.upload.mount_prefix'] = function ($app) {
            return $app['config']->get('general/branding/path') . '/upload';
        };

        $app['controller.backend'] = $app->share(function () {
            return new Controller\Backend\Backend();
        });
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

        $app['controller.async'] = $app->share(function () {
            return new Controller\Async();
        });

        $app['controller.frontend'] = $app->share(function () {
            return new Controller\Frontend();
        });

        $app['controller.classmap'] = array(
            'Bolt\\Controllers\\Frontend' => 'controller.frontend',
            'Bolt\\Controller\\Frontend'  => 'controller.frontend',
            'Bolt\\Controllers\\Routing'  => 'controller.frontend',
            'Bolt\\Controller\\Routing'   => 'controller.frontend',
        );
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
        $prefix = $app['controller.backend.mount_prefix'];
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
            $event->mount($prefix, $app['controller.' . $controller]);
        }

        // Mount the Async controller
        $event->mount('/async', $app['controller.async']);

        // Mount the Extend controller
        $prefix = $app['controller.backend.extend.mount_prefix'];
        $event->mount($prefix, $app['controller.backend.extend']);

        // Mount the Upload controller
        $prefix = $app['controller.backend.extend.mount_prefix'];
        $event->mount($prefix, $app['controller.backend.upload']);

        // Mount the 'thumbnail' provider on /thumbs.
        $event->mount('/thumbs', new ThumbnailProvider());

        // Mount the Frontend controller
        $event->mount('', $app['controller.frontend']);
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
        return array(
            ControllerEvents::MOUNT => 'mount',
            KernelEvents::REQUEST   => array('onKernelRequest', 32), // Higher than 32 and we don't know the controller
            KernelEvents::RESPONSE  => array('onKernelResponse', -128),
            KernelEvents::EXCEPTION => array('onKernelException', -128),
        );
    }
}
