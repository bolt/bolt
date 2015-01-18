<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\Extensions;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Console\Helper\Helperset;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Output\NullOutput;

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
            ->will($this->returnValue(new JsonResponse(array(array(
                    'name'=>'test',
                    'version'=>'1.0',
                    'type'=>'bolt-extension',
                    'descrip'=>'An extension'
                )))));
        
        $app['extend.runner'] = $runner;
        
        $command = new Extensions($app);
        $command->setHelperset(new Helperset(array(new TableHelper)));
        $tester = new CommandTester($command);
        
        
        
        $tester->execute(array());
        $result = $tester->getDisplay();
        $this->assertRegexp('/Name.*Version/', $result);
        $this->assertRegexp('/test.*1.0/', $result);
        

    }
    
 
   
}