<?php

namespace Bolt\Tests\Routing;

use Bolt\Routing\CallbackResolver;
use Bolt\Tests\BoltUnitTest;

class CallbackResolverTest extends BoltUnitTest
{
    public function testService()
    {
        $test = new TestClass();
        $resolver = $this->resolver([], [
            'test.class' => function () use ($test) {
                return $test;
            },
        ]);
        $str = 'test.class:foo';
        $callback = $resolver->resolveCallback($str);
        $this->assertCallback($callback, $test);
    }

    public function testStringInClassMap()
    {
        $test = new TestClass();
        $resolver = $this->resolver([
            'Bolt\Tests\Routing\TestClass' => 'test.class',
        ], [
            'test.class' => $test,
        ]);
        $str = 'Bolt\Tests\Routing\TestClass::foo';

        // True because it needs to be converted
        $this->assertTrue($resolver->isValid($str));

        $callback = $resolver->resolveCallback($str);
        $this->assertCallback($callback, $test);
    }

    public function testStringNotInClassMap()
    {
        $str = 'Bolt\Tests\Routing\TestClass::foo';
        // True because it needs to be converted
        $this->assertTrue($this->resolver()->isValid($str));

        $callback = $this->resolver()->resolveCallback($str);
        $this->assertCallback($callback);
    }

    public function testStringNotInClassMapAndStatic()
    {
        $str = 'Bolt\Tests\Routing\TestClass::staticFoo';
        // False because already valid
        $this->assertFalse($this->resolver()->isValid($str));

        $callback = $this->resolver()->resolveCallback($str);
        $this->assertCallback($callback);
    }

    public function testStringNonExistentClassNotInClassMapFails()
    {
        $str = 'Bolt\Tests\Routing\TestClassDerp::staticFoo';
        // False because it is invalid
        $this->assertFalse($this->resolver()->isValid($str));

        $callback = $this->resolver()->resolveCallback($str);
        $this->assertNotCallable($callback);
    }

    public function testStringWithParams()
    {
        $arr = ['Bolt\Tests\Routing\TestClass::withParams', ['bolt']];
        // True because it is needs to be converted
        $this->assertTrue($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertCallable($callback);
        $this->assertSame('bolt', call_user_func($callback));
    }

    public function testArrayInClassMap()
    {
        $test = new TestClass();
        $resolver = $this->resolver(
            ['Bolt\Tests\Routing\TestClass' => 'test.class'],
            ['test.class'                   => $test]
        );
        $arr = ['Bolt\Tests\Routing\TestClass', 'foo'];
        $callback = $resolver->resolveCallback($arr);
        $this->assertCallback($callback, $test);
    }

    public function testArrayNotInClassMap()
    {
        $arr = ['Bolt\Tests\Routing\TestClass', 'foo'];
        // True because it is needs to be converted
        $this->assertTrue($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertCallback($callback);
    }

    public function testArrayNotInClassMapAndStatic()
    {
        $arr = ['Bolt\Tests\Routing\TestClass', 'staticFoo'];
        // False because it is already valid
        $this->assertFalse($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertCallback($callback);
    }

    public function testArrayNonExistentClassNotInClassMapFails()
    {
        $arr = ['Bolt\Tests\Routing\TestClassDerp', 'staticFoo'];
        // False because it is invalid
        $this->assertFalse($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertNotCallable($callback);
    }

    public function testArrayWithParams()
    {
        $arr = [['Bolt\Tests\Routing\TestClass', 'withParams'], ['bolt']];
        // True because it is needs to be converted
        $this->assertTrue($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertCallable($callback);
        $this->assertSame('bolt', call_user_func($callback));
    }

    public function testArrayWithObject()
    {
        $arr = [new TestClass(), 'foo'];
        // False because it is already valid
        $this->assertFalse($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertCallback($callback);
    }

    public function testWhatAreYouDoing()
    {
        $obj = new \stdClass();
        // False because it is invalid
        $this->assertFalse($this->resolver()->isValid($obj));

        $callback = $this->resolver()->resolveCallback($obj);
        $this->assertNotCallable($callback);
    }

    protected function resolver($classmap = [], $services = [])
    {
        return new CallbackResolver(new \Pimple($services), $classmap);
    }

    protected function assertCallback($callback, $instance = null)
    {
        $this->assertCallable($callback);
        $this->assertTrue(call_user_func($callback));
        if ($instance) {
            $this->assertTrue($instance->called, 'Callback did not use class from container service');
        }
    }

    protected function assertNotCallable($callback)
    {
        $this->assertCallable($callback, false);
    }

    protected function assertCallable($callback, $expected = true)
    {
        $str = 'Callback';
        if (is_string($callback)) {
            $str = $callback;
        } elseif (is_array($callback) && is_string(reset($callback))) {
            $cls = reset($callback);
            $method = end($callback);
            $str = sprintf('[%s, %s]', $cls, $method);
        }
        $message = $expected ? "$str is not callable" : "$str should not be callable";
        $this->assertSame($expected, is_callable($callback), $message);
    }
}

class TestClass
{
    public $called = false;

    public function foo()
    {
        $this->called = true;

        return true;
    }

    public static function staticFoo()
    {
        return true;
    }

    public function withParams($param)
    {
        return $param;
    }
}
