<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Common\Json;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Extension\ArrayExtension;

/**
 * Class to test Bolt\Twig\Runtime\ArrayExtension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ArrayRuntimeTest extends BoltUnitTest
{
    public function setUp()
    {
        $this->php = \PHPUnit_Extension_FunctionMocker::start($this, 'Bolt\Twig\Extension')
            ->mockFunction('shuffle')
            ->getMock()
        ;
    }

    public function testOrderEmpty()
    {
        $handler = new ArrayExtension();

        $result = $handler->order([], 'title');
        $this->assertSame([], $result);
    }

    public function testOrderNameAsc()
    {
        $srcArr = [
            ['name' => 'Johno', 'type' => 'koala'],
            ['name' => 'Bruce', 'type' => 'clippy'],
            ['name' => 'Wayne', 'type' => 'batman'],
        ];

        $handler = new ArrayExtension();

        $result = $handler->order($srcArr, 'name', null);
        $this->assertSame('{"1":{"name":"Bruce","type":"clippy"},"0":{"name":"Johno","type":"koala"},"2":{"name":"Wayne","type":"batman"}}', Json::dump($result));
    }

    public function testOrderNameDesc()
    {
        $srcArr = [
            ['name' => 'Wayne', 'type' => 'batman'],
            ['name' => 'Bruce', 'type' => 'clippy'],
            ['name' => 'Johno', 'type' => 'koala'],
        ];

        $handler = new ArrayExtension();

        $result = $handler->order($srcArr, '-name', null);
        $this->assertSame('{"0":{"name":"Wayne","type":"batman"},"2":{"name":"Johno","type":"koala"},"1":{"name":"Bruce","type":"clippy"}}', Json::dump($result));
    }

    public function testOrderNameAscTypeAsc()
    {
        $srcArr = [
            ['name' => 'Johno', 'type' => 'koala'],
            ['name' => 'Bruce', 'type' => 'clippy'],
            ['name' => 'Johno', 'type' => 'batman'],
        ];

        $handler = new ArrayExtension();

        $result = $handler->order($srcArr, 'name', 'type');
        $this->assertSame('{"1":{"name":"Bruce","type":"clippy"},"2":{"name":"Johno","type":"batman"},"0":{"name":"Johno","type":"koala"}}', Json::dump($result));
    }

    public function testOrderNameAscTypeDesc()
    {
        $srcArr = [
            ['name' => 'Johno', 'type' => 'batman'],
            ['name' => 'Johno', 'type' => 'koala'],
            ['name' => 'Bruce', 'type' => 'clippy'],
        ];

        $handler = new ArrayExtension();

        $result = $handler->order($srcArr, 'name', '-type');
        $this->assertSame('{"2":{"name":"Bruce","type":"clippy"},"1":{"name":"Johno","type":"koala"},"0":{"name":"Johno","type":"batman"}}', Json::dump($result));
    }

    public function testOrderNameAscTypeDescMatchingSecondary()
    {
        $srcArr = [
            ['name' => 'Johno', 'type' => 'batman'],
            ['name' => 'Johno', 'type' => 'batman'],
            ['name' => 'Bruce', 'type' => 'clippy'],
        ];

        $handler = new ArrayExtension();

        $result = $handler->order($srcArr, 'name', '-type');
        $this->assertRegExp('#{"[0-2]":{"name":"Bruce","type":"clippy"},"[0-2]":{"name":"Johno","type":"batman"},"[0-2]":{"name":"Johno","type":"batman"}}#', Json::dump($result));
    }

    public function testOrderNameMatchNoSecondary()
    {
        $srcArr = [
            ['name' => 'Johno', 'type' => 'koala'],
            ['name' => 'Bruce', 'type' => 'clippy'],
            ['name' => 'Johno', 'type' => 'batman'],
        ];

        $handler = new ArrayExtension();

        $result = $handler->order($srcArr, 'name');
        $this->assertRegExp('#{"[0-2]":{"name":"Bruce","type":"clippy"},"[0-2]":{"name":"Johno","type":"(batman|koala)"},"[0-2]":{"name":"Johno","type":"(koala|batman)"}}#', Json::dump($result));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Second parameter passed to Bolt\Twig\Extension\ArrayExtension::order must be a string, object given
     */
    public function testOrderInvalidOn()
    {
        $handler = new ArrayExtension();
        $handler->order([], new \stdClass());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Third parameter passed to Bolt\Twig\Extension\ArrayExtension::order must be a string, object given
     */
    public function testOrderInvalidOnSecondary()
    {
        $handler = new ArrayExtension();
        $handler->order([], '', new \stdClass());
    }

    public function testShuffleString()
    {
        $handler = new ArrayExtension();

        $result = $handler->shuffle('shuffleboard');
        $this->assertSame('shuffleboard', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testShuffleArray()
    {
        $this->php
            ->expects($this->once())
            ->method('shuffle')
        ;

        $handler = new ArrayExtension();
        $handler->shuffle(['shuffle', 'board']);
    }

    public function dataProviderUnique()
    {
        return [
            'Same keys in different orders 1' => [
                ['abc', 'bcd', 'cde'], ['abc', 'bcd', 'cde'], ['abc' => 'abc', 'bcd' => 'bcd', 'cde' => 'cde'],
            ],
            'Same keys in different orders 2' => [
                ['bcd', 'cde', 'abc'], ['abc', 'bcd', 'cde'], ['bcd' => 'bcd', 'cde' => 'cde', 'abc' => 'abc'],
            ],
            'Same keys in different orders 3' => [
                ['cde', 'abc', 'bcd'], ['abc', 'bcd', 'cde'], ['cde' => 'cde', 'abc' => 'abc', 'bcd' => 'bcd'],
            ],

            'Single additional key in different orders 1' => [
                ['abc', 'bcd', 'cde'], ['abc', 'bcd', 'def'], ['abc' => 'abc', 'bcd' => 'bcd', 'cde' => 'cde', 'def' => 'def'],
            ],
            'Single additional key in different orders 2' => [
                ['bcd', 'cde', 'abc'], ['def', 'abc', 'bcd'], ['bcd' => 'bcd', 'cde' => 'cde', 'abc' => 'abc', 'def' => 'def'],
            ],
            'Single additional key in different orders 3' => [
                ['cde', 'abc', 'bcd'], ['bcd', 'cde', 'def'], ['cde' => 'cde', 'abc' => 'abc', 'bcd' => 'bcd', 'def' => 'def'],
            ],

            'Multiple additional keys in different orders 1' => [
                ['abc', 'bcd', 'cde'], ['abc', 'def', 'fgh'], ['abc' => 'abc', 'bcd' => 'bcd', 'cde' => 'cde', 'def' => 'def', 'fgh' => 'fgh'],
            ],
            'Multiple additional keys in different orders 2' => [
                ['abc', 'bcd', 'cde'], ['fgh', 'cde', 'def'], ['abc' => 'abc', 'bcd' => 'bcd', 'cde' => 'cde', 'fgh' => 'fgh', 'def' => 'def'],
            ],
            'Multiple additional keys in different orders 3' => [
                ['abc', 'bcd', 'cde'], ['fgh', 'def', 'efg'], ['abc' => 'abc', 'bcd' => 'bcd', 'cde' => 'cde', 'fgh' => 'fgh', 'def' => 'def', 'efg' => 'efg'],
            ],

            'Indexed array of values' => [
                [
                    ['abc', 'bcd', 'cde'], ['fgh', 'def', 'efg'],
                ],
                [
                    ['fgh', 'def', 'efg'], ['abc', 'bcd', 'cde'],
                ],
                [
                    ['abc', 'bcd', 'cde'], ['fgh', 'def', 'efg'],
                ],
            ],

            'Mapped array of values' => [
                [
                    'map1' => ['abc', 'bcd', 'cde'], 'map2' => ['fgh', 'def', 'efg'],
                ],
                [
                    'map2' => ['fgh', 'def', 'efg'], 'map1' => ['abc', 'bcd', 'cde'],
                ],
                [
                    'map1' => ['abc', 'bcd', 'cde'], 'map2' => ['fgh', 'def', 'efg'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderUnique
     */
    public function testUnique($arr1, $arr2, $expected)
    {
        $handler = new ArrayExtension();

        $this->assertSame($expected, $handler->unique($arr1, $arr2));
    }
}
