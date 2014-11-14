<?php
namespace Bolt\Tests\Twig;

use Bolt\Application;
use Bolt\Tests\BoltUnitTest;
use Bolt\TwigExtension;
use Symfony\Component\HttpFoundation\Request;
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
        
        // If the object doesn't implement method, it should return empty
        $excerpt3 = $twig->excerpt($content);
        
    }
    
    
    protected function getDummyText($length = 1000)
    {
        $words = array('this', 'is', 'a', 'test', 'long', 'string', 'of', 'words', 'and', 'means', 'almost', 'nothing');
        $longwords = range(1,$length);
        array_walk($longwords, function(&$w) use($words){$w=$words[array_rand($words)];});
        return implode(' ', $longwords);
    }
    
    
    
    
}