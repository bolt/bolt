<?php

namespace Bolt\Tests\Asset;

use Bolt\Asset\File\JavaScript;
use Bolt\Tests\Fixtures\AssetSortTrait\AssetSort;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Asset\AssetSortTrait
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AssetSortTraitTest extends TestCase
{
    const FIRST = 0;
    const LAST = 1;

    const PRIORITY_LOW = 2;
    const PRIORITY_HIGH = 1;

    public function providerSort()
    {
        return [
            [self::PRIORITY_LOW, self::PRIORITY_HIGH, self::LAST, self::FIRST],
            [self::PRIORITY_HIGH, self::PRIORITY_LOW, self::FIRST, self::LAST],
        ];
    }

    /**
     * @dataProvider providerSort
     */
    public function testSort($koalaPriority, $dropBearPriority, $koalaIndex, $dropBearIndex)
    {
        $assets = [
            JavaScript::create()->setPath('koala.js')->setPriority($koalaPriority),
            JavaScript::create()->setPath('dropbear.js')->setPriority($dropBearPriority),
        ];
        $sort = new AssetSort();
        $result = $sort->doSort($assets);

        self::assertSame('koala.js', $result[$koalaIndex]->getPath());
        self::assertSame('dropbear.js', $result[$dropBearIndex]->getPath());
    }
}
