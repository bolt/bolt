<?php

namespace Bolt\Tests\Twig\Runtime;

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
        $app = $this->getApp();
        $handler = new ArrayExtension();

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

        $handler = new ArrayExtension();

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

        $handler = new ArrayExtension();

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

        $handler = new ArrayExtension();

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

        $handler = new ArrayExtension();

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

        $handler = new ArrayExtension();

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

        $handler = new ArrayExtension();

        $result = $handler->order($srcArr, 'name');
        $this->assertRegExp('#{"[0-2]":{"name":"Bruce","type":"clippy"},"[0-2]":{"name":"Johno","type":"(batman|koala)"},"[0-2]":{"name":"Johno","type":"(koala|batman)"}}#', json_encode($result));
    }

    public function testShuffleString()
    {
        $app = $this->getApp();
        $handler = new ArrayExtension();

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

        $handler = new ArrayExtension();
        $handler->shuffle(['shuffle', 'board']);
    }
}
