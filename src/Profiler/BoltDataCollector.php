<?php

namespace Bolt\Profiler;

use Bolt;
use Bolt\Translation\Translator as Trans;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * BoltDataCollector.
 */
class BoltDataCollector extends DataCollector
{
    protected $app;

    public function __construct(Application $app)
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
     * @param Request    $request
     * @param Response   $response
     * @param \Exception $exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'version'       => Bolt\Version::VERSION,
            'payoff'        => 'Sophisticated, lightweight & simple CMS',
            'dashboardlink' => sprintf('<a href="%s">%s</a>', $this->app['url_generator']->generate('dashboard'), 'Dashboard'),
            'branding'      => null,
            'editlink'      => null,
            'edittitle'     => null,
        ];

        if ($this->app['config']->get('general/branding/provided_by/0')) {
            $this->data['branding'] = sprintf(
                '%s %s',
                Trans::__('general.phrase.provided-by-colon'),
                $this->app['config']->get('general/branding/provided_link')
            );
        }

        if (!empty($this->app['editlink'])) {
            $this->data['editlink'] = $this->app['editlink'];
            $this->data['edittitle'] = $this->app['edittitle'];
        }
    }

    /**
     * Getter for version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->data['version'];
    }

    /**
     * Getter for branding.
     *
     * @return string
     */
    public function getBranding()
    {
        return $this->data['branding'];
    }

    /**
     * Getter for payoff.
     *
     * @return string
     */
    public function getPayoff()
    {
        return $this->data['payoff'];
    }

    /**
     * Getter for dashboardlink.
     *
     * @return string
     */
    public function getDashboardlink()
    {
        return $this->data['dashboardlink'];
    }

    /**
     * Getter for editlink.
     *
     * @return string
     */
    public function getEditlink()
    {
        return $this->data['editlink'];
    }

    /**
     * Getter for edittitle.
     *
     * @return string
     */
    public function getEdittitle()
    {
        return $this->data['edittitle'];
    }
}
