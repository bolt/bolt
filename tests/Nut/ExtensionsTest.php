<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\Extensions;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/Extensions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ExtensionsTest extends BoltUnitTest
{


    public function testRun()
    {
        $app = $this->getApp();
        
        $runner = $this->getMock("Bolt\Composer\CommandRunner", array('installed'), array($app));
        $runner->expects($this->any())
            ->method('installed')
            ->will($this->returnValue('{}'));
        
        $app['extend.runner'] = $runer;
        
        $command = new Extensions($app);
        $tester = new CommandTester($command);
        
        
        
        $tester->execute(array());
        $result = $tester->getDisplay();
        

    }
    
 
   
}