<?php

namespace Bolt\Tests;

use Bolt\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Bolt\Cache
     */
    protected $object;

    public function setUp()
    {
        $this->object = new Cache();
    }

    public function tearDown()
    {
        $this->object->flushAll();
    }

    /**
     * @return array
     */
    public static function setProvider()
    {
        return array(
            array(
                'bar',
                'bar'
            ),
            array(
                array('foo' => 'bar', 'baz' => 'meh'),
                array('foo' => 'bar', 'baz' => 'meh')
            ),
            array(
                new \Bolt\Tests\FooObject(),
                new \Bolt\Tests\FooObject()
            )
        );
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($value, $expected)
    {
        $key = 'foo';
        $this->object->save($key, $value);
        $this->assertEquals($expected, $this->object->get($key));
    }
}
