<?php

namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Bolt\Composer\CommandRunner;

class Extend implements ControllerProviderInterface, ServiceProviderInterface
{
    public $app;

    public function register(Silex\Application $app)
    {
        $app['extend.site'] = 'http://beta.extensions.bolt.cm/';
        $app['extend.repo'] = 'http://beta.extensions.bolt.cm/list.json';

        // This exposes the main upload object as a service
        $app['extend.runner'] = $app->share(
            function ($app) {
                $runner = new CommandRunner($app, $app['extend.repo']);

                return $runner;
            }
        );
    }

    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];

        $app['twig']->addGlobal('title', $app['translator']->trans('Extend Bolt'));

        $ctr->get('', array($this, 'overview'))
            ->before(array($this, 'before'))
            ->bind('extend');

        $ctr->get('/check', array($this, 'check'))
            ->before(array($this, 'before'))
            ->bind('check');

        $ctr->get('/update', array($this, 'update'))
            ->before(array($this, 'before'))
            ->bind('update');

        $ctr->get('/install', array($this, 'install'))
            ->before(array($this, 'before'))
            ->bind('install');

        $ctr->get('/uninstall', array($this, 'uninstall'))
            ->before(array($this, 'before'))
            ->bind('uninstall');

        $ctr->get('/installed', array($this, 'installed'))
            ->before(array($this, 'before'))
            ->bind('installed');

        $ctr->get('/installAll', array($this, 'installAll'))
            ->before(array($this, 'before'))
            ->bind('installAll');

        return $ctr;
    }

    public function overview(Silex\Application $app, Request $request)
    {
        return $app['render']->render(
            'extend/extend.twig',
            array(
                'messages' => $app['extend.runner']->messages,
                'site' => $app['extend.site']
            )
        );
    }

    public function check(Silex\Application $app, Request $request)
    {
        return new JsonResponse($app['extend.runner']->check());

    }

    public function update(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');

        return new Response($app['extend.runner']->update($package));
    }

    public function install(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');
        $version = $request->get('version');

        return new Response($app['extend.runner']->install($package, $version));
    }

    public function uninstall(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');

        return new Response($app['extend.runner']->uninstall($package));
    }

    public function installed(Silex\Application $app, Request $request)
    {
        $result = $app['extend.runner']->installed();
        if ($result instanceof Response) {
            return $result;
        } else {
            return new Response($result);
        }
    }

    public function installAll(Silex\Application $app, Request $request)
    {
        return new Response($app['extend.runner']->installAll());
    }

    /**
     * Middleware function to check whether a user is logged on.
     */
    public function before(Request $request, \Bolt\Application $app)
    {
        // Start the 'stopwatch' for the profiler.
        $app['stopwatch']->start('bolt.backend.before');

        // If there's no active session, don't do anything..
        if (!$app['users']->isValidSession()) {
            $app->abort(404, 'You must be logged in to use this.');
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');
    }

    public function boot(Silex\Application $app)
    {
    }
}
