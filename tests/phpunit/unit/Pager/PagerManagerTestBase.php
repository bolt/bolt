<?php
/**
 * Created by PhpStorm.
 * User: rix
 * Date: 2015.12.29.
 * Time: 22:25
 */

namespace Bolt\Tests\Pager;


use Bolt\Pager\PagerManager;
use Bolt\Tests\BoltUnitTest;

abstract class PagerManagerTestBase extends BoltUnitTest
{
    protected function createPagerManager($request)
    {
        $app = $this->initApp($request);

        return new PagerManager($app);
    }

    protected function createPagerManagerMockBuilder($request = null)
    {
        $app = $this->initApp($request);
        $mockBuilder = $this
            ->getMockBuilder('Bolt\\Pager\\PagerManager')
            ->setConstructorArgs([$app]);

        return $mockBuilder;
    }

    protected function initApp($request)
    {
        $app = $this->getApp();
        $app['request'] = $request;

        return $app;
    }
}
