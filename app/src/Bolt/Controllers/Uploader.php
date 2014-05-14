<?php

namespace Bolt\Controllers;

use Silex;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Uploader implements ControllerProviderInterface
{
    public function connect(Silex\Application $app)
    {
        $ctr = $app['controllers_factory'];

        $ctr->post("/", array($this, 'uploadFile'))
            ->before(array($this, 'before'))
            ->bind('uploadFile');

        return $ctr;

    }

    public function uploadFile(Silex\Application $app)
    {
        // Default accepted filetypes are: gif|jpe?g|png|zip|tgz|txt|md|docx?|pdf|xlsx?|pptx?|mp3|ogg|wav|m4a|mp4|m4v|ogv|wmv|avi|webm
        if (is_array($app['config']->get('general/accept_file_types'))) {
            $accepted_ext = implode('|', $app['config']->get('general/accept_file_types'));
        } else {
            $accepted_ext = $app['config']->get('general/accept_file_types');
        }

        $upload_handler = new \UploadHandler(array(
            'upload_dir' => dirname(dirname(dirname(dirname($_SERVER['SCRIPT_FILENAME'])))).'/files/'.date('Y-m')."/",
            'upload_url' => '/files/'.date('Y-m')."/",
            'accept_file_types' => '/\.(' . $accepted_ext . ')$/i'
        ));

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

}
