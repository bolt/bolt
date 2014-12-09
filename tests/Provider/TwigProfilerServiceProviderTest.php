<?php
namespace Bolt\Tests\Provider;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Provider\TwigProfilerServiceProvider;

/**
 * Class to test src/Provider/TwigProfilerServiceProvider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class TwigProfilerServiceProviderTest extends BoltUnitTest
{


    public function testProvider()
    {
        $app = $this->getApp();
        $app['data_collector.templates'] = array(); 
        $app['data_collectors'] = array();  
        $provider = new TwigProfilerServiceProvider($app);    
        $app->register($provider);
        $this->assertNotEmpty($app['data_collector.templates']);
        $collectors = $app['data_collectors'];
        $bolt = $collectors['twig']->__invoke($app);
        $this->assertInstanceOf('Bolt\DataCollector\TwigDataCollector', $bolt);
        
        $app->boot();
    }
 
   
}