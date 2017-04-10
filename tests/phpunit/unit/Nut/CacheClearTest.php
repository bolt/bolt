<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\CacheClear;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/CacheClear.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class CacheClearTest extends BoltUnitTest
{
    public function testSuccessfulClear()
    {
        $app = $this->getApp();
        $this->setService('cache', $this->getMockCache());
        $command = new CacheClear($app);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Cache cleared/', $result);
    }

    public function testWithFailures()
    {
        $app = $this->getApp();
        $this->setService('cache', $this->getMockCache(false));
        $command = new CacheClear($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Failed to clear cache/', $result);
    }

    protected function getMockCache($flushResult = true)
    {
        $cache = parent::getMockCache();
        $cache->expects($this->once())
            ->method('flushAll')
            ->will($this->returnValue($flushResult))
        ;

        return $cache;
    }
}
