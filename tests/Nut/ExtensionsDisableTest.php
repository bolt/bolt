<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\ExtensionsDisable;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/ExtensionsDisable.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ExtensionsDisableTest extends BoltUnitTest
{


    public function testRun()
    {
        $app = $this->getApp();
        
        $runner = $this->getMock("Bolt\Composer\CommandRunner", array('uninstall'), array($app));
        $runner->expects($this->any())
            ->method('uninstall')
            ->will($this->returnArgument(0));
        
        $app['extend.runner'] = $runner;
        
        $command = new ExtensionsDisable($app);
        $tester = new CommandTester($command);
        
        
        
        $tester->execute(array('name'=>'test'));
        $result = $tester->getDisplay();
        $this->assertEquals('test', trim($result));
        

    }
    
 
   
}