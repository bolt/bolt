<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\Info;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/Info.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class InfoTest extends BoltUnitTest
{


    public function testRun()
    {
        $app = $this->getApp();        
        $command = new Info($app);
        $tester = new CommandTester($command);
        
        
        
        $tester->execute(array());
        $result = $tester->getDisplay();
        $this->assertRegExp("/PHP Version/", $result);
        

    }
    
 
   
}