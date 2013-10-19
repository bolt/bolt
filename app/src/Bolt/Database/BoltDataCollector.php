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
    protected $app;

    protected $data;

    public function __construct(\Bolt\Application $app)
    {
        $this->app = $app;
    }


    public function getName()
    {
        return 'bolt';
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'version' => $this->app['bolt_version'],
            'name' => $this->app['bolt_name'],
            'templates' => hackislyParseRegexTemplates($this->app['twig.loader']),
            'templatechosen' => $this->app['log']->getValue('templatechosen'),
            'templateerror' => $this->app['log']->getValue('templateerror')
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

    public function getTemplates()
    {
        return $this->data['templates'];
    }

    public function getChosenTemplate()
    {
        return $this->data['templatechosen'];
    }


    public function getTemplateError()
    {
        return $this->data['templateerror'];
    }



}
