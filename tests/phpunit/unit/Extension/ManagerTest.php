<?php

namespace Bolt\Tests\Extension;

use Bolt\Config;
use Bolt\Extension\Manager;
use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Logger\FlashLogger;
use PHPUnit\Framework\TestCase;
use Silex\Application;

/**
 * @covers \Bolt\Extension\Manager
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ManagerTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Can not re-register extensions.
     */
    public function testRegister()
    {
        $filesystem = new Filesystem(new Local(__DIR__));
        $flash = new FlashLogger();
        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $app = new Application();
        $manager = new Manager($filesystem, $filesystem, $flash, $config);
        $manager->register($app);

        $manager->register($app);
    }
}
