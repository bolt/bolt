<?php
namespace Bolt\Tests\Extensions;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\BaseExtension;

/**
 * Class to test src/BaseExtension.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class BaseExtensionTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();
        $ext = $this->getMock('Bolt\BaseExtension', null ,array($app));
        $this->assertNotEmpty($ext->getBasePath());
        $this->assertNotEmpty($ext->getBaseUrl());
        $this->assertEquals('mockobject', $ext->getMachineName());
    }
    
    public function testComposerLoading()
    {
        $app = $this->makeApp();
        $app['resources']->setPath('extensions', __DIR__."/resources");
        $app->initialize();
        $this->assertTrue($app['extensions']->isEnabled('testlocal'));
        $config = $app['extensions.testlocal']->getExtensionConfig();
        $this->assertNotEmpty($config);
    }

    
   
}

