<?php
namespace Bolt\Tests\Filesystem\Plugin;

use Bolt\Tests\BoltUnitTest;
use Bolt\Filesystem\Plugin;
use Bolt\Filesystem\Manager;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

class ThumbnailUrlTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();

        $adapter = new Local(TEST_ROOT . '/tests/resources');
        $fs = new Filesystem($adapter);

        $manager = new Manager($app);
        $manager->mountFilesystem('files', $fs);
        $manager->addPlugin(new Plugin\ThumbnailUrl($app));

        $result = $fs->thumb('generic-logo.png', 200, 200, 'crop');
        $this->assertEquals('/thumbs/200x200c/generic-logo.png', $result);
    }

    public function testName()
    {
        $app = $this->getApp();
        $plugin = new Plugin\ThumbnailUrl($app);
        $this->assertEquals('thumb', $plugin->getMethod());
    }
}
