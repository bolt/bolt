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
        // This exposes the main upload object as a service
        $app['extend'] = $app->share(function ($app) { 
            
            
        });
        
        
    }
    
    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];

        $app['twig']->addGlobal('title', __("Extend Bolt"));
        
        $ctr->get("", array($this, 'overview'))
            ->before(array($this, 'before'))
            ->bind('overview');
        
        $ctr->get("/check", array($this, 'check'))
            ->before(array($this, 'before'))
            ->bind('check');

        return $ctr;

    }
    
    public function overview(Silex\Application $app, Request $request)
    {
        //$runner = new CommandRunner($app);
        return new Response("testing");
        
    }

    public function check(Silex\Application $app, Request $request)
    {
        $runner = new CommandRunner($app);
        return new Response($runner->check());
        
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
            $app->abort(404, "You must be logged in to use this.");
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');
    }
    
    public function boot(Silex\Application $app)
    {
        
    }

}
