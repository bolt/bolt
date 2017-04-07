<?php

namespace Bolt\Tests\Filesystem;

use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\FilePermissions;
use Bolt\Tests\BoltUnitTest;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

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

        if (ini_set('file_uploads', '0') !== false) {
            try {
                $fp->allowedUpload($okFile);
            } catch (IOException $e) {
                $this->assertEquals($e->getMessage(), 'File uploads are not allowed, check the file_uploads ini directive.');
            }
            ini_set('file_uploads', '1');
        }

        $this->assertTrue($fp->allowedUpload($okFile));
    }

    public function providerFormatFilesize()
    {
        return [
            [1, '1 B'],
            [300, '300 B'],
            [1027, '1.00 KiB'],
            [2345, '2.29 KiB'],
            [1048577, '1.00 MiB'],
            [2293867, '2.19 MiB'],
        ];
    }

    /**
     * @dataProvider providerFormatFilesize
     */
    public function testFormatFilesize($size, $expected)
    {
        /** @var FilePermissions|MockObject $filePermissions */
        $filePermissions = $this->getMockBuilder(FilePermissions::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMaxUploadSize'])
            ->getMock()
        ;

        $filePermissions
            ->expects($this->once())
            ->method('getMaxUploadSize')
            ->willReturn($size)
        ;
        $fix = $filePermissions->getMaxUploadSizeNice();
        $this->assertEquals($expected, $fix);
    }
}
