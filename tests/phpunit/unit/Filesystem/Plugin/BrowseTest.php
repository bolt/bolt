<?php
namespace Bolt\Tests\Filesystem\Plugin;

use Bolt\Filesystem\Plugin;
use Bolt\Tests\BoltUnitTest;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class BrowseTest extends BoltUnitTest
{
    public function testSetup()
    {
        $app = $this->getApp();

        $adapter = new Local(TEST_ROOT);
        $fs = new Filesystem($adapter);

        $fs->addPlugin(new Plugin\Authorized($app));
        $fs->addPlugin(new Plugin\Browse());

        $result = $fs->browse('/');
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
        $adapter = new Local(PHPUNIT_ROOT . '/resources');
        $fs = new Filesystem($adapter);

        $fs->addPlugin(new Plugin\Authorized($app));
        $fs->addPlugin(new Plugin\Browse());

        $result = $fs->browse('');
        $files = $result[0];
        foreach ($files as $file) {
            if ($file['type'] == 'png') {
                $this->assertNotEmpty($file['imagesize']);
            }
        }
    }
}
