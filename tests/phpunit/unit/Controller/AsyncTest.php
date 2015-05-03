<?php
namespace Bolt\Tests\Controller;

use Bolt\Response\BoltResponse;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Controller\ControllerUnitTest;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation of src/Controller/Backend/Async.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 **/
class AsyncTest extends ControllerUnitTest
{
    public function testDashboardnews()
    {
        $this->setRequest(Request::create('/async/dashboardnews'));
        $response = $this->controller()->dashboardnews($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('components/panel-news.twig', $response->getTemplateName());
    }

    public function testLatestactivity()
    {
        $this->setRequest(Request::create('/async/latestactivity'));
        $response = $this->controller()->latestactivity($this->getRequest());

        $this->assertTrue($response instanceof BoltResponse);
        $this->assertSame('components/panel-activity.twig', $response->getTemplateName());
    }

    /**
     * @return \Bolt\Controller\Async
     */
    protected function controller()
    {
        return $this->getService('controller.async');
    }
}
