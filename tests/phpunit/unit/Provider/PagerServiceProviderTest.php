<?php

namespace Bolt\Tests\Provider;

use Bolt\Pager\PagerManager;
use Bolt\Provider\PagerServiceProvider;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PagerServiceProviderTest
 *
 * @package Bolt\Tests\Provider
 *
 * @author Rix Beck <rix@neologik.hu>
 */
class PagerServiceProviderTest extends BoltUnitTest
{
    public function testProvider()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $provider = new PagerServiceProvider($app);
        $app->register($provider);

        $this->assertInstanceOf(PagerManager::class, $app['pager']);

        $app->boot();
    }
}
