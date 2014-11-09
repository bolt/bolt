<?php
namespace Bolt\Tests\Composer;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Composer\BootstrapGenerator;

/**
 * Class to test src/Composer/BootstrapGenerator.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class BootstrapGeneratorTest extends BoltUnitTest
{
    public $workspace;
    
    public function setup()
    {
        $this->workspace = TEST_ROOT.'/tests/resources';
        chdir($this->workspace);
        
    }
    
    public function tearDown()
    {
        if (is_dir($this->workspace.'/public3')) {
            $this->rmdir($this->workspace.'/public3');
            rmdir($this->workspace.'/public3');
        }
        @unlink($this->workspace.'/index.php');
    }

    public function testConstruct()
    {
        $boot = new BootstrapGenerator(true, 'public2');
        $this->assertEquals('public2', $boot->webname);
        $this->assertTrue($boot->webroot);
    }
    
    public function testGenerate()
    {
        $boot = new BootstrapGenerator(true, 'public3');
        $code = $boot->generate();
        $this->assertRegExp('#setPath\("web", "public3"\)#', $code);
        $this->assertRegExp('#setPath\("files", "public3/files"\)#', $code);
        $this->assertRegExp('#setPath\("themebase", "public3/theme"\)#', $code);
    }
    
    public function testWrite()
    {
        
        $boot = new BootstrapGenerator(true, 'public3');
        $code = $boot->generate();
        $location = $boot->create();
        $this->assertEquals($code, file_get_contents($location));
        
    }
    
    public function testWriteToNonWebroot()
    {
        $boot = new BootstrapGenerator();
        $code = $boot->generate();
        $location = $boot->create();
        $this->assertEquals($code, file_get_contents($location));
    }
    
    
    
    
    
 
   
}