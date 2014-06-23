<?php

namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Sirius\Upload\Handler as UploadHandler;
use Sirius\Upload\Container\Local;
use Sirius\Upload\Result\File;
use Sirius\Upload\Result\Collection;



use Symfony\Component\HttpFoundation\File\UploadedFile;

class Upload implements ControllerProviderInterface, ServiceProviderInterface
{
    
    public $app;
    public $uploaddir;
    
    public function register(Silex\Application $app)
    {
        // This exposes the main upload object as a service
        $app['upload'] = $app->share(function ($app) { 
            
            $allowedExensions = $app['config']->get('general/accept_file_types');
            $uploadHandler = new UploadHandler($app['upload.container']);
            $uploadHandler->setPrefix($app['upload.prefix']);
            $uploadHandler->setOverwrite($app['upload.overwrite']);
            $uploadHandler->addRule('extension', array('allowed' => $allowedExensions));
            return $uploadHandler;
        });
        
        
        // This exposes the file container as a configurabole object please refer to:
        // Sirius\Upload\Container\ContainerInterface 
        // Any compatible file handler can be used.
        $app['upload.container'] = $app->share(function ($app) {
            $base = $app['resources']->getPath($app['upload.namespace']);
            if(!is_writable($base)) {
                throw new \RuntimeException("Unable to write to upload destination. Check permissions on $base", 1);
            }
            $container = new Local($base);
            return $container;
        });
        
        // This allows multiple upload locations, all prefixed with a namespace. The default is /files
        // Note, if you want to provide an alternative namespace, you must set a path on the $app['resources']
        // service
        $app['upload.namespace'] = 'files';
        
        // This gets prepended to all file saves, can be reset to "" or add your own closure for more complex ones.
        $app['upload.prefix'] = date('Y-m')."/";
        
        $app['upload.overwrite'] = false;
    }
    
    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];
        $controller = $this;
        $ctr->match( "/{namespace}", function(Silex\Application $app, Request $request, $namespace = null) use($controller){

            if($namespace === "") {
                $namespace = $app['upload.namespace'];
            }
            return $controller->uploadFile($app, $request, $namespace);
        
        })->assert('namespace', '.*');

        return $ctr;

    }

    public function uploadFile(Silex\Application $app, Request $request, $namespace)
    {
        
        $app['upload.namespace'] = $namespace;
        
        $files = $request->files->get($namespace);
        if(!$files) {
            return new JsonResponse(array());
        } 
        $filesToProcess = array();
        foreach($files as $file) {
            if($file instanceof UploadedFile) {
                $filesToProcess[] = array(
                    'name'=> $file->getClientOriginalName(),
                    'tmp_name' => $file->getPathName()
                );
            } else {
                $filesToProcess[] = $file;
            }
        } 
        
                
        $result = $app['upload']->process($filesToProcess);

        if ($result->isValid()) {
            $result->confirm();
            if($result instanceof File) {
                $successfulFiles = array($result->name);
            } elseif($result instanceof Collection) {
                foreach($result as $resultFile) {
                    $successfulFiles[] = array(
                        'url'=>$namespace."/".$resultFile->name,
                        'name'=>$resultFile->name
                    );
                }
            }
            return new JsonResponse($successfulFiles);
        } else {
            $result->clear();
            $errorFiles = array();
            foreach($result as $resultFile) {
                $errors = $resultFile->getMessages();
                $errorFiles[] = array(
                    'url'=>$namespace."/".$resultFile->original_name,
                    'name'=>$resultFile->original_name,
                    'error' => $errors[0]->__toString()
                ); 
            }
            
            return new JsonResponse($errorFiles);
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
            $app->abort(404, "You must be logged in to use this.");
        }

        // Stop the 'stopwatch' for the profiler.
        $app['stopwatch']->stop('bolt.backend.before');
    }
    
    public function boot(Silex\Application $app)
    {
        
    }

}
