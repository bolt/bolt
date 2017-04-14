<?php

namespace Bolt\Tests\Collection;

use ArrayObject;
use Bolt\Collection\Bag;
use Bolt\Collection\ImmutableBag;
use PHPUnit_Framework_TestCase as TestCase;

class ImmutableBagTest extends TestCase
{
    // region Creation / Unwrapping Methods

    public function provideFromAndToArray()
    {
        return [
            'bag'         => [new ImmutableBag(['foo' => 'bar'])],
            'traversable' => [new ArrayObject(['foo' => 'bar'])],
            'null'        => [null, []],
            'stdClass'    => [json_decode(json_encode(['foo' => 'bar']))],
            'array'       => [['foo' => 'bar']],
            'mixed'       => ['derp', ['derp']],
        ];
    }

    /**
     * @dataProvider provideFromAndToArray
     *
     * @param mixed $input
     * @param array $output
     */
    public function testFromAndToArray($input, $output = ['foo' => 'bar'])
    {
        $actual = ImmutableBag::from($input)->toArray();
        $this->assertSame($output, $actual);
    }

    public function testFromRecursive()
    {
        $a = [
            'foo' => 'bar',
            'items' => new ArrayObject(['hello' => 'world']),
            'std class' => json_decode(json_encode([
                'why use' => 'these',
            ])),
        ];

        $bag = ImmutableBag::fromRecursive($a);

        $bagArr = $bag->toArray();
        $this->assertEquals('bar', $bagArr['foo']);

        $this->assertInstanceOf(ImmutableBag::class, $bagArr['items']);
        /** @var ImmutableBag $items */
        $items = $bagArr['items'];
        $this->assertEquals(['hello' => 'world'], $items->toArray());

        $this->assertInstanceOf(ImmutableBag::class, $bagArr['std class']);
        /** @var ImmutableBag $stdClass */
        $stdClass = $bagArr['std class'];
        $this->assertEquals(['why use' => 'these'], $stdClass->toArray());
    }

    public function testToArrayRecursive()
    {
        $bag = new ImmutableBag([
            'foo' => 'bar',
            'colors' => new ImmutableBag(['red', 'blue']),
            'array' => ['hello', 'world'],
        ]);
        $expected = [
            'foo' => 'bar',
            'colors' => ['red', 'blue'],
            'array' => ['hello', 'world'],
        ];

        $arr = $bag->toArrayRecursive();

        $this->assertEquals($expected, $arr);
    }

    public function testCombine()
    {
        $actual = ImmutableBag::combine(['red', 'green'], ['bad', 'good'])->toArray();
        $expected = [
            'red' => 'bad',
            'green' => 'good',
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testCombineEmpty()
    {
        $actual = ImmutableBag::combine([], [])->toArray();

        $this->assertEquals([], $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testCombineDifferentSizes()
    {
        ImmutableBag::combine(['derp'], ['wait', 'wut']);
    }

    // endregion

    // region Methods returning a single value

    public function testHas()
    {
        $bag = new ImmutableBag(['foo' => 'bar', 'null' => null]);

        $this->assertTrue($bag->has('foo'));
        $this->assertTrue($bag->has('null'));

        $this->assertFalse($bag->has('derp'));
    }

    public function testHasPath()
    {
        $bag = new ImmutableBag([
            'items' => new ArrayObject([
                'foo' => 'bar',
            ]),
        ]);

        $this->assertTrue($bag->hasPath('items/foo'));

        $this->assertFalse($bag->hasPath('items/derp'));
        $this->assertFalse($bag->hasPath('derp'));
    }

    public function testHasItem()
    {
        $foo = new ArrayObject();
        $bag = new ImmutableBag([
            'foo' => 'bar',
            'items' => $foo,
        ]);

        $this->assertTrue($bag->hasItem('bar'));
        $this->assertTrue($bag->hasItem($foo));

        $this->assertFalse($bag->hasItem('derp'));
    }

    public function testGet()
    {
        $bag = new ImmutableBag([
            'foo' => 'bar',
            'null' => null,
        ]);

        $this->assertEquals('bar', $bag->get('foo'));
        $this->assertNull($bag->get('null', 'default'));
        $this->assertEquals('default', $bag->get('derp', 'default'));
    }

    public function testGetPath()
    {
        $bag = new ImmutableBag([
            'items' => new ArrayObject([
                'foo' => 'bar',
            ]),
        ]);

        $this->assertEquals('bar', $bag->getPath('items/foo'));

        $this->assertNull($bag->getPath('derp/derp'));
        $this->assertEquals('default', $bag->getPath('derp/derp', 'default'));
    }

    public function testCount()
    {
        $bag = new ImmutableBag(['foo', 'bar']);

        $this->assertEquals(2, count($bag));
    }

    public function testEmpty()
    {
        $bag = new ImmutableBag(['foo', 'bar']);
        $this->assertFalse($bag->isEmpty());

        $empty = new ImmutableBag();
        $this->assertTrue($empty->isEmpty());
    }

    public function testFirst()
    {
        $bag = new ImmutableBag(['first', 'second']);
        $this->assertEquals('first', $bag->first());

        $empty = new ImmutableBag();
        $this->assertFalse($empty->first());
    }

    public function testLast()
    {
        $bag = new ImmutableBag(['first', 'second']);
        $this->assertEquals('second', $bag->last());

        $empty = new ImmutableBag();
        $this->assertFalse($empty->last());
    }

    public function testJoin()
    {
        $bag = new ImmutableBag(['first', 'second', 'third']);
        $this->assertEquals('first, second, third', $bag->join(', '));

        $empty = new ImmutableBag();
        $this->assertEquals('', $empty->join(', '));
    }

    public function testSum()
    {
        $bag = new ImmutableBag([3, 4]);
        $this->assertEquals(7, $bag->sum());

        $empty = new ImmutableBag();
        $this->assertEquals(0, $empty->sum());

        $dumb = new ImmutableBag(['wut']);
        $this->assertEquals(0, $dumb->sum());
    }

    public function testProduct()
    {
        $bag = new ImmutableBag([3, 4]);
        $this->assertEquals(12, $bag->product());

        $empty = new ImmutableBag();
        $this->assertEquals(1, $empty->product());

        $dumb = new ImmutableBag(['wut']);
        $this->assertEquals(0, $dumb->product());
    }

    /**
     * @dataProvider \Bolt\Tests\Helper\ArrTest::isIndexedProvider
     *
     * @param array $data
     * @param bool  $isIndexed
     */
    public function testIsAssociativeAndIndexed($data, $isIndexed)
    {
        $bag = new ImmutableBag($data);

        $this->assertEquals($isIndexed, $bag->isIndexed());
        $this->assertEquals(!$isIndexed, $bag->isAssociative());
    }

    // endregion

    // region Methods returning a new bag

    public function testMutable()
    {
        $bag = new ImmutableBag(['foo' => 'bar']);

        $mutable = $bag->mutable();

        $this->assertNotSame($bag, $mutable);
        $this->assertInstanceOf(Bag::class, $mutable);
        $this->assertEquals(['foo' => 'bar'], $mutable->toArray());
    }

    public function testKeys()
    {
        $bag = new ImmutableBag(['foo' => 'bar', 'hello' => 'world']);

        $keys = $bag->keys();

        $this->assertBagResult(['foo', 'hello'], $bag, $keys);
    }

    public function testValues()
    {
        $bag = new ImmutableBag(['foo' => 'bar', 'hello' => 'world']);

        $values = $bag->values();

        $this->assertBagResult(['bar', 'world'], $bag, $values);
    }

    public function testMap()
    {
        $bag = new ImmutableBag(['foo' => 'bar', 'hello' => 'world']);

        $actual = $bag->map(function ($key, $item) {
            return $key . '.' . $item;
        });

        $this->assertBagResult(['foo' => 'foo.bar', 'hello' => 'hello.world'], $bag, $actual);
    }

    public function testMapKeys()
    {
        $bag = new ImmutableBag(['foo' => 'bar', 'hello' => 'world']);

        $actual = $bag->mapKeys(function ($key, $item) {
            return $key . '.' . $item;
        });

        $this->assertBagResult(['foo.bar' => 'bar', 'hello.world' => 'world'], $bag, $actual);
    }

    public function testFilter()
    {
        $bag = new ImmutableBag(['foo', 'bar', 'hello', 'world']);

        $actual = $bag->filter(function ($key, $item) {
            return $item !== 'bar' && $key !== 2;
        });

        $this->assertBagResult([0 => 'foo', 3 => 'world'], $bag, $actual);
    }

    public function testClean()
    {
        $bag = new ImmutableBag([null, '', 'foo', false, 0, true, [], ['bar']]);

        $actual = $bag->clean();

        $this->assertBagResult([2 => 'foo', 5 => true, 7 => ['bar']], $bag, $actual);
    }

    public function testReplace()
    {
        $bag = new ImmutableBag(['foo' => 'bar']);

        $actual = $bag->replace(['foo' => 'baz', 'hello' => 'world']);

        $this->assertBagResult(['foo' => 'baz', 'hello' => 'world'], $bag, $actual);
    }

    /**
     * @dataProvider \Bolt\Tests\Helper\ArrTest::replaceRecursiveProvider
     *
     * @param array $array1
     * @param array $array2
     * @param array $expected
     */
    public function testReplaceRecursive($array1, $array2, $expected)
    {
        $bag = ImmutableBag::from($array1);

        $actual = $bag->replaceRecursive($array2);

        $this->assertBagResult($expected, $bag, $actual);
    }

    public function testDefaults()
    {
        $bag = new ImmutableBag(['foo' => 'bar']);

        $actual = $bag->defaults(['foo' => 'baz', 'hello' => 'world']);

        $this->assertBagResult(['foo' => 'bar', 'hello' => 'world'], $bag, $actual);
    }

    /**
     * @dataProvider \Bolt\Tests\Helper\ArrTest::replaceRecursiveProvider
     *
     * @param array $array1
     * @param array $array2
     * @param array $expected
     */
    public function testDefaultsRecursive($array1, $array2, $expected)
    {
        $bag = ImmutableBag::from($array2);

        $actual = $bag->defaultsRecursive($array1);

        $this->assertBagResult($expected, $bag, $actual);
    }

    public function testMerge()
    {
        $bag = new ImmutableBag(['foo', 'bar']);

        $actual = $bag->merge(['hello', 'world']);

        $this->assertBagResult(['foo', 'bar', 'hello', 'world'], $bag, $actual);
    }

    public function provideSlice()
    {
        return [
            [ 0, null, false, ['foo', 'bar', 'hello', 'world']],
            [ 1, null, false, [       'bar', 'hello', 'world']],
            [ 1,    2, false, [       'bar', 'hello'         ]],
            [-2, null, false, [              'hello', 'world']],
            [ 1,   -1, false, [       'bar', 'hello'         ]],
            [-2,   -1, false, [              'hello'         ]],
            [ 1,    2,  true, [1 => 'bar', 2 => 'hello']],
        ];
    }

    /**
     * @dataProvider provideSlice
     *
     * @param int      $offset
     * @param int|null $length
     * @param bool     $preserveKeys
     * @param array    $expected
     */
    public function testSlice($offset, $length, $preserveKeys, $expected)
    {
        $bag = new ImmutableBag(['foo', 'bar', 'hello', 'world']);

        $actual = $bag->slice($offset, $length, $preserveKeys);

        $this->assertBagResult($expected, $bag, $actual);
    }

    public function testPartition()
    {
        $bag = new ImmutableBag(['foo' => 'bar', 'hello' => 'world']);

        $actual = $bag->partition(function ($key, $item) {
            return strpos($item, 'a') !== false;
        });

        $this->assertTrue(is_array($actual));
        $this->assertCount(2, $actual);

        list($trueBag, $falseBag) = $actual;
        $this->assertBagResult(['foo' => 'bar'], $bag, $trueBag);
        $this->assertBagResult(['hello' => 'world'], $bag, $falseBag);
    }

    public function testColumn()
    {
        $bag = new ImmutableBag([
            ['id' => 'foo', 'value' => 'bar'],
            ['id' => 'hello', 'value' => 'world'],
        ]);

        $actual = $bag->column('id');
        $this->assertBagResult(['foo', 'hello'], $bag, $actual);

        $actual = $bag->column('value', 'id');
        $this->assertBagResult(['foo' => 'bar', 'hello' => 'world'], $bag, $actual);
    }

    public function testFlip()
    {
        $bag = new ImmutableBag(['foo' => 'bar', 'hello' => 'world', 'second' => 'world']);

        $actual = $bag->flip();

        $this->assertBagResult(['bar' => 'foo', 'world' => 'second'], $bag, $actual);
    }

    public function testReduce()
    {
        $bag = new ImmutableBag([1, 2, 3, 4]);

        $product = $bag->reduce(
            function ($carry, $item) {
                return $carry * $item;
            },
            1
        );

        $this->assertEquals(24, $product);
    }

    public function testUnique()
    {
        $bag = new ImmutableBag(['foo', 'bar', 'foo', 3, '3', '3a', '3']);
        $actual = $bag->unique();
        $this->assertBagResult(['foo', 'bar', 3 , '3', '3a'], $bag, $actual);

        $first = new ImmutableBag();
        $second = new ImmutableBag();
        $bag = new ImmutableBag([$first, $second, $first]);
        $actual = $bag->unique();
        $this->assertBagResult([$first, $second], $bag, $actual);
    }

    public function testChunk()
    {
        $bag = new ImmutableBag(['a', 'b', 'c', 'd', 'e']);

        $chunked = $bag->chunk(2);

        $this->assertInstanceOf(ImmutableBag::class, $chunked);
        $this->assertNotSame($bag, $chunked);
        $this->assertCount(3, $chunked);

        $this->assertBagResult(['a', 'b'], $bag, $chunked->get(0));
        $this->assertBagResult(['c', 'd'], $bag, $chunked->get(1));
        $this->assertBagResult(['e'], $bag, $chunked->get(2));
    }

    public function testChunkPreserveKeys()
    {
        $bag = new ImmutableBag(['a', 'b', 'c', 'd', 'e']);

        $chunked = $bag->chunk(2, true);

        $this->assertInstanceOf(ImmutableBag::class, $chunked);
        $this->assertNotSame($bag, $chunked);
        $this->assertCount(3, $chunked);

        $this->assertBagResult(['a', 'b'], $bag, $chunked->get(0));
        $this->assertBagResult([2 => 'c', 3 => 'd'], $bag, $chunked->get(1));
        $this->assertBagResult([4 => 'e'], $bag, $chunked->get(2));
    }

    // endregion

    // region Sorting Methods

    public function testReverse()
    {
        $bag = new ImmutableBag(['a', 'b', 'c', 'd']);

        $actual = $bag->reverse();

        $this->assertBagResult(['d', 'c', 'b', 'a'], $bag, $actual);
    }

    public function testReversePreserveKeys()
    {
        $bag = new ImmutableBag(['a', 'b', 'c', 'd']);

        $actual = $bag->reverse(true);

        $this->assertBagResult([3 => 'd', 2 => 'c', 1 => 'b', 0 => 'a'], $bag, $actual);
    }

    public function testShuffle()
    {
        $bag = new ImmutableBag(['a', 'b', 'c', 'd']);

        $actual = $bag->shuffle();

        $this->assertInstanceOf(ImmutableBag::class, $actual);
        $this->assertNotSame($bag, $actual);
        $this->assertNotEquals($bag->toArray(), $actual->toArray());

        $sorted = $actual->toArray();
        sort($sorted);
        $this->assertEquals($bag->toArray(), $sorted);
    }

    // endregion

    // region Internal Methods

    public function testIterator()
    {
        $bag = new ImmutableBag(['a', 'b', 'c', 'd']);

        $this->assertEquals(['a', 'b', 'c', 'd'], iterator_to_array($bag));
    }

    public function testOffsetExists()
    {
        $bag = new ImmutableBag(['foo' => 'bar']);

        $this->assertTrue(isset($bag['foo']));
        $this->assertFalse(isset($bag['derp']));
    }

    public function testOffsetGet()
    {
        $bag = new ImmutableBag(['foo' => 'bar']);

        $this->assertEquals('bar', $bag['foo']);
        $this->assertNull($bag['nope']);
    }

    public function testOffsetGetByReference()
    {
        $bag = new ImmutableBag(['arr' => ['1']]);

        // Assert arrays are not able to be modified by reference.
        $errors = new \ArrayObject();
        set_error_handler(function ($type, $message) use ($errors) {
            $errors[] = [$type, $message];
        });

        $arr = &$bag['arr'];

        restore_error_handler();

        $this->assertEquals([[E_NOTICE, 'Indirect modification of overloaded element of Bolt\Collection\ImmutableBag has no effect']], $errors->getArrayCopy());
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Cannot modify items on an Bolt\Collection\ImmutableBag
     */
    public function testOffsetSet()
    {
        $bag = new ImmutableBag();

        $bag['foo'] = 'bar';
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Cannot remove items from an Bolt\Collection\ImmutableBag
     */
    public function testOffsetUnset()
    {
        $bag = new ImmutableBag(['foo' => 'bar']);

        unset($bag['foo']);
    }

    public function testDebugInfo()
    {
        $bag = new ImmutableBag(['foo' => 'bar']);

        $this->assertEquals($bag->toArray(), $bag->__debugInfo());
        $this->assertEquals('bar', $bag->foo);
    }

    // endregion

    /**
     * Assert $actualBag is an ImmutableBag that's a different instance from $initialBag and its items equal $expected.
     *
     * @param array        $expected
     * @param ImmutableBag $initialBag
     * @param ImmutableBag $actualBag
     */
    protected function assertBagResult($expected, $initialBag, $actualBag)
    {
        $this->assertInstanceOf(ImmutableBag::class, $actualBag);
        $this->assertNotSame($initialBag, $actualBag);
        $this->assertEquals($expected, $actualBag->toArray());
    }
}
