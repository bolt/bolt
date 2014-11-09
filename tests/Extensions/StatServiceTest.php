<?php
namespace Bolt\Tests\Extensions;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Extensions\StatService;

/**
 * Class to test src/Extensions/StatService.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class StatServiceTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();
        $stat = $this->getMock(StatService::class, array('recordInstall'), array($app));
        $stat = new StatService($app);
        
        $response = $stat->recordInstall("mytest",'1.0.0');
        $this->assertEquals($app['extend.site'].'stat/install/mytest/1.0.0', $response);
    }

    
   
}

namespace Bolt\Extensions;

function file_get_contents($url) 
{
    return $url;
}
