<?php
namespace Bolt\Tests\Profiler;

use Bolt\Helpers\Html;
use Bolt\Profiler\BoltDataCollector;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test correct operation and locations of src/DataCollector/BoltDataCollector.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BoltDataCollectorTest extends BoltUnitTest
{
    public function testBasicData()
    {
        $app = $this->getApp();

        $request = Request::create(
            '/',
            'GET',
            [],
            [],
            [],
            []
        );

        $response = $app->handle($request);

        $data = new BoltDataCollector($app);
        $data->collect($request, $response);
        $this->assertNotEmpty($data->getName());
        $this->assertNotEmpty($data->getVersion());
        $this->assertNotEmpty($data->getPayoff());
        $this->assertNotEmpty($data->getDashboardLink());
    }

    public function testBrandingData()
    {
        $app = $this->getApp();
        $app['config']->set('general/branding/provided_by/0', 'testperson');
        $app['config']->set('general/branding/provided_by/1', 'testemail');
        $app['config']->set('general/branding/provided_link', Html::providerLink(['testperson', 'testemail']));
        $request = Request::create('/', 'GET');
        $response = $app->handle($request);

        $data = new BoltDataCollector($app);
        $data->collect($request, $response);

        $this->assertRegExp('/testperson/', $data->getBranding());
        $this->assertRegExp('/testemail/', $data->getBranding());
    }
}
