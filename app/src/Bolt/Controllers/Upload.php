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

use Symfony\Component\HttpFoundation\File\UploadedFile;

class Upload implements ControllerProviderInterface, ServiceProviderInterface
{
    
    public $app;
    public $uploaddir;
    
    public function register(Silex\Application $app)
    {
        $app['upload'] = $app->share(function ($app) { 
            
            $allowedExensions = $app['config']->get('general/accept_file_types');
            $uploadHandler = new UploadHandler($app['upload.container']);
            $uploadHandler->setPrefix($app['upload.prefix']);
            $uploadHandler->addRule('extension', ['allowed' => $allowedExensions]);
            return $uploadHandler;
        });
        
        
        $app['upload.container'] = $app->share(function ($app) {
            $base = $app['resources']->getPath($app['upload.namespace']);
            if(!is_writable($base)) {
                throw new \RuntimeException("Unable to write to upload destination. Check permissions on $base", 1);
            }
            $container = new Local($base);
            return $container;
        });
        
        $app['upload.namespace'] = 'files';
        
        $app['upload.prefix'] = date('Y-m')."/";
    }
    
    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];

        $ctr->match("/{namespace}", function(Silex\Application $app, Request $request, $namespace = null){

            if($namespace === "") {
                $namespace = $app['upload.namespace'];
            }
            return $this->uploadFile($app, $request, $namespace);
        
        })->assert('namespace', '.*');

        return $ctr;

    }

    public function uploadFile(Silex\Application $app, Request $request, $namespace)
    {
        
        $app['upload.namespace'] = $namespace;
        
        $files = (array)$request->files->get($namespace);
        $filesToProcess = array();
        foreach($files as $file) {
            if($file instanceof UploadedFile) {
                $filesToProcess[] = array(
                    'name'=> $file->getClientOriginalName(),
                    'tmp_name' => $file->getPathName()
                );
            } else {
                $filesToProcess = $file;
            }
        } 

                
        
        
        if(!$filesToProcess) {
            return new JsonResponse(array("status"=>"ERROR","files"=>array()));
        }      
        
                
        $result = $app['upload']->process($filesToProcess);

        if ($result->isValid()) {
            $result->confirm();
            if($result instanceof File) {
                $successfulFiles = array($result->name);
            } elseif($result instanceof Collection) {
                foreach($result as $resultFile) {
                    $successfulFiles[] = $namespace."/".$resultFile['name'];
                }
            }
            return new JsonResponse(array("status"=>"OK","files"=>$successfulFiles));
        } else {
            $result->clear();
            foreach($result->getMessages() as $error) {
                $errors[] = $error->__toString();
            }
            return new JsonResponse(array("status"=>"ERROR", "messages"=>$errors));
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
