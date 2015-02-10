<?php
namespace Bolt\Tests\Filesystem\Plugin;

use Bolt\Tests\BoltUnitTest;
use Bolt\Filesystem\Plugin;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

class BrowseTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();

        $adapter = new Local(TEST_ROOT);
        $fs = new Filesystem($adapter);

        $plugin = new Plugin\Browse();
        $plugin->setFilesystem($fs);
        $result = $plugin->handle("/", $app);
        $this->assertGreaterThan(0, count($result));
    }

    public function testName()
    {
        $plugin = new Plugin\Browse();
        $this->assertEquals('browse', $plugin->getMethod());
    }

    public function testImageCalculation()
    {
        $app = $this->getApp();
        $adapter = new Local(TEST_ROOT . '/tests/resources');
        $fs = new Filesystem($adapter);

        $plugin = new Plugin\Browse();
        $plugin->setFilesystem($fs);
        $result = $plugin->handle("", $app);
        $files = $result[0];
        foreach ($files as $file) {
            if ($file['type'] == 'png') {
                $this->assertNotEmpty($file['imagesize']);
            }
        }
    }
}
