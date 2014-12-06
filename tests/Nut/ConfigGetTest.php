<?php
namespace Bolt\Tests\Nut;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Nut\ConfigGet;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/ConfigGet.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ConfigGetTest extends BoltUnitTest
{


    public function testGet()
    {
        $app = $this->getApp();
        $command = new ConfigGet($app);
        $tester = new CommandTester($command);
        $tester->execute(array('key'=>'sitename', '--file'=>__DIR__.'/resources/config.yml'));
        $this->assertEquals("sitename: A sample site\n", $tester->getDisplay());
        
        // test invalid
        $tester = new CommandTester($command);
        $tester->execute(array('key'=>'nonexistent','--file'=>__DIR__.'/resources/config.yml'));
        $this->assertEquals("nonexistent not found.\n", $tester->getDisplay());
        
    }
    
    public function testDefaultFile()
    {
        $app = $this->getApp();
        $command = new ConfigGet($app);
        $tester = new CommandTester($command);
        $app['resources']->setPath('config', __DIR__.'/resources');
        $tester->execute(array('key'=>'sitename'));
        $this->assertEquals("sitename: A sample site\n", $tester->getDisplay());
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