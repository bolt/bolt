<?php

namespace Bolt\Tests\Asset;

use Bolt\Asset\BoltVersionStrategy;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\FileInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Asset\BoltVersionStrategy
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BoltVersionStrategyTest extends TestCase
{
    public function testGetVersion()
    {
        $file = $this->getMockBuilder(FileInterface::class)->getMock();
        $file
            ->expects($this->atLeastOnce())
            ->method('getFullPath')
            ->willReturn('koala.css')
        ;
        $file
            ->expects($this->atLeastOnce())
            ->method('getTimestamp')
            ->willReturn(42)
        ;
        $directory = $this->getMockBuilder(DirectoryInterface::class)->getMock();
        $directory
            ->expects($this->atLeastOnce())
            ->method('getFile')
            ->with('koala.css')
            ->willReturn($file)
        ;

        $strategy = new BoltVersionStrategy($directory, 'pepper');

        self::assertSame('9e30a60095', $strategy->getVersion('koala.css'));
    }

    public function testGetVersionException()
    {
        $file = $this->getMockBuilder(FileInterface::class)->getMock();
        $file
            ->expects($this->any())
            ->method('getFullPath')
            ->willThrowException(new IOException(''))
        ;
        $file
            ->expects($this->any())
            ->method('getTimestamp')
            ->willThrowException(new IOException(''))
        ;
        $directory = $this->getMockBuilder(DirectoryInterface::class)->getMock();
        $directory
            ->expects($this->atLeastOnce())
            ->method('getFile')
            ->with('koala.css')
            ->willReturn($file)
        ;

        $strategy = new BoltVersionStrategy($directory, 'pepper');

        self::assertSame('', $strategy->getVersion('koala.css'));
    }

    public function testApplyVersion()
    {
        $file = $this->getMockBuilder(FileInterface::class)->getMock();
        $file
            ->expects($this->atLeastOnce())
            ->method('getFullPath')
            ->willReturn('koala.css')
        ;
        $file
            ->expects($this->atLeastOnce())
            ->method('getTimestamp')
            ->willReturn(42)
        ;
        $directory = $this->getMockBuilder(DirectoryInterface::class)->getMock();
        $directory
            ->expects($this->atLeastOnce())
            ->method('getFile')
            ->with('koala.css')
            ->willReturn($file)
        ;

        $strategy = new BoltVersionStrategy($directory, 'pepper');

        self::assertSame('koala.css?9e30a60095', $strategy->applyVersion('koala.css'));
    }

    public function testApplyVersionException()
    {
        $file = $this->getMockBuilder(FileInterface::class)->getMock();
        $file
            ->expects($this->any())
            ->method('getFullPath')
            ->willThrowException(new IOException(''))
        ;
        $file
            ->expects($this->any())
            ->method('getTimestamp')
            ->willThrowException(new IOException(''))
        ;
        $directory = $this->getMockBuilder(DirectoryInterface::class)->getMock();
        $directory
            ->expects($this->atLeastOnce())
            ->method('getFile')
            ->with('koala.css')
            ->willReturn($file)
        ;

        $strategy = new BoltVersionStrategy($directory, 'pepper');

        self::assertSame('koala.css', $strategy->applyVersion('koala.css'));
    }
}
