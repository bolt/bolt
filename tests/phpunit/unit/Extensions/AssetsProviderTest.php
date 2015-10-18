<?php
namespace Bolt\Tests\Extensions;

use Bolt\Asset\Target;
use Bolt\Extensions;
use Bolt\Storage\Entity;

/**
 * Class to test correct operation and locations of assets provider.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AssetsProviderTest extends AbstractExtensionsUnitTest
{
    public $template = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedCss = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="testfile.css?v=5e544598b8d78644071a6f25fd8bba82" media="screen">
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedLateCss = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
<link rel="stylesheet" href="testfile.css?v=5e544598b8d78644071a6f25fd8bba82" media="screen">
</body>
</html>
HTML;

    public $expectedJs = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
<script src="testfile.js?v=289fc946f38fee1a3e947eca1d6208b6"></script>
</body>
</html>
HTML;

    public $expectedLateJs = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
<script src="testfile.js?v=289fc946f38fee1a3e947eca1d6208b6"></script>
</body>
</html>
HTML;

    public $expectedStartOfHead = <<<HTML
<html>
<head>
<meta name="test-snippet" />
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedEndOfHead = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
<meta name="test-snippet" />
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedStartOfBody = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<p class="test-snippet"></p>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedEndOfHtml = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
<p class="test-snippet"></p>
</html>
HTML;

    public $expectedBeforeCss = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<meta name="test-snippet" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedAfterCss = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
<meta name="test-snippet" />
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    public $expectedAfterMeta = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<meta name="test-snippet" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    protected function getApp($boot = true)
    {
        $app = parent::getApp();
        $app['asset.file.hash.factory'] = $app->protect(function ($fileName) {
            return md5($fileName);
        });

        return $app;
    }

    public function testBadExtensionSnippets()
    {
        $app = $this->getApp();
        $logger = $this->getMock('\Monolog\Logger', ['critical'], ['testlogger']);
        $logger->expects($this->atLeastOnce())
            ->method('critical')
            ->will($this->returnCallback(function ($message) {
                    \PHPUnit_Framework_Assert::assertSame(
                        'Snippet loading failed for Bolt\Tests\Extensions\Mock\BadExtensionSnippets with callable a:2:{i:0;O:47:"Bolt\Tests\Extensions\Mock\BadExtensionSnippets":0:{}i:1;s:18:"badSnippetCallBack";}',
                        $message);
                }
            ))
        ;
        $app['asset.queue.snippet'] = new \Bolt\Asset\Snippet\Queue(
            $app['asset.injector'],
            $app['cache'],
            $app['config'],
            $app['resources'],
            $app['request_stack'],
            $logger
        );
        $app['extensions']->register(new Mock\BadExtensionSnippets($app));

        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->template), $this->html($html));
    }

    public function testAddCss()
    {
        $app = $this->getApp();
        $app['asset.queue.file']->add('stylesheet', 'testfile.css');
        $assets = $app['asset.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['stylesheet']));
    }

    public function testAddJs()
    {
        $app = $this->getApp();
        $app['asset.queue.file']->add('javascript', 'testfile.js');
        $assets = $app['asset.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['javascript']));
    }

    public function testEmptyProcessAssetsFile()
    {
        $app = $this->getApp();
        $html = $app['asset.queue.file']->process('html');
        $this->assertEquals('html', $html);
    }

    public function testEmptyProcessAssetsSnippets()
    {
        $app = $this->getApp();
        $html = $app['asset.queue.snippet']->process('html');
        $this->assertEquals('html', $html);
    }

    public function testJsProcessAssets()
    {
        $app = $this->getApp();
        $app['asset.queue.file']->add('javascript', 'testfile.js');
        $html = $app['asset.queue.file']->process($this->template);
        $this->assertEquals($this->html($this->expectedJs), $this->html($html));
    }

    public function testLateJs()
    {
        $app = $this->getApp();
        $app['asset.queue.file']->add('javascript', 'testfile.js', ['late' => true]);
        $html = $app['asset.queue.file']->process($this->template);
        $this->assertEquals($this->html($this->expectedLateJs),  $this->html($html));
    }

    public function testCssProcessAssets()
    {
        $app = $this->getApp();
        $app['asset.queue.file']->add('stylesheet', 'testfile.css');
        $html = $app['asset.queue.file']->process($this->template);
        $this->assertEquals($this->html($this->expectedCss), $this->html($html));
    }

    public function testLateCss()
    {
        $app = $this->getApp();
        $app['asset.queue.file']->add('stylesheet', 'testfile.css', ['late' => true]);
        $html = $app['asset.queue.file']->process($this->template);
        $this->assertEquals($this->html($this->expectedLateCss), $this->html($html));
    }

    // This method normalises the html so that differeing whitespace doesn't effect the strings.
    protected function html($string)
    {
        $doc = new \DOMDocument();

        // Here for PHP 5.3 compatibility where the constants aren't available
        if (!defined('LIBXML_HTML_NOIMPLIED')) {
            $doc->loadHTML($string);
        } else {
            $doc->loadHTML($string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        }
        $doc->preserveWhiteSpace = false;
        $html = $doc->saveHTML();
        $html = str_replace("\t", '', $html);
        $html = str_replace("\n", '', $html);

        return $html;
    }

    public function testSnippet()
    {
        $app = $this->getApp();

        // Test snippet inserts at top of <head>
        $app['asset.queue.snippet']->add(Target::START_OF_HEAD, '<meta name="test-snippet" />');

        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($html));

        // Test snippet inserts at end of <head>
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add(Target::END_OF_HEAD, '<meta name="test-snippet" />');
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($html));

        // Test snippet inserts at end of body
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add(Target::START_OF_BODY, '<p class="test-snippet"></p>');
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedStartOfBody), $this->html($html));

        // Test snippet inserts at end of </html>
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add(Target::END_OF_HTML, '<p class="test-snippet"></p>');
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHtml), $this->html($html));

        // Test snippet inserts before existing css
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add(Target::BEFORE_CSS, '<meta name="test-snippet" />');
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedBeforeCss), $this->html($html));

        // Test snippet inserts after existing css
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add(Target::AFTER_CSS, '<meta name="test-snippet" />');
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedAfterCss), $this->html($html));

        // Test snippet inserts after existing meta tags
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add(Target::AFTER_META, '<meta name="test-snippet" />');
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedAfterMeta), $this->html($html));
    }

    public function testSnippetsWithCallback()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));

        // Test snippet inserts at top of <head>
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($html));
    }

    public function testSnippetsWithGlobalCallback()
    {
        $app = $this->getApp();
        $app['asset.queue.snippet']->add(
            Target::AFTER_META,
            '\Bolt\Tests\Extensions\globalAssetsSnippet',
            'core',
            ["\n"]
        );

        // Test snippet inserts at top of <head>
        $html = $app['asset.queue.snippet']->process('<html></html>');
        $this->assertEquals('<html></html><br />'.PHP_EOL.PHP_EOL, $html);
    }

    public function testExtensionSnippets()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\Extension($app));
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($html));
    }

    public function testAddJquery()
    {
        $app = $this->makeApp();
        $app->initialize();

        $app = $this->getApp();
        $app['config']->set('general/add_jquery', true);
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertContains('js/jquery', $html);

        $app['config']->set('general/add_jquery', false);
        $html = $app['asset.queue.snippet']->process($this->template);
        $this->assertNotContains('js/jquery', $html);
    }

    public function testAddJqueryOnlyOnce()
    {
        $app = $this->getApp();
        $app->initialize();
        $app['config']->set('general/add_jquery', true);
        $html = $app['asset.queue.snippet']->process($this->template);
        $html = $app['asset.queue.snippet']->process($html);
    }

    public function testSnippetsWorkWithBadHtml()
    {
        $locations = [
            Target::START_OF_HEAD,
            Target::START_OF_BODY,
            Target::END_OF_BODY,
            Target::END_OF_HTML,
            Target::AFTER_META,
            Target::AFTER_CSS,
            Target::BEFORE_CSS,
            Target::BEFORE_JS,
            Target::AFTER_CSS,
            Target::AFTER_JS,
            'madeuplocation'
        ];
        foreach ($locations as $location) {
            $app = $this->getApp();
            $template = "<invalid></invalid>";
            $snip = '<meta name="test-snippet" />';
            $app['asset.queue.snippet']->add($location, $snip);
            $html = $app['asset.queue.snippet']->process($template);
            $this->assertEquals($template.$snip.PHP_EOL, $html);
        }
    }
}

function globalAssetsSnippet($string)
{
    return nl2br($string);
}
