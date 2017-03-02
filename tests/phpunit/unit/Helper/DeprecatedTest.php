<?php

namespace Bolt\Tests\Helper;

use Bolt\Helpers\Deprecated;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
class DeprecatedTest extends TestCase
{
    protected $deprecations = [];

    public function testMethod()
    {
        Deprecated::method(3.0, 'baz', 'Foo::bar');
        $this->assertDeprecation('Foo::bar() is deprecated since 3.0 and will be removed in %d.0. Use baz() instead.');

        $realClass = static::class;
        Deprecated::method(3.0, $realClass, 'Foo::bar');
        $this->assertDeprecation("Foo::bar() is deprecated since 3.0 and will be removed in %d.0. Use $realClass instead.");

        Deprecated::method(3.0, 'Do it this way instead.', 'Foo::bar');
        $this->assertDeprecation('Foo::bar() is deprecated since 3.0 and will be removed in %d.0. Do it this way instead.');
    }

    public function testMethodUsingBacktrace()
    {
        TestDeprecatedClass::foo();
        $this->assertDeprecation('Bolt\Tests\Helper\TestDeprecatedClass::foo() is deprecated and will be removed in %d.0.');

        deprecatedFunction();
        $this->assertDeprecation('Bolt\Tests\Helper\deprecatedFunction() is deprecated and will be removed in %d.0.');

        TestDeprecatedClass::magicStatic();
        $this->assertDeprecation('Bolt\Tests\Helper\TestDeprecatedClass::magicStatic() is deprecated and will be removed in %d.0.');

        $cls = new TestDeprecatedClass();
        $cls->magic();
        $this->assertDeprecation('Bolt\Tests\Helper\TestDeprecatedClass::magic() is deprecated and will be removed in %d.0.');

        $cls->magic;
        $this->assertDeprecation('Getting Bolt\Tests\Helper\TestDeprecatedClass::magic is deprecated and will be removed in %d.0.');

        $cls->magic = 'derp';
        $this->assertDeprecation('Setting Bolt\Tests\Helper\TestDeprecatedClass::magic is deprecated and will be removed in %d.0.');

        isset($cls->magic);
        $this->assertDeprecation('isset(Bolt\Tests\Helper\TestDeprecatedClass::magic) is deprecated and will be removed in %d.0.');
        unset($cls->magic);
        $this->assertDeprecation('unset(Bolt\Tests\Helper\TestDeprecatedClass::magic) is deprecated and will be removed in %d.0.');

        new TestDeprecatedClass(true);
        $this->assertDeprecation('Bolt\Tests\Helper\TestDeprecatedClass is deprecated and will be removed in %d.0.');
    }

    public function testClass()
    {
        Deprecated::cls('Foo\Bar');
        $this->assertDeprecation('Foo\Bar is deprecated and will be removed in %d.0.');
        Deprecated::cls('Foo\Bar', null, 'Bar\Baz');
        $this->assertDeprecation('Foo\Bar is deprecated and will be removed in %d.0. Use Bar\Baz instead.');
        Deprecated::cls('Foo\Bar', null, 'Do it this way instead.');
        $this->assertDeprecation('Foo\Bar is deprecated and will be removed in %d.0. Do it this way instead.');
    }

    public function testService()
    {
        Deprecated::service('foo');
        $this->assertDeprecation('Accessing $app[\'foo\'] is deprecated and will be removed in %d.0.');
        Deprecated::service('foo', null, 'bar');
        $this->assertDeprecation('Accessing $app[\'foo\'] is deprecated and will be removed in %d.0. Use $app[\'bar\'] instead.');
        Deprecated::service('foo', null, 'Do it this way instead.');
        $this->assertDeprecation('Accessing $app[\'foo\'] is deprecated and will be removed in %d.0. Do it this way instead.');
    }

    public function testWarn()
    {
        Deprecated::warn('Foo bar');
        $this->assertDeprecation('Foo bar is deprecated and will be removed in %d.0.');

        Deprecated::warn('Foo bar', 3.0);
        $this->assertDeprecation('Foo bar is deprecated since 3.0 and will be removed in %d.0.');
        Deprecated::warn('Foo bar', 3.3);
        $this->assertDeprecation('Foo bar is deprecated since 3.3 and will be removed in %d.0.');

        Deprecated::warn('Foo bar', null, 'Use baz instead.');
        $this->assertDeprecation('Foo bar is deprecated and will be removed in %d.0. Use baz instead.');
        Deprecated::warn('Foo bar', 3.0, 'Use baz instead.');
        $this->assertDeprecation('Foo bar is deprecated since 3.0 and will be removed in %d.0. Use baz instead.');
    }

    public function testRaw()
    {
        Deprecated::raw('Hello world.');
        $this->assertDeprecation('Hello world.');
    }

    protected function setUp()
    {
        $this->deprecations = [];
        set_error_handler(
            function ($type, $msg, $file, $line) {
                $this->deprecations[] = $msg;
            },
            E_USER_DEPRECATED
        );
    }

    protected function tearDown()
    {
        restore_error_handler();
    }

    private function assertDeprecation($msg)
    {
        $this->assertNotEmpty($this->deprecations, 'No deprecations triggered.');
        $this->assertStringMatchesFormat($msg, $this->deprecations[0]);
        $this->deprecations = [];
    }
}

class TestDeprecatedClass
{
    public function __construct($deprecatedClass = false)
    {
        if ($deprecatedClass) {
            Deprecated::method();
        }
    }

    public static function foo()
    {
        Deprecated::method();
    }

    public function __call($name, $arguments)
    {
        Deprecated::method();
    }

    public static function __callStatic($name, $arguments)
    {
        Deprecated::method();
    }

    public function __get($name)
    {
        Deprecated::method();
    }

    public function __set($name, $value)
    {
        Deprecated::method();
    }

    public function __isset($name)
    {
        Deprecated::method();
    }

    public function __unset($name)
    {
        Deprecated::method();
    }
}

function deprecatedFunction()
{
    Deprecated::method();
}
