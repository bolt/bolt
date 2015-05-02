<?php

namespace Bolt\Nut;

use Bolt\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpFoundation\Request;

/**
 * Nut building block
 */
abstract class BaseCommand extends Command
{
    /** @var Bolt\Application */
    protected $app;

    /**
     * @param \Bolt\Application $app
     * @param Request           $request Reserved for tests
     */
    public function __construct(Application $app, Request $request = null)
    {
        parent::__construct();
        $this->app = $app;

        /*
         * We need this to exist for $app['logger.system'] and $app['storage']
         * calls in Nut to avoid the RuntimeException:
         *   Accessed request service outside of request scope. Try moving that
         *   call to a before handler or controller
         */
        $app['request'] = $request ? : Request::createFromGlobals();
    }

    /**
     * Log a Nut execution if auditing is on
     *
     * @param string $source  __CLASS__ of caller
     * @param string $message Message to log
     */
    protected function auditLog($source, $message)
    {
        if ($this->app['config']->get('general/auditlog/enabled', true)) {
            $this->app['logger.system']->info($message, array('event' => 'nut', 'source' => $source));
        }
    }
}
