<?php
namespace Bolt\Tests\Twig;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\TwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Bolt\Storage;

/**
 * Class to test src/Library.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class BoltTwigHelpersTest extends BoltUnitTest
{
    
    public function testTwigInterface()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertGreaterThan(0, $twig->getFunctions());
        $this->assertGreaterThan(0, $twig->getFilters());
        $this->assertGreaterThan(0, $twig->getTests());
        $this->assertEquals("Bolt", $twig->getName());
    }
    
    public function testFileExists()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertTrue($twig->fileExists(__FILE__));
        
        // Test safe returns false
        $app = $this->getApp();
        $twig = new TwigExtension($app, true);
        $this->assertFalse($twig->fileExists(__FILE__));
    }
    
    public function testPrintDump()
    {
        // First safe test
        $app = $this->getApp();
        $twig = new TwigExtension($app, true);
        $this->assertEquals('?', $twig->printDump(range(1,10)));
        
        // Now test with debug off
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $app['config']->set('general/debug', false);
        $this->assertEquals('', $twig->printDump(range(1,10)));
        
        // Now test with debug enabled
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertRegExp('/dumper-root/', $twig->printDump(range(1,10)));
    }
    
    public function testPrintBacktrace()
    {
        // First test with debug off
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $app['config']->set('general/debug', false);
        $this->assertEmpty($twig->printBacktrace());
        
        // Safe mode test
        $app = $this->getApp();
        $twig = new TwigExtension($app, true);
        $this->assertEmpty($twig->printBacktrace());
        
        // Debug mode
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertNotEmpty($twig->printBacktrace(2));
    }
    
    public function testHtmlLang()
    {
        // Default Locale
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertEquals('en-GB', $twig->htmlLang());
        
        // Custom Locale de_DE
        $app = $this->getApp();
        $app['config']->set('general/locale', 'de_DE');
        $twig = new TwigExtension($app);
        $this->assertEquals('de-DE', $twig->htmlLang());
        
    }
    
    public function testLocaleDateTime()
    {
        // Default Locale
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertEquals('January  1, 2014 00:00', $twig->localeDateTime('1 Jan 2014'));
        
        // Locale Switch
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        setlocale(LC_ALL, 'fr_FR');
        $this->assertEquals('janvier  1, 2014 00:00', $twig->localeDateTime('1 Jan 2014'));

    }
    
    public function testExcerpt()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $storage = new Storage($app);
        $content = $storage->getEmptyContent('showcases');
        $content->setValue('body', $this->getDummyText());
        $content->setValue('title', 'A Test Title');

        // First check on the raw string excerpt length 200 and ellipsis added
        $excerpt1 = $twig->excerpt($this->getDummyText());
        $this->assertEquals(200, mb_strlen($excerpt1));
        $this->assertEquals('…', mb_substr($excerpt1, -1));
        
        // If passed an object exceprt will try to call an excerpt() method on it
        $mock = $this->getMock('Bolt\Content', array('excerpt'), array($app));
        $mock->expects($this->any())
            ->method('excerpt')
            ->will($this->returnValue('called'));
        $excerpt2 = $twig->excerpt($mock);
        $this->assertEquals('called', $excerpt2);
        
        // If the object doesn't implement method, it should return false
        $obj = new \ArrayObject(array('info'=>'A test title', 'body'=>$this->getDummyText()) );
        $this->assertFalse($twig->excerpt($obj));
        
        // Check that array works.
        $sample = array('info'=>'A test title', 'body'=>$this->getDummyText());
        $excerpt4 = $twig->excerpt($sample);
        $this->assertRegExp('/'.$sample['info'].'/', $excerpt4);
        
        // Check that non text returns empty
        $excerpt5 = $twig->excerpt(1);
        $this->assertEmpty($excerpt5);
    }
    
    public function testTrim()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $excerpt = $twig->trim($this->getDummyText());
        $this->assertEquals(200, mb_strlen($excerpt));
    }
    
    public function testYmlLink()
    {
        $app = $this->getApp();
        $this->expectOutputRegex('#Redirecting to /bolt/#');
        $app->run();
        $twig = new TwigExtension($app, false);
        $link = $twig->ymllink(' config.yml');
        $this->assertRegExp('#<a href=\'/bolt/file/edit/#', $link);
        
        // Test nothing happens in safe mode
        $app = $this->getApp();
        $twig = new TwigExtension($app, true);
        $this->assertNull($twig->ymllink('config.yml'));
    }
    
    public function testImageInfo()
    {
        // Safe mode should return null
        $app = $this->getApp();
        $twig = new TwigExtension($app, true);
        $this->assertNull($twig->imageInfo('nofile'));
        
        // Test on normal mode
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $img = '../generic-logo.png';
        $info = $twig->imageInfo($img);
        $this->assertEquals(11, count($info));
        
        // Test non readable image fails
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertFalse($twig->imageInfo('nothing'));
        
    }
    
    public function testSlug()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertEquals('<h1>My <strong>big</strong> title</h1>', $twig->markdown("# My **big** title"));
    }
    
    public function testMarkdown()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertEquals('a-title-made-of-words', $twig->slug("A Title Made of Words"));
    }
    
    public function testTwig()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $snippet = 'Hello {{item}}';
        $this->assertEquals('Hello World', $twig->twig($snippet, array('item'=>'World')));
    }
    
    public function testDecorateTT()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertEquals('run: <tt>cat</tt>', $twig->decorateTT('run: `cat`'));
    }
    
    public function testUcfirst()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertEquals('Test this', $twig->ucfirst('test this'));
    }
    
    public function testOrder()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $input = array(
            array('title'=>'Gamma', 'id'=>10, 'date'=>'2014-01-19'),  
            array('title'=>'Alpha', 'id'=>10, 'date'=>'2014-02-20'),    
            array('title'=>'Beta', 'id'=>8, 'date'=>'2014-01-10'),    
            array('title'=>'Delta', 'id'=>6, 'date'=>'2014-01-19') 
        );
        $result = array_values($twig->order($input, 'id'));
        $this->assertEquals('Delta', $result[0]['title']);
        $this->assertEquals('Beta', $result[1]['title']);
        $this->assertEquals('Alpha', $result[2]['title']);
        
        // Test sort on secondary keys
        $result = array_values($twig->order($input, 'id', 'date'));
        $this->assertEquals('Gamma', $result[2]['title']);
        
        
    }
    
    public function testFirst()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertEquals(1, $twig->first(array(1,2,3,4)));
        $this->assertFalse($twig->first(1));
    }
    
    public function testLast()
    {
        $app = $this->getApp();
        $twig = new TwigExtension($app);
        $this->assertEquals(4, $twig->last(array(1,2,3,4)));
        $this->assertFalse($twig->last(1));
    }
    
    
    public function testCurrent()
    {
        // Setup the db so we have a predictable content url to test
        $this->addDefaultUser();
        $app = $this->getApp();
        $storage = new Storage($app);        
        $content = $storage->getEmptyContent('showcases');
        $content->setValues(array('title'=>'New Showcase','slug'=>'new-showcase','status'=>'published'));
        $storage->saveContent($content);
        
        
        $phpunit = $this;
        $twig = new TwigExtension($app);
        $storage = new Storage($app);
        
        // Get the content object and create a routed request
        $request = Request::create('/showcase/new-showcase');     
        $app->before(function($request, $app) use($phpunit, $twig, $storage){
            $fetched = $storage->getContent('showcases/1');
            $phpunit->assertTrue($twig->current($fetched));
        });
        $app->handle($request);
        
        
        // Test works on custom homepage
        $app['config']->set('general/homepage', 'showcase/new-showcase');
        $request = Request::create('/');
        $app->before(function($request, $app) use($phpunit, $twig, $storage){
            $fetched = $storage->getContent('showcases/1');
            $phpunit->assertTrue($twig->current($fetched));
        });
        $app->handle($request);
        
    }
    
    public function testToken()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $twig = new TwigExtension($app);
        $this->assertNotEmpty($twig->token());
    }
    
    protected function getDummyText($length = 1000)
    {
        $words = array('this', 'is', 'a', 'test', 'long', 'string', 'of', 'words', 'and', 'means', 'almost', 'nothing');
        $longwords = range(1,$length);
        array_walk($longwords, function(&$w) use($words){$w=$words[array_rand($words)];});
        return implode(' ', $longwords);
    }
    
    
    
    
}