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
        $file = "picture-of-kittens.jpg";
        $this->assertEquals('jpg', Library::getExtension($file));

        $empty = '/path/to/noext';
        $this->assertEquals('', Library::getExtension($empty));
    }

    public function testSafeFilename()
    {
        $abs = "/etc/passwd";
        $this->assertEquals('etc/passwd', Library::safeFilename($abs));

        // Test urlparams get encoded
        $urlparams = "%2F..%2F..%2Fsecretfile.txt";
        $this->assertEquals('%252F..%252F..%252Fsecretfile.txt', Library::safeFilename($urlparams));
    }

    public function testTemplateParser()
    {
        $app = $this->getApp();
        $loader = $app['twig.loader'];
        $app['twig']->render('error.twig', ['context' => [
            'class'   => 'BoltResponse',
            'message' => 'Clippy is bent out of shape',
            'code'    => '1555',
            'trace'   => []
        ]]);
        $templates = Library::parseTwigTemplates($loader);

        $this->assertEquals(1, count($templates));

        // Test deprecated function for now
        $this->assertEquals($templates, Library::hackislyParseRegexTemplates($loader));
    }

    public function testPath()
    {
        $app = $this->getApp();
        $app->run();
        $this->expectOutputRegex('#Redirecting to /bolt/#');

        $basic = "homepage";
        $this->assertEquals("/", Library::path($basic));

        $this->assertEquals(
            '/pages/content',
            Library::path(
                'contentlink',
                ['contenttypeslug' => 'pages', 'slug' => 'content']
            )
        );

        $query = "testing=yes";
        $this->assertEquals("/search?testing=yes", Library::path("search", [], $query));
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
        $request = Request::create("/");
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
        $this->expectOutputRegex("/Redirecting to/i");
        $redirect = Library::simpleredirect("/test", false);
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
        $this->expectOutputRegex("/Redirecting to/i");
        $redirect = Library::simpleredirect("", false);
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
        $redirect = Library::simpleredirect("/test2", true);
    }

    public function testSaveSerialize()
    {
        $data = range(0, 100);
        $file = PHPUNIT_ROOT . '/resources/data.php';
        $this->assertTrue(Library::saveSerialize($file, $data));
    }

    public function testSaveSerializeFailsOnLock()
    {
        $data = range(0, 100);
        $file = PHPUNIT_ROOT . '/resources/data.php';
        $fp = fopen($file, 'a');
        flock($fp, LOCK_EX);
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/Could not lock/i");
        $response = Library::saveSerialize($file, $data);
    }

    public function testSaveSerializeErrors()
    {
        $data = range(0, 100);
        $file = TEST_ROOT . '/non/existent/path/data.php';
        $this->setExpectedException('PHPUnit_Framework_Error_Warning');
        $response = Library::saveSerialize($file, $data);
        $this->assertTrue($response);
    }

    public function testLoadSerialize()
    {
        $file = PHPUNIT_ROOT . '/resources/data.php';
        $data = Library::loadSerialize($file);
        $this->assertEquals(range(0, 100), $data);
        unlink($file);
    }

    public function testLoadSerializeErrors()
    {
        $file = TEST_ROOT . '/non/existent/path/data.php';
        $this->setExpectedException('Bolt\Exception\LowlevelException');
        $this->expectOutputRegex("/File is not readable/i");
        $this->assertTrue(Library::loadSerialize($file));
    }

    public function testLoadSerializeErrorsSilently()
    {
        $file = TEST_ROOT . '/non/existent/path/data.php';
        $this->assertFalse(Library::loadSerialize($file, true));
    }

    public function testSmartUnserialize()
    {
        $json = json_encode(range(1, 100));
        $this->assertEquals(Library::smartUnserialize($json), range(1, 100));

        $php = serialize(range(1, 100));
        $this->assertEquals(Library::smartUnserialize($php), range(1, 100));
    }

    public function testLegacyLoadSerialize()
    {
        $file = PHPUNIT_ROOT . '/resources/data.php';
        file_put_contents($file, serialize(range(1, 100)));
        $data = Library::loadSerialize($file);
        $this->assertEquals(range(1, 100), $data);
        unlink($file);
    }

    public function testLegacyLoadSerializeWithWindowsNewlines()
    {
        $file = PHPUNIT_ROOT . '/resources/data.php';
        $data = "\r\n" . serialize('string');
        file_put_contents($file, $data);
        $data = Library::loadSerialize($file);
        $this->assertEquals("string", $data);
        unlink($file);
    }

    public function testLegacyLoadSerializeMixedNewlines()
    {
        $file = PHPUNIT_ROOT . '/resources/data.php';
        $data = "\n\n" . serialize('string');
        file_put_contents($file, $data);
        $data = Library::loadSerialize($file);
        $this->assertEquals("string", $data);
        unlink($file);
    }

    public function testBadLoadSerializeFails()
    {
        $file = PHPUNIT_ROOT . '/resources/data.php';
        $data = "\n\n" . 'string';
        file_put_contents($file, $data);
        $data = Library::loadSerialize($file);
        $this->assertFalse($data);
        unlink($file);
    }
}
