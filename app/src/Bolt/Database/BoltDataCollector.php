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

    protected $data;

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
        $this->data = array(
            'version' => $this->version,
            'name' => $this->name
        );

    }

    public function getVersion()
    {
        return $this->data['version'];
    }

    public function getVersionName()
    {
        return $this->data['name'];
    }



}
