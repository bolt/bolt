<?php

namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;

use Bolt\Composer\CommandRunner;

class Extend implements ControllerProviderInterface, ServiceProviderInterface
{

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
        
        $ctr->get('/installPackage', array($this, 'installPackage'))
            ->before(array($this, 'before'))
            ->bind('installPackage');
            
        $ctr->get('/installInfo', array($this, 'installInfo'))
            ->before(array($this, 'before'))
            ->bind('installInfo');
        
        $ctr->get('/packageInfo', array($this, 'packageInfo'))
            ->before(array($this, 'before'))
            ->bind('packageInfo');
            
        $ctr->get('/generateTheme', array($this, 'generateTheme'))
            ->before(array($this, 'before'))
            ->bind('generateTheme');

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
    

    public function installPackage(Silex\Application $app, Request $request)
    {
        return $app['render']->render(
            'extend/install-package.twig',
            array(
                'messages' => $app['extend.runner']->messages,
                'site' => $app['extend.site']
            )
        );
    }
    
    public function installInfo(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');
        $versions = array('dev'=>array(),'stable'=>array());
        try {
            $url = $app['extend.site']."info.json?package=".$package."&bolt=".$app['bolt_version'];
            $info = json_decode(file_get_contents($url));
            foreach($info->version as $version) {
                $versions[$version->stability][]=$version;
            }
        } catch (\Exception $e) {
            
        }
        return new JsonResponse($versions);
        
    }
    
    public function packageInfo(Silex\Application $app, Request $request)
    {
        $package = $request->get('package');
        $version = $request->get('version');
        return new JsonResponse($app['extend.runner']->info($package, $version));
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
    
    
   public function generateTheme(Silex\Application $app, Request $request)
    {
        $theme = $request->get('theme');
        $newName = $request->get('name');

        if (! $newName) {
            $newName = basename($theme);
        }
        
        $source = $app['resources']->getPath('extensions').'/vendor/'.$theme;
        $destination = $app['resources']->getPath('themebase').'/'.$newName;
        if (is_dir($source)) {
            try {
                $filesystem = new Filesystem;
                $filesystem->mkdir($destination);
                $filesystem->mirror($source, $destination);
                return new Response($app['translator']->trans('Theme successfully generated. You can now edit it directly from your theme folder.'));
            } catch (\Exception $e) {
               return new Response($app['translator']->trans('We were unable to generate the theme. It is likely that your theme directory is not writable by Bolt. Check the permissions and try reinstalling.')); 
            }   
        }        
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

        // Most of the 'check if user is allowed' happens here: match the current route to the 'allowed' settings.
        if (!$app['users']->isAllowed('extensions')) {
            $app['session']->getFlashBag()->set('error', __('You do not have the right privileges to view that page.'));

            return redirect('dashboard');
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');

    }

    public function boot(Silex\Application $app)
    {
    }
}
