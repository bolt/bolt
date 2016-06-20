<?php

namespace Bolt\Tests\Filesystem\Plugin;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Manager;
use Bolt\Filesystem\Plugin;
use Bolt\Tests\BoltUnitTest;

class AuthorizedTest extends BoltUnitTest
{
    public function testSetup()
    {
        $app = $this->getApp();

        $adapter = new Local(PHPUNIT_ROOT . '/resources');
        $fs1 = new Filesystem($adapter);
        $fs2 = new Filesystem($adapter);
        $fs3 = new Filesystem($adapter);

        $manager = new Manager([]);
        $manager->mountFilesystem('files', $fs1);
        $manager->mountFilesystem('cache', $fs2);
        $manager->mountFilesystem('something', $fs3);
        $manager->addPlugin(new Plugin\Authorized($app));

        $this->assertTrue($fs1->authorized(''));
        $this->assertFalse($fs2->authorized(''));
        $this->assertFalse($fs3->authorized(''));
    }
}
