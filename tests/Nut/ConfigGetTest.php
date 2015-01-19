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
    
    public function setUp()
    {
        @mkdir(__DIR__.'/resources/', 0777, true);
        @mkdir(__DIR__.'/../../app/cache/', 0777, true);
        $distname = realpath(__DIR__ . '/../../app/config/config.yml.dist');
        @copy($distname, __DIR__.'/resources/config.yml');
    }
    
    public function tearDown()
    {
        @unlink(__DIR__.'/resources/config.yml');
        @unlink(__DIR__.'/../../app/cache/');
    }
    
 
   
}