<?php

namespace Bolt\DataCollector;

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

    /**
     * Collect the date for the Toolbar item.
     *
     * @param Request $request
     * @param Response $response
     * @param \Exception $exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = array(
            'version' => $this->app['bolt_version'],
            'name' => $this->app['bolt_name'],
            'fullversion' => sprintf('%s %s %s', __("Version: "), $this->app['bolt_version'], $this->app['bolt_name']),
            'payoff' => __('Sophisticated, lightweight & simple CMS'),
            'aboutlink' => sprintf("<a href=\"%s\">%s</a>", path('about'), __('About')),
            'branding' => null,
            'editlink' => null,
            'edittitle' => null
        );

        if ($this->app['config']->get('general/branding/provided_by/0')) {
            $this->data['branding'] = sprintf(
                "%s <a href=\"mailto:%s\">%s</a>",
                __("Provided by:"),
                $this->app['config']->get('general/branding/provided_by/0'),
                $this->app['config']->get('general/branding/provided_by/1')
            );
        }

        if (!empty($this->app['editlink'])) {
            $this->data['editlink'] = $this->app['editlink'];
            $this->data['edittitle'] = $this->app['edittitle'];
        }

    }

    /**
     * Getter for version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->data['version'];
    }

    /**
     * Getter for fullversion
     *
     * @return string
     */
    public function getFullVersion()
    {
        return $this->data['fullversion'];
    }

    /**
     * Getter for name
     *
     * @return string
     */
    public function getVersionName()
    {
        return $this->data['name'];
    }


    /**
     * Getter for branding
     *
     * @return string
     */
    public function getBranding()
    {
        return $this->data['branding'];
    }

    /**
     * Getter for payoff
     *
     * @return string
     */
    public function getPayoff()
    {
        return $this->data['payoff'];
    }


    /**
     * Getter for aboutlink
     *
     * @return string
     */
    public function getAboutlink()
    {
        return $this->data['aboutlink'];
    }


    /**
     * Getter for editlink
     *
     * @return string
     */
    public function getEditlink()
    {
        return $this->data['editlink'];
    }


    /**
     * Getter for aboutlink
     *
     * @return string
     */
    public function getEdittitle()
    {
        return $this->data['edittitle'];
    }
}
