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
        
        $runner = $this->getMock("Bolt\Composer\CommandRunner", array('install'), array($app));
        $runner->expects($this->any())
            ->method('install')
            ->will($this->returnCallback(function($package, $version){
                return $package.":".$version;
            }));
        
        $app['extend.runner'] = $runner;
        
        $command = new ExtensionsEnable($app);
        $tester = new CommandTester($command);
        
        
        
        $tester->execute(array('name'=>'test','version'=>'1.0'));
        $result = $tester->getDisplay();
        $this->assertRegexp('/test\:1\.0/', trim($result));
        

    }
    
 
   
}