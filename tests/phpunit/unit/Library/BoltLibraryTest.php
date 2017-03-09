<?php

namespace Bolt\Tests\Library;

use Bolt\Library;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Library.
 *
 * @group legacy
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BoltLibraryTest extends BoltUnitTest
{
    public function testFormatFilesize()
    {
        $b = 300;
        $fix = Library::formatFilesize($b);
        $this->assertEquals('300 B', $fix);

        $k = 1027;
        $fix = Library::formatFilesize($k);
        $this->assertEquals('1.00 KiB', $fix);

        $m = 1048577;
        $fix = Library::formatFilesize($m);
        $this->assertEquals('1.00 MiB', $fix);
    }

    public function testGetExtension()
    {
        $file = 'picture-of-kittens.jpg';
        $this->assertEquals('jpg', Library::getExtension($file));

        $empty = '/path/to/noext';
        $this->assertEquals('', Library::getExtension($empty));
    }

    public function testSafeFilename()
    {
        $abs = '/etc/passwd';
        $this->assertEquals('etc/passwd', Library::safeFilename($abs));

        // Test urlparams get encoded
        $urlparams = '%2F..%2F..%2Fsecretfile.txt';
        $this->assertEquals('%252F..%252F..%252Fsecretfile.txt', Library::safeFilename($urlparams));
    }
}
