<?php
namespace Bolt\Tests\Provider;

use Bolt\Provider\PagerServiceProvider;
use Bolt\Tests\BoltFunctionalTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PagerServiceProviderTest
 *
 * @package Bolt\Tests\Provider
 *
 * @author Rix Beck <rix@neologik.hu>
 */
class PagerServiceProviderTest extends BoltFunctionalTestCase
{
    public function testProvider()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $provider = new PagerServiceProvider($app);
        $app->register($provider);

        $this->assertInstanceOf('Bolt\Pager\PagerManager', $app['pager']);

        $app->boot();
    }
}
