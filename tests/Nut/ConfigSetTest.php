<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\ConfigSet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/ConfigSet.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ConfigSetTest extends BoltUnitTest
{


    public function testSet()
    {
        $app = $this->getApp();
        $command = new ConfigSet($app);
        $tester = new CommandTester($command);
        
        // Test successful update
        $tester->execute(array('key'=>'sitename', 'value'=>"my test", '--file'=>__DIR__.'/resources/config.yml'));
        $this->assertRegexp("/New value for sitename: my test was successful/", $tester->getDisplay());        
        
        // Test non-existent fails
        $tester->execute(array('key'=>'nonexistent', 'value'=>"test", '--file'=>__DIR__.'/resources/config.yml'));
        $this->assertEquals("nonexistent not found, or file not writable.\n", $tester->getDisplay());

    }
    
    public function testDefaultFile()
    {
        $app = $this->getApp();
        $command = new ConfigSet($app);
        $tester = new CommandTester($command);
        $app['resources']->setPath('config', __DIR__.'/resources');
        $tester->execute(array('key'=>'nonexistent', 'value'=>"test"));
        $this->assertEquals("nonexistent not found, or file not writable.\n", $tester->getDisplay());
    }
    
    static public function setUpBeforeClass()
    {
        $distname = realpath(__DIR__ . '/../../app/config/config.yml.dist');
        copy($distname, __DIR__.'/resources/config.yml');
    }
    
    static public function tearDownAfterClass()
    {
        unlink(__DIR__.'/resources/config.yml');
    }
    
    
    
 
   
}