<?php

namespace Bolt\Tests\Routing;

use Bolt\Routing\CallbackResolver;
use Bolt\Tests\BoltUnitTest;
use Pimple\Container;

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

    public function testStringWithParams()
    {
        $arr = ['Bolt\Tests\Routing\TestClass::withParams', ['bolt']];
        // True because it is needs to be converted
        $this->assertTrue($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertCallable($callback);
        $this->assertSame('bolt', call_user_func($callback));
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
        return new CallbackResolver(new Container($services), $classmap);
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
