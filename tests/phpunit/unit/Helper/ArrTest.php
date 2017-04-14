<?php

namespace Bolt\Tests\Helper;

use Bolt\Helpers\Arr;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Helper/Arr.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class ArrTest extends BoltUnitTest
{
    public function testLegacyMakeValuePairs()
    {
        $test = [
            ['id' => 1, 'value' => 1],
            ['id' => 2, 'value' => 2],
        ];
        $this->assertEquals([1 => 1, 2 => 2], Arr::makeValuePairs($test, 'id', 'value'));
        $this->assertEquals([0 => 1, 1 => 2], Arr::makeValuePairs($test, '', 'value'));
    }

    public function testLegacyMergeRecursiveDistinct()
    {
        $arr1 = ['key' => 'orig value'];
        $arr2 = ['key' => 'new value'];
        $this->assertEquals(['key' => 'new value'], Arr::mergeRecursiveDistinct($arr1, $arr2));

        // Needs an exclusion for accept_file_types
        $arr1 = ['accept_file_types' => ['jpg']];
        $arr2 = ['accept_file_types' => ['jpg', 'png']];
        $actual = Arr::mergeRecursiveDistinct($arr1, $arr2);
        $this->assertEquals(['accept_file_types' => ['jpg', 'png']], $actual);

        // Test Recursion
        $arr1 = ['key' => ['test' => 'new value']];
        $arr2 = ['key' => ['test' => 'nested new value']];

        $this->assertEquals(
            ['key' => ['test' => 'nested new value']],
            Arr::mergeRecursiveDistinct($arr1, $arr2)
        );

        // This is why this method is deprecated:
        $arr1 = ['key' => ['foo', 'bar']];
        $arr2 = ['key' => ['baz']];
        $actual = Arr::mergeRecursiveDistinct($arr1, $arr2);
        $this->assertEquals(['key' => ['baz', 'bar']], $actual);
    }

    /**
     * @dataProvider deepDiffProvider
     */
    public function testDeepDiff($a, $b, $expected)
    {
        $actual = Arr::deepDiff($a, $b);
        $this->assertEquals($expected, $actual);
    }

    public function deepDiffProvider()
    {
        return [
            'No features at all' => [
                [],
                [],
                [],
            ],
            'Feature one stays the same' => [
                ['feature one' => 'old feature'],
                ['feature one' => 'old feature'],
                [],
            ],
            'Feature one gets removed' => [
                ['feature one' => 'old feature'],
                [],
                [
                    ['feature one', 'old feature', null],
                ],
            ],
            'Feature one gets added' => [
                [],
                ['feature one' => 'new feature'],
                [
                    ['feature one', null, 'new feature'],
                ],
            ],
            'Feature one gets updated' => [
                ['feature one' => 'old feature one'],
                ['feature one' => 'new feature one'],
                [
                    ['feature one', 'old feature one', 'new feature one'],
                ],
            ],
            'Multi feature one & two get updated' => [
                [
                    'feature one' => 'old feature one',
                    'feature two' => 'old feature two',
                ],
                [
                    'feature one' => 'new feature one',
                    'feature two' => 'new feature two',
                ],
                [
                    ['feature one', 'old feature one', 'new feature one'],
                    ['feature two', 'old feature two', 'new feature two'],
                ],
            ],
        ];
    }
}
