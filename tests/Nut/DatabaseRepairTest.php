<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\DatabaseRepair;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/DatabaseRepair.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class DatabaseRepairTest extends BoltUnitTest
{


    public function testRun()
    {
        $app = $this->getApp();
        $command = new DatabaseRepair($app);
        $tester = new CommandTester($command);
        
        $tester->execute(array());
        $result = $tester->getDisplay();
        $this->assertEquals("Your database is already up to date.", trim($result));
        
        // Now introduce some changes
        $app['config']->set('contenttypes/newcontent', array('fields'=>array('title'=>array('type'=>'text'))));
        
        $tester->execute(array());
        $result = $tester->getDisplay();
        $this->assertRegExp("/Created table `bolt_newcontent`/", $result);

    }
    
 
   
}