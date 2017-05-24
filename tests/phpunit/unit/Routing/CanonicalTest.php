<?php

namespace Bolt\Tests\Routing;

use Bolt\Routing\Canonical;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Test for Canonical class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class CanonicalTest extends TestCase
{
    public function urlProvider()
    {
        return [
            'http'  => ['/foo', 'http://localhost/foo'],
            'https' => ['https://localhost/foo', 'https://localhost/foo'],
        ];
    }

    /**
     * @dataProvider urlProvider
     *
     * @param string $uri
     * @param string $expected
     */
    public function testUrl($uri, $expected)
    {
        $app = $this->createAppWithCanonical();

        $this->assertCanonicalUrlInKernel($app, $uri, $expected);
    }

    public function globalOverrideProvider()
    {
        return [
            'only host (http)'       => ['/foo', 'http://bar.com/foo', 'bar.com'],
            'only host (https)'      => ['https://localhost/foo', 'https://bar.com/foo', 'bar.com'],

            'only host and http scheme (http)'  => ['/foo', 'http://bar.com/foo', 'http://bar.com'],
            'only host and http scheme (https)' => ['https://localhost/foo', 'https://bar.com/foo', 'http://bar.com'],

            'only host and https scheme (http)'  => ['/foo', 'https://bar.com/foo', 'https://bar.com'],
            'only host and https scheme (https)' => ['https://localhost/foo', 'https://bar.com/foo', 'https://bar.com'],
        ];
    }

    /**
     * @dataProvider globalOverrideProvider
     *
     * @param string $uri
     * @param string $expected
     * @param string $globalOverride
     */
    public function testGlobalOverrideWithConstructor($uri, $expected, $globalOverride)
    {
        $app = $this->createAppWithCanonical($globalOverride);

        $this->assertCanonicalUrlInKernel($app, $uri, $expected);
    }

    /**
     * @dataProvider globalOverrideProvider
     *
     * @param string $uri
     * @param string $expected
     * @param string $globalOverride
     */
    public function testGlobalOverrideWithSetter($uri, $expected, $globalOverride)
    {
        $app = $this->createAppWithCanonical(null);
        $app['canonical']->setGlobalOverride($globalOverride);

        $this->assertCanonicalUrlInKernel($app, $uri, $expected);
    }

    public function overrideProvider()
    {
        return [
            'absolute url'                                  => ['http://foo.com/bar', null, false, 'http://foo.com/bar'],
            'absolute url (force ssl does not apply)'       => ['http://foo.com/bar', null, true, 'http://foo.com/bar'],
            'absolute url (global override does not apply)' => ['http://foo.com/bar', 'baz.com', false, 'http://foo.com/bar'],

            'network url'                                  => ['//foo.com/bar', null, false, '//foo.com/bar'],
            'network url (force ssl does not apply)'       => ['//foo.com/bar', null, true, '//foo.com/bar'],
            'network url (global override does not apply)' => ['//foo.com/bar', 'baz.com', false, '//foo.com/bar'],

            'absolute path'                           => ['/bar', null, false, 'http://localhost/base/bar'],
            'absolute path (force ssl applies)'       => ['/bar', null, true, 'https://localhost/base/bar'],
            'absolute path (global override applies)' => ['/bar', 'baz.com', false, 'http://baz.com/base/bar'],

            'relative path'                           => ['bar', null, false, 'http://localhost/base/foo/bar'],
            'relative path (force ssl applies)'       => ['bar', null, true, 'https://localhost/base/foo/bar'],
            'relative path (global override applies)' => ['bar', 'baz.com', false, 'http://baz.com/base/foo/bar'],
        ];
    }

    /**
     * @dataProvider overrideProvider
     *
     * @param string $override
     * @param string $globalOverride
     * @param string $forceSsl
     * @param string $expected
     */
    public function testOverride($override, $globalOverride, $forceSsl, $expected)
    {
        $app = $this->createAppWithCanonical($globalOverride, $forceSsl);

        $app->after(function () use ($app, $override) {
            $app['canonical']->setOverride($override);
        });

        $request = Request::create('/base/foo', 'GET', [], [], [], [
            'PHP_SELF'        => '/base/index.php',
            'SCRIPT_FILENAME' => '/base/index.php',
        ]);

        $this->assertCanonicalUrlInKernel($app, $request, $expected);
    }

    public function testNullOutsideRequestCycle()
    {
        $app = $this->createAppWithCanonical();

        $url = $app['canonical']->getUrl();

        $this->assertNull($url);
    }

    public function testNullForUnmatchedRoute()
    {
        $app = $this->createAppWithCanonical();

        $app['dispatcher']->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) use ($app) {
            $app['canonical']->onRequest($event);
            $event->stopPropagation();
        }, 1000);

        $this->assertCanonicalUrlInKernel($app, '/unmatched', null);
    }

    protected function createAppWithCanonical($globalOverride = null, $forceSsl = false)
    {
        $app = new Application();
        $app->register(new UrlGeneratorServiceProvider());
        $app->match('/foo');

        $canonical = new Canonical($app['url_generator'], $forceSsl, $globalOverride);
        $canonical->setUrlGenerator($app['url_generator']);
        $app['canonical'] = $canonical;

        $app['dispatcher']->addSubscriber($canonical);

        return $app;
    }

    /**
     * @param Application    $app
     * @param string|Request $uri
     * @param string         $expected
     */
    protected function assertCanonicalUrlInKernel(Application $app, $uri, $expected)
    {
        $url = null;
        $app->after(
            function () use (&$url, $app) {
                $url = $app['canonical']->getUrl();
            }
        );

        $request = $uri instanceof Request ? $uri : Request::create($uri);

        $app->handle($request);

        $this->assertEquals($expected, $url);

        $this->assertNull($app['canonical']->getUrl(), 'Canonical url from previous request should be reset.');
    }
}
