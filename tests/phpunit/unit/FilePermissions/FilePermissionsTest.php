<?php
namespace Bolt\Tests\FilePermissions;

use Bolt\Filesystem\FilePermissions;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/FilePermissions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FilePermissionsTest extends BoltUnitTest
{
    public function testBasicAuth()
    {
        $app = $this->getApp();
        $fp = new FilePermissions($app['config']);
        $this->assertTrue($fp->authorized('config', 'test.yml'));
        $this->assertFalse($fp->authorized('cache', ''));
        $this->assertFalse($fp->authorized('something', '/path/to/.htaccess'));
    }

    public function testAllowedUpload()
    {
        $app = $this->getApp();
        $fp = new FilePermissions($app['config']);
        $hiddenFile = '.bashrc';
        $this->assertFalse($fp->allowedUpload($hiddenFile));

        $badExtension = 'evil.exe';
        $this->assertFalse($fp->allowedUpload($badExtension));

        $okFile = 'mycoolimage.jpg';
        $this->assertTrue($fp->allowedUpload($okFile));
    }
}
