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

    public function replaceRecursiveProvider()
    {
        return [
            'scalar replaces scalar (no duh)'         => [
                ['a' => ['b' => 'foo']],
                ['a' => ['b' => 'bar']],
                ['a' => ['b' => 'bar']],
            ],
            'second adds to first (no duh)'           => [
                ['a' => ['b' => 'foo']],
                ['a' => ['c' => 'bar']],
                ['a' => ['b' => 'foo', 'c' => 'bar']],
            ],
            'list replaces list completely'           => [
                ['a' => ['foo', 'bar']],
                ['a' => ['baz']],
                ['a' => ['baz']],
            ],
            'null replaces scalar'                    => [
                ['a' => ['b' => 'foo']],
                ['a' => ['b' => null]],
                ['a' => ['b' => null]],
            ],
            'null ignores arrays (both types)'        => [
                ['a' => ['b' => 'foo']],
                ['a' => null],
                ['a' => ['b' => 'foo']],
            ],
            'empty list replaces arrays (both types)' => [
                ['a' => ['foo', 'bar']],
                ['a' => []],
                ['a' => []],
            ],
            'scalar replaces arrays (both types)'     => [
                ['a' => ['foo', 'bar']],
                ['a' => 'derp'],
                ['a' => 'derp'],
            ],
        ];
    }

    /**
     * @dataProvider replaceRecursiveProvider
     *
     * @param array $array1
     * @param array $array2
     * @param array $result
     */
    public function testReplaceRecursive($array1, $array2, $result)
    {
        $this->assertEquals($result, Arr::replaceRecursive($array1, $array2));
    }

    public function isIndexedProvider()
    {
        return [
            'key value pairs'                  => [['key' => 'value'], false],
            'empty array'                      => [[], true],
            'list'                             => [['foo', 'bar'], true],
            'zero-indexed numeric int keys'    => [[0 => 'foo', 1 => 'bar'], true],
            'zero-indexed numeric string keys' => [['0' => 'foo', '1' => 'bar'], true],
            'non-zero-indexed keys'            => [[1 => 'foo', 2 => 'bar'], false],
            'non-sequential keys'              => [[0 => 'foo', 2 => 'bar'], false],
        ];
    }

    /**
     * @dataProvider isIndexedProvider
     *
     * @param array $array
     * @param bool  $indexed
     */
    public function testIsIndexedAndAssociative($array, $indexed)
    {
        $this->assertEquals($indexed, Arr::isIndexed($array));
        $this->assertEquals(!$indexed, Arr::isAssociative($array));
    }

    public function testNonArraysAreNotIndexedOrAssociative()
    {
        $this->assertFalse(Arr::isIndexed('derp'));
        $this->assertFalse(Arr::isAssociative('derp'));
    }

    public function testColumn()
    {
        $data = [
            new TestColumn('foo', 'bar'),
            new TestColumn('hello', 'world'),
            ['id' => '5', 'value' => 'asdf'],
        ];

        $result = Arr::column($data, 'id');
        $this->assertEquals(['foo', 'hello', '5'], $result);

        $result = Arr::column($data, 'value', 'id');
        $expected = [
            'foo'   => 'bar',
            'hello' => 'world',
            '5'     => 'asdf',
        ];
        $this->assertEquals($expected, $result);
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

class TestColumn
{
    public $id;
    private $value;

    public function __construct($id, $value)
    {
        $this->id = $id;
        $this->value = $value;
    }

    public function __isset($name)
    {
        return $name === 'value';
    }

    public function __get($name)
    {
        return $this->value;
    }
}
