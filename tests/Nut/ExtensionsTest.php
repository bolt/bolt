<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\Extensions;
use Composer\Package\CompletePackage;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\TableHelper;

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
        
        $testPackage = new CompletePackage('test','1.0.1','1.0');
        $testPackage->setDescription('An extension');
        $testPackage->setType('bolt-extension');
        
        $runner = $this->getMock("Bolt\Composer\PackageManager", array('showPackage'), array($app));
        $runner->expects($this->any())
            ->method('showPackage')
            ->will($this->returnValue(array( 'test'=>array('package'=>$testPackage) )));
        
        $app['extend.manager'] = $runner;
                
        $command = new Extensions($app);
        $command->setHelperset(new HelperSet(array(new TableHelper)));
        $tester = new CommandTester($command);
        
        
        
        $tester->execute(array());
        $result = $tester->getDisplay();
        $this->assertRegexp('/Name.*Version/', $result);
        $this->assertRegexp('/test.*1.0/', $result);
        

    }
    
 
   
}