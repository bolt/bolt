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
            'fullversion' => sprintf('%s %s %s', __("Version: "), $this->app['bolt_version'], $this->app['bolt_name']),
            'templates' => hackislyParseRegexTemplates($this->app['twig.loader']),
            'templatechosen' => $this->app['log']->getValue('templatechosen'),
            'templateerror' => $this->app['log']->getValue('templateerror'),
            'payoff' => __('Sophisticated, lightweight & simple CMS'),
            'aboutlink' => sprintf("<a href=\"%s\">%s</a>", path('about'), __('About') )
        );

        if ($this->app['config']->get('general/branding/provided_by/0')) {
            $this->data['branding'] = sprintf(
                "%s <a href=\"mailto:%s\">%s</a>",
                __("Provided by:"),
                $this->app['config']->get('general/branding/provided_by/0'),
                $this->app['config']->get('general/branding/provided_by/1')
            );
        }


    }

    public function getVersion()
    {
        return $this->data['version'];
    }

    public function getFullVersion()
    {
        return $this->data['fullversion'];
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

    public function getBranding()
    {
        return $this->data['branding'];
    }

    public function getPayoff()
    {
        return $this->data['payoff'];
    }


    public function getAboutlink()
    {
        return $this->data['aboutlink'];
    }



}
