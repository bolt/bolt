<?php
namespace Bolt\Tests\Library;

use Bolt\Library;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Library.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BoltLibraryTest extends BoltUnitTest
{
    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testFormatFilesize()
    {
        $b = 300;
        $fix = Library::formatFilesize($b);
        $this->assertEquals('300 B', $fix);

        $k = 1027;
        $fix = Library::formatFilesize($k);
        $this->assertEquals('1.00 KiB', $fix);

        $m = 1048577;
        $fix = Library::formatFilesize($m);
        $this->assertEquals('1.00 MiB', $fix);
    }

    public function testGetExtension()
    {
        $file = 'picture-of-kittens.jpg';
        $this->assertEquals('jpg', Library::getExtension($file));

        $empty = '/path/to/noext';
        $this->assertEquals('', Library::getExtension($empty));
    }

    public function testSafeFilename()
    {
        $abs = '/etc/passwd';
        $this->assertEquals('etc/passwd', Library::safeFilename($abs));

        // Test urlparams get encoded
        $urlparams = '%2F..%2F..%2Fsecretfile.txt';
        $this->assertEquals('%252F..%252F..%252Fsecretfile.txt', Library::safeFilename($urlparams));
    }

    public function testPath()
    {
        $app = $this->getApp();
        $app->run();
        $this->expectOutputRegex('#Redirecting to /bolt/#');

        $basic = 'homepage';
        $this->assertEquals('/', Library::path($basic));

        $this->assertEquals(
            '/pages/content',
            Library::path(
                'contentlink',
                ['contenttypeslug' => 'pages', 'slug' => 'content']
            )
        );

        $query = 'testing=yes';
        $this->assertEquals('/search?testing=yes', Library::path('search', [], $query));
    }

    public function testRedirect()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $app->handle($request);
        $response = Library::redirect('search');
        $this->assertEquals('/search', $response->headers->get('Location'));
    }

    public function testRedirectWithParameters()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $app->handle($request);
        $response = Library::redirect('contentlink', ['contenttypeslug' => 'pages', 'slug' => 'content']);
        $this->assertEquals('/pages/content', $response->headers->get('Location'));
    }

    public function testRedirectLocation()
    {
        $app = $this->getApp();
        $request = Request::create('/');
        $app->handle($request);
        $app['request'] = $request;

        $response = Library::redirect('login');
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\RedirectResponse', $response);
        $this->assertRegExp('|Redirecting to /bolt/login|', $response->getContent());
        $this->assertTrue($response->isRedirect(), "Response isn't a valid redirect condition.");
    }

    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testSimpleRedirect()
    {
        if (phpversion('xdebug') === false) {
            $this->markTestSkipped('No xdebug support enabled.');
        }

        $app = $this->getApp();
        $this->expectOutputRegex('/Redirecting to/i');
        $redirect = Library::simpleredirect('/test', false);
        $this->assertContains('location: /test', xdebug_get_headers());
    }

    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testSimpleRedirectEmpty()
    {
        if (phpversion('xdebug') === false) {
            $this->markTestSkipped('No xdebug support enabled.');
        }

        $app = $this->getApp();
        $this->expectOutputRegex('/Redirecting to/i');
        $redirect = Library::simpleredirect('', false);
        $this->assertContains('location: /', xdebug_get_headers());
    }

    /**
     * @runInSeparateProcess
     */
    public function testSimpleRedirectAbort()
    {
        $app = $this->getApp();
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', "Redirecting to '/test2'.");
        $this->expectOutputString("<p>Redirecting to <a href='/test2'>/test2</a>.</p><script>window.setTimeout(function () { window.location='/test2'; }, 500);</script>");
        $redirect = Library::simpleredirect('/test2', true);
    }
}
