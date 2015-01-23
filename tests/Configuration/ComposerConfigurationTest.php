<?php
namespace Bolt\Tests\Configuration;

use Bolt\Application;
use Bolt\Configuration\Composer;
use Bolt\Configuration\ComposerChecks;
use Bolt\Configuration\LowlevelException;
use Bolt\Configuration\ResourceManager;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *  
 * @runTestsInSeparateProcesses
 *
 */
class ComposerConfigurationTest extends \PHPUnit_Framework_TestCase
{

    public function setup()
    {
        $this->php = \PHPUnit_Extension_FunctionMocker::start($this, 'Bolt\Configuration')
            ->mockFunction('is_writable')
            ->mockFunction('is_dir')
            ->getMock();
    }

    public function tearDown()
    {
        \PHPUnit_Extension_FunctionMocker::tearDown();
    }

    
    public function testComposerCustomConfig()
    {
        $config = new Composer(TEST_ROOT);
        $this->assertEquals('/bolt-public/', $config->getUrl('app'));
    }
    
    public function testComposerVerification()
    {
        $config = new Composer(TEST_ROOT);
        $verifier = new ComposerChecks($config);
        
        // Return true for all theses checks
        $this->php
            ->expects($this->any())
            ->method('is_dir')
            ->will($this->returnValue(true));
            
        $this->php
            ->expects($this->any())
            ->method('is_writable')
            ->will($this->returnValue(true));
        
        $config->setVerifier($verifier);
        $config->verify(); 
    }
    
    public function testCheckSummary()
    {
        $config = new Composer(TEST_ROOT);
        $verifier = new ComposerChecks($config);
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $verifier->checkDir('/non/existent/path');
    }
    
    public function testCheckSummaryReportsError()
    {
        $config = new Composer(TEST_ROOT);
        $config->setPath('database', '/path/to/nowhere');
        $verifier = new ComposerChecks($config);
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $verifier->checkDir('/path/to/nowhere');

    }
    
    public function testCheckDir()
    {
        $fakeLocation = "/path/to/nowhere";
        $config = new Composer(TEST_ROOT);
        $verifier = new ComposerChecks($config);
        
        $app['resources'] = $config;
        ResourceManager::$theApp = $app;

        // Check we get an exception if the directory isn't writable
        $this->php
            ->expects($this->at(0))
            ->method('is_dir')
            ->will($this->returnValue(true));
        
        $this->php
            ->expects($this->at(1))
            ->method('is_dir')
            ->will($this->returnValue(true));
            
        $this->php
            ->expects($this->any())
            ->method('is_writable')
            ->will($this->returnValue(false));    
        

        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $verifier->checkDir($fakeLocation);

    }
    

   
}
