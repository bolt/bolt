<?php

namespace Bolt\Tests\Pager;

use Bolt\Pager\Pager;
use Bolt\Pager\PagerManager;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class PagerManagerTestBase extends BoltUnitTest
{
    protected function createPagerManager()
    {
        return new PagerManager();
    }

    protected function createPagerManagerMockBuilder()
    {
        $mockBuilder = $this
            ->getMockBuilder('Bolt\\Pager\\PagerManager');

        return $mockBuilder;
    }

    protected function initApp($request = null)
    {
        $app = $this->getApp();
        $app['request'] = $request;
        $app['request_stack'] = new RequestStack();

        if ($request) {
            $app['request_stack']->push($request);
        }

        return $app;
    }

    protected function &getProtectedAttrRef($object, $attrName)
    {
        $writer = \Closure::bind(
            function &($object, $attrName) {
                return $object->$attrName;
            },
            null,
            $object
        );

        return $writer($object, $attrName);
    }

    protected function methodInvoker($object, $method, array $args = [])
    {
        $closure = function ($method, $args) {
            return call_user_func_array([$this, $method], $args);
        };
        $invoker = $closure->bindTo($object, $object);

        return $invoker($method, $args);
    }

    protected function createPager($array = [])
    {
        $pager = new Pager();

        foreach ($array as $key => $item) {
            $pager->$key = $item;
        }

        return $pager;
    }
}
