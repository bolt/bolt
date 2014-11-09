<?php
namespace Bolt\Tests\FilePermissions;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\FilePermissions;

/**
 * Class to test src/FilePermissions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class FilePermissionsTest extends BoltUnitTest
{


    public function testBasicAuth()
    {
        $app = $this->getApp();
        $fp = new FilePermissions($app);
        $test = $app['resources']->getPath('config').'test.yml';
        $this->assertTrue($fp->authorized($test));
        $this->assertFalse($fp->authorized("/path/to/.htaccess"));
    }
    
    public function testAllowedUpload()
    {
        $app = $this->getApp();
        $fp = new FilePermissions($app);
        $hiddenFile = ".bashrc";
        $this->assertFalse($fp->allowedUpload($hiddenFile));
        
        $badExtension = "evil.exe";
        $this->assertFalse($fp->allowedUpload($badExtension));
        
        $okFile = "mycoolimage.jpg";
        $this->assertTrue($fp->allowedUpload($okFile));
    }
    

    
    
 
   
}