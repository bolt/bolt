<?php
namespace Bolt\Tests\Configuration;

use Bolt\Application;
use Bolt\Configuration\Composer;
use Bolt\Configuration\ComposerChecks;
use Bolt\Configuration\LowlevelException;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ComposerConfigurationTest extends \PHPUnit_Framework_TestCase
{

    public function setup()
    {

    }

    public function tearDown()
    {
    }

    
    public function testComposerCustomConfig()
    {
        $config = new Composer(TEST_ROOT);
        $app = new Application(array('resources' => $config));
        $this->assertEquals('/bolt-public/', $config->getUrl('app'));
    }
    
    public function testComposerVerification()
    {
        $config = new Composer(TEST_ROOT);
        $verifier = new ComposerChecks($config);
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
        // Check we get an exception if the directory isn't writable
        nativeFunctionExpects('is_dir', array(true, true));
        nativeFunctionExpects('is_writable', false);

        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/Bolt - Fatal Error/");
        $verifier->checkDir($fakeLocation);

    }
    

   
}
