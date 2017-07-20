<?php

namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\ArrayHandler;

/**
 * Class to test Bolt\Twig\Handler\ArrayHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ArrayHandlerTest extends BoltUnitTest
{
    public function setUp()
    {
        $this->php = \PHPUnit_Extension_FunctionMocker::start($this, 'Bolt\Twig\Handler')
            ->mockFunction('shuffle')
            ->getMock()
        ;
    }

    public function testOrderEmpty()
    {
        $app = $this->getApp();
        $handler = new ArrayHandler($app);

        $result = $handler->order([], 'title');
        $this->assertSame([], $result);
    }

    public function testOrderNameAsc()
    {
        $app = $this->getApp();
        $srcArr = [
            ['name' => 'Johno', 'type' => 'koala'],
            ['name' => 'Bruce', 'type' => 'clippy'],
            ['name' => 'Wayne', 'type' => 'batman'],
        ];

        $handler = new ArrayHandler($app);

        $result = $handler->order($srcArr, 'name', null);
        $this->assertSame('{"1":{"name":"Bruce","type":"clippy"},"0":{"name":"Johno","type":"koala"},"2":{"name":"Wayne","type":"batman"}}', json_encode($result));
    }

    public function testOrderNameDesc()
    {
        $app = $this->getApp();
        $srcArr = [
            ['name' => 'Wayne', 'type' => 'batman'],
            ['name' => 'Bruce', 'type' => 'clippy'],
            ['name' => 'Johno', 'type' => 'koala'],
        ];

        $handler = new ArrayHandler($app);

        $result = $handler->order($srcArr, '-name', null);
        $this->assertSame('{"0":{"name":"Wayne","type":"batman"},"2":{"name":"Johno","type":"koala"},"1":{"name":"Bruce","type":"clippy"}}', json_encode($result));
    }

    public function testOrderNameAscTypeAsc()
    {
        $app = $this->getApp();
        $srcArr = [
            ['name' => 'Johno', 'type' => 'koala'],
            ['name' => 'Bruce', 'type' => 'clippy'],
            ['name' => 'Johno', 'type' => 'batman'],
        ];

        $handler = new ArrayHandler($app);

        $result = $handler->order($srcArr, 'name', 'type');
        $this->assertSame('{"1":{"name":"Bruce","type":"clippy"},"2":{"name":"Johno","type":"batman"},"0":{"name":"Johno","type":"koala"}}', json_encode($result));
    }

    public function testOrderNameAscTypeDesc()
    {
        $app = $this->getApp();
        $srcArr = [
            ['name' => 'Johno', 'type' => 'batman'],
            ['name' => 'Johno', 'type' => 'koala'],
            ['name' => 'Bruce', 'type' => 'clippy'],
        ];

        $handler = new ArrayHandler($app);

        $result = $handler->order($srcArr, 'name', '-type');
        $this->assertSame('{"2":{"name":"Bruce","type":"clippy"},"1":{"name":"Johno","type":"koala"},"0":{"name":"Johno","type":"batman"}}', json_encode($result));
    }

    public function testOrderNameAscTypeDescMatchingSecondary()
    {
        $app = $this->getApp();
        $srcArr = [
            ['name' => 'Johno', 'type' => 'batman'],
            ['name' => 'Johno', 'type' => 'batman'],
            ['name' => 'Bruce', 'type' => 'clippy'],
        ];

        $handler = new ArrayHandler($app);

        $result = $handler->order($srcArr, 'name', '-type');
        $this->assertRegExp('#{"[0-2]":{"name":"Bruce","type":"clippy"},"[0-2]":{"name":"Johno","type":"batman"},"[0-2]":{"name":"Johno","type":"batman"}}#', json_encode($result));
    }

    public function testOrderNameMatchNoSecondary()
    {
        $app = $this->getApp();
        $srcArr = [
            ['name' => 'Johno', 'type' => 'koala'],
            ['name' => 'Bruce', 'type' => 'clippy'],
            ['name' => 'Johno', 'type' => 'batman'],
        ];

        $handler = new ArrayHandler($app);

        $result = $handler->order($srcArr, 'name');
        $this->assertRegExp('#{"[0-2]":{"name":"Bruce","type":"clippy"},"[0-2]":{"name":"Johno","type":"(batman|koala)"},"[0-2]":{"name":"Johno","type":"(koala|batman)"}}#', json_encode($result));
    }

    public function testShuffleString()
    {
        $app = $this->getApp();
        $handler = new ArrayHandler($app);

        $result = $handler->shuffle('shuffleboard');
        $this->assertSame('shuffleboard', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testShuffleArray()
    {
        $app = $this->getApp();

        $this->php
            ->expects($this->once())
            ->method('shuffle')
        ;

        $handler = new ArrayHandler($app);
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
                ['abc', 'bcd', 'cde'], ['abc', 'bcd', 'def'], ['abc' => 'abc', 'bcd' => 'bcd', 'cde' => 'cde', 'def' => 'def']
            ],
            'Single additional key in different orders 2' => [
                ['bcd', 'cde', 'abc'], ['def', 'abc', 'bcd'], ['bcd' => 'bcd', 'cde' => 'cde', 'abc' => 'abc', 'def' => 'def']
            ],
            'Single additional key in different orders 3' => [
                ['cde', 'abc', 'bcd'], ['bcd', 'cde', 'def'], ['cde' => 'cde', 'abc' => 'abc', 'bcd' => 'bcd', 'def' => 'def']
            ],

            'Multiple additional keys in different orders 1' => [
                ['abc', 'bcd', 'cde'], ['abc', 'def', 'fgh'], ['abc' => 'abc', 'bcd' => 'bcd', 'cde' => 'cde', 'def' => 'def', 'fgh' => 'fgh']
            ],
            'Multiple additional keys in different orders 2' => [
                ['abc', 'bcd', 'cde'], ['fgh', 'cde', 'def'], ['abc' => 'abc', 'bcd' => 'bcd', 'cde' => 'cde', 'fgh' => 'fgh', 'def' => 'def']
            ],
            'Multiple additional keys in different orders 3' => [
                ['abc', 'bcd', 'cde'], ['fgh', 'def', 'efg'], ['abc' => 'abc', 'bcd' => 'bcd', 'cde' => 'cde', 'fgh' => 'fgh', 'def' => 'def', 'efg' => 'efg']
            ],

            'Indexed array of values' => [
                [
                    ['abc', 'bcd', 'cde'], ['fgh', 'def', 'efg']
                ],
                [
                    ['fgh', 'def', 'efg'], ['abc', 'bcd', 'cde']
                ],
                [
                    ['abc', 'bcd', 'cde'], ['fgh', 'def', 'efg']
                ],
            ],

            'Mapped array of values' => [
                [
                    'map1' => ['abc', 'bcd', 'cde'], 'map2' => ['fgh', 'def', 'efg']
                ],
                [
                    'map2' => ['fgh', 'def', 'efg'], 'map1' => ['abc', 'bcd', 'cde']
                ],
                [
                    'map1' => ['abc', 'bcd', 'cde'], 'map2' => ['fgh', 'def', 'efg']
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderUnique
     */
    public function testUnique($arr1, $arr2, $expected)
    {
        $app = $this->getApp();
        $handler = new ArrayHandler($app);

        $this->assertSame($expected, $handler->unique($arr1, $arr2));
    }
}
