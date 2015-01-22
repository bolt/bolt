<?php
namespace Bolt\Tests\Extensions;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Extensions\ExtensionsInfoService;

/**
 * Class to test src/Extensions/ExtensionsInfoService.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class InfoServiceTest extends BoltUnitTest
{

    public function testPackageInfo()
    {
        $app = $this->getApp();
        $service = new ExtensionsInfoService($app['extend.site'], $app['extend.urls']);
        $service->setFormat('raw');
        
        $response = $service->info("mytest",'2.0.0');
        $this->assertEquals($app['extend.site'].'info.json?package=mytest&bolt=2.0.0', $response);
    }
    
    public function testInfoList()
    {
        $app = $this->getApp();
        $service = new ExtensionsInfoService($app['extend.site'], $app['extend.urls']);
        $service->setFormat('raw');
        
        $response = $service->all();
        $this->assertEquals($app['extend.site'].'list.json', $response);
    }

    
   
}

namespace Bolt\Extensions;

function file_get_contents($url) 
{
    return $url;
}
