<?php
namespace Bolt\Tests\Library;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\Library;
use Bolt\Configuration\ResourceManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Class to test src/Library.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class BoltLibraryTest extends BoltUnitTest
{


    public function testFixPath()
    {
        $windowsPath = "A:\My\File";
        $fix = Library::fixPath($windowsPath);
        $this->assertEquals("A:/My/File", $fix);
        
        $protocolPath = "//path/to\image.jpg";
        $fix = Library::fixPath($protocolPath, false);
        $this->assertEquals("//path/to/image.jpg", $fix);
        
        $relative = "/path/to/nested/nested/../../image.jpg";
        $fix = Library::fixPath($relative);
        $this->assertEquals("/path/to/image.jpg", $fix);
        
        $relativeUrl = "http://path/to/nested/nested/../../image.jpg";
        $fix = Library::fixPath($relativeUrl, false);
        $this->assertEquals("http://path/to/image.jpg", $fix);
        
        $relativeSecureUrl = "https://path/to/nested/nested/../../image.jpg";
        $fix = Library::fixPath($relativeSecureUrl, false);
        $this->assertEquals("https://path/to/image.jpg", $fix);

    }
    
    public function testFormatFilesize()
    {
        $b = 300;
        $fix = Library::formatFilesize($b);
        $this->assertEquals('300 b', $fix);
        
        $k = 1027;
        $fix = Library::formatFilesize($k);
        $this->assertEquals('1.00 kb', $fix);
        
        $m = 1048577;
        $fix = Library::formatFilesize($m);
        $this->assertEquals('1.00 mb', $fix);
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
        $template = $app['twig']->render('error.twig');
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
            Library::path('contentlink', array('contenttypeslug'=>'pages', 'slug'=>'content')
        ));
        
        $query = "testing=yes";
        $this->assertEquals("/search?testing=yes", Library::path("search", array(), $query));

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
        $response = Library::redirect('contentlink', array('contenttypeslug'=>'pages', 'slug'=>'content'));
        $this->assertEquals('/pages/content', $response->headers->get('Location'));
    }
    
    public function testRedirectLocation()
    {
        $app = $this->getApp();
        $request = Request::create("/");
        $app->handle($request);
        $app['request'] = $request;
        
        $response = Library::redirect('login');
        $this->assertNotEmpty($app['session']->get('retreat'));
        $this->assertEquals('homepage', $app['session']->get('retreat')['route']);

    }

    /**
     * @runInSeparateProcess
     */    
    public function testSimpleRedirect()
    {
        $app = $this->getApp();
        $redirect = Library::simpleredirect("/test", false);
        $this->assertEquals( array( 'location: /test' ), xdebug_get_headers() );
    }
    
    /**
     * @runInSeparateProcess
     */    
    public function testSimpleRedirectEmpty()
    {
        $app = $this->getApp();
        $redirect = Library::simpleredirect("", false);
        $this->assertEquals( array( 'location: /' ), xdebug_get_headers() );
    }
    
    /**
     * @runInSeparateProcess
     */    
    public function testSimpleRedirectAbort()
    {
        $app = $this->getApp();
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException', "Redirecting to '/test2'.");
        $redirect = Library::simpleredirect("/test2");    
    }
    
    public function testSaveSerialize()
    {
        $data = range(0,100);
        $file = TEST_ROOT."/tests/resources/data.php";
        $this->assertTrue(Library::saveSerialize($file, $data));
    }
    
    public function testSaveSerializeFailsOnLock()
    {
        $data = range(0,100);
        $file = TEST_ROOT."/tests/resources/data.php";
        $fp = fopen($file, 'a');
        flock($fp, LOCK_EX);
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\HttpException');
        $this->assertTrue(Library::saveSerialize($file, $data));
    }
    
    public function testSaveSerializeErrors()
    {
        $data = range(0,100);
        $file = TEST_ROOT."/non/existent/path/data.php";
        $this->setExpectedException('ErrorException');
            
        $this->assertTrue(Library::saveSerialize($file, $data));
    }
    
    public function testLoadSerialize()
    {
        $file = TEST_ROOT."/tests/resources/data.php";
        $data = Library::loadSerialize($file);
        $this->assertEquals(range(0,100), $data);
        unlink($file);
    }
    
    public function testLoadSerializeErrors()
    {
        $file = TEST_ROOT."/non/existent/path/data.php"; 
        $this->setExpectedException('Bolt\Configuration\LowlevelException');
        $this->expectOutputRegex("/File is not readable/i");          
        $this->assertTrue(Library::loadSerialize($file));
    }
    
    public function testLoadSerializeErrorsSilently()
    {
        $file = TEST_ROOT."/non/existent/path/data.php";          
        $this->assertFalse(Library::loadSerialize($file, true));
    }
    
    public function testSmartUnserialize()
    {
        $json = json_encode(range(1,100));
        $this->assertEquals(Library::smart_unserialize($json), range(1,100));
        
        $php = serialize(range(1,100));
        $this->assertEquals(Library::smart_unserialize($php), range(1,100));
    }
    
    public function testLegacyLoadSerialize()
    {
        $file = TEST_ROOT."/tests/resources/data.php";
        file_put_contents($file, serialize(range(1,100)));
        $data = Library::loadSerialize($file);
        $this->assertEquals(range(1,100), $data);
        unlink($file);
    }
    
    public function testLegacyLoadSerializeWithWindowsNewlines()
    {
        $file = TEST_ROOT."/tests/resources/data.php";
        $data = "\r\n".serialize("string");
        file_put_contents($file, $data);
        $data = Library::loadSerialize($file);
        $this->assertEquals("string", $data);
        unlink($file);
    }
    
    public function testLegacyLoadSerializeMixedNewlines()
    {
        $file = TEST_ROOT."/tests/resources/data.php";
        $data = "\n\n".serialize("string");
        file_put_contents($file, $data);
        $data = Library::loadSerialize($file);
        $this->assertEquals("string", $data);
        unlink($file);
    }
    
    public function testBadLoadSerializeFails()
    {
        $file = TEST_ROOT."/tests/resources/data.php";
        $data = "\n\n"."string";
        file_put_contents($file, $data);
        $data = Library::loadSerialize($file);
        $this->assertFalse($data);
        unlink($file);
    }
    
    
    
   
}