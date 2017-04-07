<?php

namespace Bolt\Tests\Collection;

use ArrayObject;
use Bolt\Collection\Bag;
use Bolt\Collection\ImmutableBag;
use PHPUnit_Framework_TestCase as TestCase;

class BagTest extends TestCase
{
    public function testImmutable()
    {
        $bag = new Bag(['foo', 'bar']);

        $immutable = $bag->immutable();

        $this->assertNotSame($bag, $immutable);
        $this->assertInstanceOf(ImmutableBag::class, $immutable);
        $this->assertEquals(['foo', 'bar'], $immutable->toArray());
    }

    public function testAdd()
    {
        $bag = new Bag();

        $bag->add('foo');
        $bag->add('bar');

        $this->assertEquals(['foo', 'bar'], $bag->toArray());
    }

    public function testPrepend()
    {
        $bag = new Bag();

        $bag->prepend('foo');
        $bag->prepend('bar');

        $this->assertEquals(['bar', 'foo'], $bag->toArray());
    }

    public function testSet()
    {
        $bag = new Bag();

        $bag->set('foo', 'bar');

        $this->assertEquals(['foo' => 'bar'], $bag->toArray());
    }

    public function testSetPath()
    {
        $bag = new Bag([
            'items' => new ArrayObject([
                'foo' => 'bar',
            ]),
        ]);

        $bag->setPath('items/hello', 'world');
        $bag->setPath('items/colors/[]', 'red');
        $bag->setPath('items/colors/[]', 'blue');

        $this->assertEquals('world', $bag->getPath('items/hello'));

        $this->assertEquals(['red', 'blue'], $bag->getPath('items/colors'));
    }

    public function testClear()
    {
        $bag = new Bag(['foo', 'bar']);

        $bag->clear();

        $this->assertTrue($bag->isEmpty());
    }

    public function testRemove()
    {
        $bag = new Bag(['foo' => 'bar']);

        $this->assertEquals('bar', $bag->remove('foo'));
        $this->assertFalse($bag->has('foo'));

        $this->assertNull($bag->remove('derp'));
        $this->assertEquals('default', $bag->remove('derp', 'default'));
    }

    public function testRemoveItem()
    {
        $bag = new Bag(['foo', 'bar']);

        $bag->removeItem('bar');

        $this->assertFalse($bag->hasItem('bar'));
    }

    public function testRemoveFirst()
    {
        $bag = new Bag(['foo', 'bar']);

        $this->assertEquals('foo', $bag->removeFirst());
        $this->assertEquals('bar', $bag->removeFirst());
        $this->assertNull($bag->removeFirst());
        $this->assertTrue($bag->isEmpty());
    }

    public function testRemoveLast()
    {
        $bag = new Bag(['foo', 'bar']);

        $this->assertEquals('bar', $bag->removeLast());
        $this->assertEquals('foo', $bag->removeLast());
        $this->assertNull($bag->removeFirst());
        $this->assertTrue($bag->isEmpty());
    }

    // region Internal Methods

    public function testOffsetGet()
    {
        $bag = new Bag(['foo' => 'bar']);

        $this->assertEquals('bar', $bag['foo']);
        $this->assertNull($bag['nope']);
    }

    public function testOffsetGetModifyByReference()
    {
        $bag = new Bag(['arr' => ['1']]);

        // Assert arrays are not able to be modified by reference.
        $errors = new \ArrayObject();
        set_error_handler(function ($type, $message) use ($errors) {
            $errors[] = [$type, $message];
        });

        $arr = &$bag['arr'];
        $arr[] = '2';

        restore_error_handler();
        $this->assertEmpty($errors->getArrayCopy());

        $this->assertEquals(['1', '2'], $bag['arr']);
    }

    public function testOffsetSet()
    {
        $bag = new Bag();

        $bag['foo'] = 'bar';
        $bag[] = 'hello';
        $bag[] = 'world';

        $this->assertEquals('bar', $bag['foo']);
        $this->assertEquals('hello', $bag[0]);
        $this->assertEquals('world', $bag[1]);
    }

    public function testOffsetUnset()
    {
        $bag = new Bag(['foo' => 'bar']);

        unset($bag['foo']);

        $this->assertFalse($bag->has('foo'));
    }

    // endregion
}
