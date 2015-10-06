<?php
namespace Bolt\Tests\Twig;

use Bolt\Storage;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\TwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Class to test src/Library.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BoltTwigHelpersTest extends BoltUnitTest
{
    protected function tearDown()
    {
        parent::tearDown();
        VarDumper::setHandler(null);
    }

    public function testTwigInterface()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertGreaterThan(0, $twig->getFunctions());
        $this->assertGreaterThan(0, $twig->getFilters());
        $this->assertGreaterThan(0, $twig->getTests());
        $this->assertEquals('Bolt', $twig->getName());
    }

    public function testFileExists()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertTrue($twig->fileExists(__FILE__));

        // Test safe returns false
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, true);
        $this->assertFalse($twig->fileExists(__FILE__));
    }

    public function testPrintDump()
    {
        $this->stubVarDumper();
        $list = range(1, 10);

        // First safe test
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, true);
        $this->assertNull($twig->printDump($list));

        // Now test with debug off
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $app['debug'] = false;
        $this->assertNull($twig->printDump($list));

        // Now test with debug enabled
        $app = $this->getApp();
        $app['debug'] = true;
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertEquals($list, $twig->printDump($list));
    }

    public function testPrintBacktrace()
    {
        $this->stubVarDumper();

        // First test with debug off
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $app['debug'] = false;
        $this->assertNull($twig->printBacktrace());

        // Safe mode test
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, true);
        $this->assertNull($twig->printBacktrace());

        // Debug mode
        $app = $this->getApp();
        $app['debug'] = true;
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertNotEmpty($twig->printBacktrace());
    }

    public function testHtmlLang()
    {
        // Default Locale
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertEquals('en-GB', $twig->htmlLang());

        // Custom Locale de_DE
        $app = $this->getApp();
        $app['locale'] = 'de_DE';
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertEquals('de-DE', $twig->htmlLang());
    }

//     public function testLocaleDateTime()
//     {
//         // Default Locale
//         $app = $this->getApp();
//         $handlers = $this->getTwigHandlers($app);
//         $twig = new TwigExtension($app, $handlers, false);
//         $this->assertEquals('January  1, 2014 00:00', $twig->localeDateTime('1 Jan 2014'));

//         // Locale Switch
//         $app = $this->getApp();
//         $handlers = $this->getTwigHandlers($app);
//         $twig = new TwigExtension($app, $handlers, false);
//         setlocale(LC_ALL, 'fr_FR.UTF8');
//         $this->assertEquals('janvier  1, 2014 00:00', $twig->localeDateTime('1 Jan 2014'));

//     }

    public function testExcerpt()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $storage = new Storage($app);
        $content = $storage->getEmptyContent('showcases');
        $content->setValue('body', $this->getDummyText());
        $content->setValue('title', 'A Test Title');

        // First check on the raw string excerpt length 200 and ellipsis added
        $excerpt1 = $twig->excerpt($this->getDummyText());
        $this->assertEquals(200, mb_strlen($excerpt1, "UTF-8"));
        $this->assertEquals('…', mb_substr($excerpt1, -1, 1, 'UTF-8'));

        // If passed an object excerpt will try to call an excerpt() method on it
        $mock = $this->getMock('Bolt\Legacy\Content', ['excerpt'], [$app]);
        $mock->expects($this->any())
            ->method('excerpt')
            ->will($this->returnValue('called'));
        $excerpt2 = $twig->excerpt($mock);
        $this->assertEquals('called', $excerpt2);

        // If the object doesn't implement method, it should return false
        // Note: Current behaviour is that an ArrayObject will be treated as an array:
        // values are 'glued' together, and excerpt is created from that. If we change
        // that behaviour, the test below should be uncommented again.
//         $obj = new \ArrayObject(['info' => 'A test title', 'body' => $this->getDummyText()]);
//         $this->assertFalse($twig->excerpt($obj));

        // Check that array works.
        $sample = ['info' => 'A test title', 'body' => $this->getDummyText()];
        $excerpt4 = $twig->excerpt($sample);
        $this->assertRegExp('/' . $sample['info'] . '/', $excerpt4);

        // Check that non text returns empty
        $excerpt5 = $twig->excerpt(1);
        $this->assertEmpty($excerpt5);
    }

    public function testTrim()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $excerpt = $twig->trim($this->getDummyText());
        $this->assertEquals(200, mb_strlen($excerpt, 'UTF-8'));
    }

    public function testYmlLink()
    {
        $app = $this->getApp();
        $this->expectOutputRegex('#Redirecting to /bolt/#');
        $app->run();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $link = $twig->ymllink(' config.yml');
        $this->assertRegExp('#<a href="/bolt/file/edit/#', $link);

        // Test nothing happens in safe mode
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, true);
        $this->assertNull($twig->ymllink('config.yml'));
    }

    public function testImageInfo()
    {
        // Safe mode should return null
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, true);
        $this->assertNull($twig->imageInfo('nofile'));

        // Test on normal mode
        $app = $this->getApp();
        $app['resources']->setPath('files', PHPUNIT_ROOT . '/resources');
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $img = 'generic-logo.png';
        $info = $twig->imageInfo($img);
        $this->assertEquals(12, count($info));

        // Test non readable image fails
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertFalse($twig->imageInfo('nothing'));
    }

    public function testSlug()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertEquals('<h1>My <strong>big</strong> title</h1>', $twig->markdown("# My **big** title"));
    }

    public function testMarkdown()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertEquals('a-title-made-of-words', $twig->slug("A Title Made of Words"));
    }

    public function testTwig()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $snippet = 'Hello {{item}}';
        $this->assertEquals('Hello World', $twig->twig($snippet, ['item' => 'World']));
    }

    public function testDecorateTT()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertEquals('run: <tt>cat</tt>', $twig->decorateTT('run: `cat`'));
    }

    public function testOrder()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $input = [
            ['title' => 'Gamma', 'id' => 10, 'date' => '2014-01-19'],
            ['title' => 'Alpha', 'id' => 10, 'date' => '2014-02-20'],
            ['title' => 'Beta',  'id' => 8,  'date' => '2014-01-10'],
            ['title' => 'Delta', 'id' => 6,  'date' => '2014-01-19']
        ];
        $result = array_values($twig->order($input, 'id'));
        $this->assertEquals('Delta', $result[0]['title']);
        $this->assertEquals('Beta', $result[1]['title']);
        $this->assertRegExp('/Alpha|Gamma/', $result[2]['title']);

        // Test sort on secondary keys
        $result = array_values($twig->order($input, 'id', 'date'));
        $this->assertEquals('Gamma', $result[2]['title']);
    }

    public function testCurrent()
    {
        // Setup the db so we have a predictable content url to test
        $app = $this->getApp();
        $this->addDefaultUser($app);
        $storage = new Storage($app);
        $content = $storage->getEmptyContent('showcases');
        $content->setValues(['title' => 'New Showcase', 'slug' => 'new-showcase', 'status' => 'published']);
        $storage->saveContent($content);

        $phpunit = $this;
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $storage = new Storage($app);

        // Get the content object and create a routed request
        $request = Request::create('/showcase/new-showcase');
        $app->before(
            function ($request, $app) use ($phpunit, $twig, $storage) {
                $fetched = $storage->getContent('showcases/1');
                $phpunit->assertTrue($twig->current($fetched));
            }
        );
        $app->handle($request);

        // Test works on custom homepage
        $app['config']->set('general/homepage', 'showcase/new-showcase');
        $request = Request::create('/');
        $app->before(
            function ($request, $app) use ($phpunit, $twig, $storage) {
                $fetched = $storage->getContent('showcases/1');
                $phpunit->assertTrue($twig->current($fetched));
            }
        );
        $app->handle($request);

        // Delete the content so we're back to a clean database
        $storage->deleteContent('showcases', 1);
    }

    public function testToken()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $this->assertNotEmpty($twig->token());
    }

    public function testListTemplates()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        $templates = $twig->listTemplates();
        $this->assertNotEmpty($templates);

        $filtered = $twig->listTemplates('index*');
        $this->assertGreaterThan(0, count($filtered));

        // Test safe mode does nothing
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, true);
        $this->assertNull($twig->listTemplates());
    }

    public function testPager()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $twig = new TwigExtension($app, $handlers, false);
        // Test incomplete
        //$pager = $twig->pager($app['twig']);
    }

    protected function getDummyText($length = 1000)
    {
        $words = ['this', 'is', 'a', 'test', 'long', 'string', 'of', 'words', 'and', 'means', 'almost', 'nothing'];
        $longwords = range(1, $length);
        array_walk(
            $longwords,
            function (&$w) use ($words) {
                $w = $words[array_rand($words)];
            }
        );

        return implode(' ', $longwords);
    }

    /**
     * Override Symfony's default handler to get the output
     */
    protected function stubVarDumper()
    {
        VarDumper::setHandler(
            function ($var) {
                return $var;
            }
        );
    }
}
