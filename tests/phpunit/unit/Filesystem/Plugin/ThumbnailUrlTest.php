<?php

namespace Bolt\Tests\Filesystem\Plugin;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Manager;
use Bolt\Filesystem\Plugin;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ThumbnailUrlTest extends BoltUnitTest
{
    public function testHandle()
    {
        $adapter = new Local(PHPUNIT_ROOT . '/resources');
        $fs = new Filesystem($adapter);

        $manager = new Manager([]);
        $manager->mountFilesystem('files', $fs);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('thumb', [
                'width'  => 200,
                'height' => 200,
                'action' => 'c',
                'file'   => 'generic-logo.png',
            ])
            ->willReturn('/thumbs/200x200c/generic-logo.png')
        ;

        $manager->addPlugin(new Plugin\ThumbnailUrl($urlGenerator));

        $result = $fs->thumb('generic-logo.png', 200, 200, 'crop');
        $this->assertEquals('/thumbs/200x200c/generic-logo.png', $result);
    }

    public function testName()
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $plugin = new Plugin\ThumbnailUrl($urlGenerator);

        $this->assertEquals('thumb', $plugin->getMethod());
    }
}
