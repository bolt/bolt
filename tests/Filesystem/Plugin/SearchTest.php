<?php
namespace Bolt\Tests\Filesystem\Plugin;

use Bolt\Tests\BoltUnitTest;
use Bolt\Filesystem\Plugin;
use League\Flysystem\Filesystem;
use League\Flysystem\Adapter\Local;

class SearchTest extends BoltUnitTest
{

    public function testSetup()
    {
        $app = $this->getApp();

        $adapter = new Local(TEST_ROOT . '/tests/resources');
        $fs = new Filesystem($adapter);

        $plugin = new Plugin\Search();
        $plugin->setFilesystem($fs);
        $result = $plugin->handle("*");
        $this->assertGreaterThan(0, count($result));
    }

    public function testName()
    {
        $plugin = new Plugin\Search();
        $this->assertEquals('search', $plugin->getMethod());
    }
}
