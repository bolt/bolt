<?php

namespace Bolt\Database;

use Doctrine\DBAL\Logging\DebugStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * BoltDataCollector.
 *
 */
class BoltDataCollector extends DataCollector
{
    protected $version;
    protected $name;

    public function __construct(\Bolt\Application $app)
    {
        $this->version = $app['bolt_version'];
        $this->name = $app['bolt_name'];
    }


    public function getName()
    {
        return 'bolt';
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {

    }

    public function getVersion()
    {
        // @TODO: Figure out why $this->version does not work. In __construct it's set correctly..
        global $app;

        return $app['bolt_version'];
    }

    public function getVersionName()
    {
        // @TODO: Figure out why $this->name does not work. In __construct it's set correctly..
        global $app;

        return $app['bolt_name'];
    }



}
