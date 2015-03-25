<?php

namespace Bolt\Nut;

use Bolt\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Request;

abstract class BaseCommand extends Command
{
    protected $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;

        /*
         * We need this to exist for $app['logger.system'] and $app['storage']
         * calls in Nut to avoid the RuntimeException:
         *   Accessed request service outside of request scope. Try moving that
         *   call to a before handler or controller
         */
        $app['request'] = Request::createFromGlobals();
    }
}
