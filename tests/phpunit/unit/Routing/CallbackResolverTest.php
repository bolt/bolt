<?php

namespace Bolt\Tests\Routing;

use Bolt\Routing\CallbackResolver;
use Bolt\Tests\BoltUnitTest;

class CallbackResolverTest extends BoltUnitTest
{
    public function testService()
    {
        $test = new TestClass();
        $resolver = $this->resolver(array(), array(
            'test.class' => function () use ($test) {
                return $test;
            },
        ));
        $str = 'test.class:foo';
        $callback = $resolver->resolveCallback($str);
        $this->assertCallback($callback, $test);
    }

    public function testStringInClassMap()
    {
        $test = new TestClass();
        $resolver = $this->resolver(array(
            'Bolt\Tests\Routing\TestClass' => 'test.class',
        ), array(
            'test.class' => $test,
        ));
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

    public function testArrayInClassMap()
    {
        $test = new TestClass();
        $resolver = $this->resolver(array(
            'Bolt\Tests\Routing\TestClass' => 'test.class',
        ), array(
            'test.class' => $test,
        ));
        $arr = array('Bolt\Tests\Routing\TestClass', 'foo');
        $callback = $resolver->resolveCallback($arr);
        $this->assertCallback($callback, $test);
    }

    public function testArrayNotInClassMap()
    {
        $arr = array('Bolt\Tests\Routing\TestClass', 'foo');
        // True because it is needs to be converted
        $this->assertTrue($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertCallback($callback);
    }
    public function testArrayNotInClassMapAndStatic()
    {
        $arr = array('Bolt\Tests\Routing\TestClass', 'staticFoo');
        // False because it is already valid
        $this->assertFalse($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertCallback($callback);
    }

    public function testArrayNonExistentClassNotInClassMapFails()
    {
        $arr = array('Bolt\Tests\Routing\TestClassDerp', 'staticFoo');
        // False because it is invalid
        $this->assertFalse($this->resolver()->isValid($arr));

        $callback = $this->resolver()->resolveCallback($arr);
        $this->assertNotCallable($callback);
    }

    public function testArrayWithObject()
    {
        $arr = array(new TestClass(), 'foo');
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

    protected function resolver($classmap = array(), $services = array())
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
}
