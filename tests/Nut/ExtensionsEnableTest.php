<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\ExtensionsEnable;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/ExtensionsEnable.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ExtensionsEnableTest extends BoltUnitTest
{


    public function testRun()
    {
        $app = $this->getApp();
        
        $runner = $this->getMock("Bolt\Composer\PackageManager", array('requirePackage'), array($app));
        $runner->expects($this->any())
            ->method('requirePackage')
            ->will($this->returnValue(0));
        
        $app['extend.manager'] = $runner;
        
        $command = new ExtensionsEnable($app);
        $tester = new CommandTester($command);
        
        
        
        $tester->execute(array('name'=>'test','version'=>'1.0'));
        $result = $tester->getDisplay();
        $this->assertRegexp('/[Done]/', trim($result));
        

    }
    
 
   
}