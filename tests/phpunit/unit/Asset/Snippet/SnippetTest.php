<?php

namespace Bolt\Tests\Asset\Snippet;

use Bolt\Asset\Snippet\Snippet;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Asset\Snippet\Snippet
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SnippetTest extends TestCase
{
    public function testCreate()
    {
        self::assertInstanceOf(Snippet::class, Snippet::create());
    }

    public function testPriority()
    {
        $snippet = Snippet::create();

        self::assertInstanceOf(Snippet::class, $snippet->setPriority(42));
        self::assertSame(42, $snippet->getPriority());
    }

    public function testLocation()
    {
        $snippet = Snippet::create();

        self::assertInstanceOf(Snippet::class, $snippet->setLocation('gum-tree'));
        self::assertSame('gum-tree', $snippet->getLocation());
    }

    public function testCallback()
    {
        $snippet = Snippet::create();
        $callback = function () {};

        self::assertInstanceOf(Snippet::class, $snippet->setCallback($callback));
        self::assertSame($callback, $snippet->getCallback());
    }

    public function testCallbackArguments()
    {
        $snippet = Snippet::create();
        $args = ['koala', 'dropbear'];

        self::assertInstanceOf(Snippet::class, $snippet->setCallbackArguments($args));
        self::assertSame($args, $snippet->getCallbackArguments());
    }

    public function testZone()
    {
        $snippet = Snippet::create();

        self::assertInstanceOf(Snippet::class, $snippet->setZone('twilight'));
        self::assertSame('twilight', $snippet->getZone());
    }

    public function testCastString()
    {
        $snippet = Snippet::create()
            ->setCallback('dropbear')
        ;

        self::assertSame('dropbear', (string) $snippet);
    }

    public function testCastCallable()
    {
        $snippet = Snippet::create()
            ->setCallback(function ($arg) { return $arg; })
            ->setCallbackArguments(['koala'])
        ;

        self::assertSame('koala', (string) $snippet);
    }

    public function testCastInvalidArray()
    {
        $snippet = Snippet::create()->setCallback([]);

        self::assertSame('<!-- An exception occurred creating snippet -->', (string) $snippet);
    }

    public function testCastInvalidType()
    {
        $snippet = Snippet::create()->setCallback([function () {}]);

        self::assertSame('<!-- An exception occurred creating snippet -->', (string) $snippet);
    }
}
