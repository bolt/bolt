<?php
namespace Bolt\Tests\Extensions;

use Bolt\Asset\File\JavaScript;
use Bolt\Asset\File\Stylesheet;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
<link rel="stylesheet" href="existing.css" media="screen">
<link rel="stylesheet" href="testfile.css?5e544598b8d78644071a6f25fd8bba82" media="screen">
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
<link rel="stylesheet" href="testfile.css?5e544598b8d78644071a6f25fd8bba82" media="screen">
</body>
</html>
HTML;

    public $expectedJs = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
<script src="testfile.js?289fc946f38fee1a3e947eca1d6208b6"></script>
</head>
<body>
<script src="existing.js"></script>
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
<script src="testfile.js?289fc946f38fee1a3e947eca1d6208b6"></script>
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

    public $snippetException = <<<HTML
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
<!-- An exception occurred creating snippet -->
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
HTML;

    protected function getApp($boot = true)
    {
        $app = parent::getApp(false);
        $mock = $this->getMock('\Bolt\Asset\BoltVersionStrategy', ['getVersion'], [$app['filesystem']->getFilesystem('extensions'), $app['asset.salt']]);
        $mock->expects($this->any())
            ->method('getVersion')
            ->will($this->returnCallback(
                function($fileName) {
                    return md5($fileName);
                }
            ))
        ;
        $app['asset.version_strategy'] = $app->protect(function () use ($mock) {
            return $mock;
        });
        $app->boot();

        return $app;
    }

    public function testBadExtensionSnippets()
    {
        $app = $this->getApp();
        $app['asset.queue.snippet'] = new \Bolt\Asset\Snippet\Queue(
            $app['asset.injector'],
            $app['cache'],
            $app['config'],
            $app['resources'],
            $app['request_stack']
        );
        new Mock\BadExtensionSnippets($app);
        $response = new Response($this->template);

        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->snippetException), $this->html($response->getContent()));
    }

    public function testAddCss()
    {
        $app = $this->getApp();
        $stylesheet = (new Stylesheet())->setFileName('testfile.css');
        $app['asset.queue.file']->add($stylesheet);
        $assets = $app['asset.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['stylesheet']));
    }

    public function testAddJs()
    {
        $app = $this->getApp();
        $javaScript = (new JavaScript())->setFileName('testfile.js');
        $app['asset.queue.file']->add($javaScript);
        $assets = $app['asset.queue.file']->getQueue();
        $this->assertEquals(1, count($assets['javascript']));
    }

    public function testEmptyProcessAssetsFile()
    {
        $app = $this->getApp();
        $response = new Response('html');

        $app['asset.queue.file']->process($this->getRequest(), $response);
        $this->assertEquals('html', $response->getContent());
    }

    public function testEmptyProcessAssetsSnippets()
    {
        $app = $this->getApp();
        $response = new Response('html');

        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals('html', $response->getContent());
    }

    public function testJsProcessAssets()
    {
        $app = $this->getApp();
        $javaScript = (new JavaScript())->setFileName('testfile.js');
        $app['asset.queue.file']->add($javaScript);
        $app = $this->getApp();

        $response = new Response($this->template);

        $app['asset.queue.file']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedJs), $this->html($response->getContent()));
    }

    public function testLateJs()
    {
        $app = $this->getApp();
        $javaScript = (new JavaScript())
            ->setFileName('testfile.js')
            ->setLate(true)
        ;
        $app['asset.queue.file']->add($javaScript);
        $response = new Response($this->template);

        $app['asset.queue.file']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedLateJs),  $this->html($response->getContent()));
    }

    public function testCssProcessAssets()
    {
        $app = $this->getApp();
        $stylesheet = (new Stylesheet())->setFileName('testfile.css');
        $app['asset.queue.file']->add($stylesheet);
        $response = new Response($this->template);

        $app['asset.queue.file']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedCss), $this->html($response->getContent()));
    }

    public function testLateCss()
    {
        $app = $this->getApp();
        $stylesheet = (new Stylesheet())
            ->setFileName('testfile.css')
            ->setLate(true)
        ;
        $app['asset.queue.file']->add($stylesheet);
        $response = new Response($this->template);

        $app['asset.queue.file']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedLateCss), $this->html($response->getContent()));
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
        $response = new Response($this->template);
        $app['asset.queue.snippet']->add($this->getSnippet(Target::START_OF_HEAD, '<meta name="test-snippet" />'));
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($response->getContent()));

        // Test snippet inserts at end of <head>
        $response = new Response($this->template);
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add($this->getSnippet(Target::END_OF_HEAD, '<meta name="test-snippet" />'));
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($response->getContent()));

        // Test snippet inserts at end of body
        $response = new Response($this->template);
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add($this->getSnippet(Target::START_OF_BODY, '<p class="test-snippet"></p>'));
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedStartOfBody), $this->html($response->getContent()));

        // Test snippet inserts at end of </html>
        $response = new Response($this->template);
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add($this->getSnippet(Target::END_OF_HTML, '<p class="test-snippet"></p>'));
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedEndOfHtml), $this->html($response->getContent()));

        // Test snippet inserts before existing css
        $response = new Response($this->template);
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add($this->getSnippet(Target::BEFORE_CSS, '<meta name="test-snippet" />'));
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedBeforeCss), $this->html($response->getContent()));

        // Test snippet inserts after existing css
        $response = new Response($this->template);
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add($this->getSnippet(Target::AFTER_CSS, '<meta name="test-snippet" />'));
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedAfterCss), $this->html($response->getContent()));

        // Test snippet inserts after existing meta tags
        $response = new Response($this->template);
        $app['asset.queue.snippet']->clear();
        $app['asset.queue.snippet']->add($this->getSnippet(Target::AFTER_META, '<meta name="test-snippet" />'));
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedAfterMeta), $this->html($response->getContent()));
    }

    public function testSnippetsWithCallback()
    {
        $app = $this->getApp();
        new Mock\SnippetCallbackExtension($app);
        $response = new Response($this->template);

        // Test snippet inserts at top of <head>
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($response->getContent()));
    }

    public function testSnippetsWithGlobalCallback()
    {
        $app = $this->getApp();
        $app['asset.queue.snippet']->add($this->getSnippet(
            Target::AFTER_META,
            '\Bolt\Tests\Extensions\globalAssetsSnippet',
            'core',
            ["\n"]
        ));

        // Test snippet inserts at top of <head>
        $response = new Response('<html></html>');
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals('<html></html><br />' . PHP_EOL . PHP_EOL, $response->getContent());
    }

    public function testExtensionSnippets()
    {
        $app = $this->getApp();
        new Mock\Extension($app);
        $response = new Response($this->template);
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($response->getContent()));
    }

    public function testAddJquery()
    {
        $app = $this->makeApp();
        $app->initialize();

        $app = $this->getApp();
        $app['config']->set('general/add_jquery', true);
        $response = new Response($this->template);
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertContains('js/jquery', $response->getContent());

        $app['config']->set('general/add_jquery', false);
        $response = new Response($this->template);
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $this->assertNotContains('js/jquery', $response->getContent());
    }

    public function testAddJqueryOnlyOnce()
    {
        $app = $this->getApp();
        $app->initialize();
        $app['config']->set('general/add_jquery', true);
        $response = new Response($this->template);
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
        $app['asset.queue.snippet']->process($this->getRequest(), $response);
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
            'madeuplocation',
        ];
        foreach ($locations as $location) {
            $app = $this->getApp();
            $template = '<invalid></invalid>';
            $snip = '<meta name="test-snippet" />';
            $app['asset.queue.snippet']->add($this->getSnippet($location, $snip));

            $response = new Response($template);
            $app['asset.queue.snippet']->process($this->getRequest(), $response);
            //$html = $app['asset.queue.snippet']->process($template);
            $this->assertEquals($template . $snip . PHP_EOL, $response->getContent());
        }
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        $request = Request::createFromGlobals();
        $request->attributes->set(Zone::KEY, Zone::FRONTEND);

        return $request;
    }

    /**
     * @return Snippet
     */
    private function getSnippet($location, $callback, $extensionName = 'core', $callbackArguments = [])
    {
        $snippet = (new Snippet())
            ->setLocation($location)
            ->setCallback($callback)
            ->setCallbackArguments($callbackArguments)
        ;

        return $snippet;
    }
}

function globalAssetsSnippet($string)
{
    return nl2br($string);
}
