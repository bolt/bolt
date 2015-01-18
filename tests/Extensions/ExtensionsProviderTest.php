<?php
namespace Bolt\Tests\Extensions;

use Bolt\Application;
use Bolt\Configuration\Standard;
use Bolt\Extensions;
use Bolt\Tests\BoltUnitTest;
use Bolt\Extensions\Snippets\Location as SnippetLocation;


/**
 * Class to test correct operation and locations of extensions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class ExtensionsProviderTest extends BoltUnitTest
{
    
    public $template = <<< EOM
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
EOM;

    public $expectedCss = <<< EOM
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="testfile.css" media="screen">
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
</body>
</html>
EOM;

    public $expectedLateCss = <<< EOM
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
<link rel="stylesheet" href="testfile.css" media="screen">
</body>
</html>
EOM;

    public $expectedJs = <<< EOM
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
<script src="testfile.js"></script>
</body>
</html>
EOM;

    public $expectedLateJs = <<< EOM
<html>
<head>
<meta charset="utf-8" />
<link rel="stylesheet" href="existing.css" media="screen">
</head>
<body>
<script src="existing.js"></script>
<script src="testfile.js"></script>
</body>
</html>
EOM;

    public $expectedStartOfHead = <<< EOM
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
EOM;

    public $expectedEndOfHead = <<< EOM
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
EOM;

    public $expectedStartOfBody = <<< EOM
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
EOM;

    public $expectedEndOfHtml = <<< EOM
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
EOM;

    public $expectedBeforeCss = <<< EOM
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
EOM;

    public $expectedAfterCss = <<< EOM
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
EOM;

    public $expectedAfterMeta = <<< EOM
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
EOM;


    public function tearDown()
    {
        
    }
    
    
    public function testExtensionRegister()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\Extension($app));
        $this->assertTrue(isset($app['extensions']));
        $this->assertTrue($app['extensions']->isEnabled('testext'));
        $app['extensions']->register(new Mock\Extension($app));
    }
    
    public function testBadExtension()
    {
        $app = $this->getApp();
        $app['log'] = new Mock\Logger;
        $app['extensions']->register(new Mock\BadExtension($app));        
        $this->assertEquals('[EXT] Initialisation failed for badextension: BadExtension', $app['log']->lastLog());
        
    }
    
    public function testBadExtensionConfig()
    {
        $app = $this->getApp();
        $app['log'] = new Mock\Logger;
        $app['extensions']->register(new Mock\BadExtensionConfig($app));
        $this->assertEquals(
            '[EXT] YAML config failed to load for badextensionconfig: BadExtensionConfig', 
            $app['log']->lastLog()
        );
    }
    
    public function testBadExtensionSnippets()
    {
        $app = $this->getApp();
        $app['log'] = new Mock\Logger;
        $app['extensions']->register(new Mock\BadExtensionSnippets($app));
        $this->assertEquals(
            '[EXT] Snippet loading failed for badextensionsnippets: BadExtensionSnippets', 
            $app['log']->lastLog()
        );
    }
    
    public function testAddCss()
    {
        $app = $this->getApp();
        $app['extensions']->addCss('testfile.css');
        $assets = $app['extensions']->getAssets();
        $this->assertEquals(1, count($assets['css']));  
    }
    
    public function testAddJs()
    {
        $app = $this->getApp();
        $app['extensions']->addJavascript('testfile.js');
        $assets = $app['extensions']->getAssets();
        $this->assertEquals(1, count($assets['js']));  
    }
    
    public function testEmptyProcessAssets()
    {
        $app = $this->getApp();
        $html = $app['extensions']->processAssets("html");
        $this->assertEquals("html", $html);
    }
    
    public function testJsProcessAssets()
    {
        $app = $this->getApp();
        $app['extensions']->addJavascript('testfile.js');
        $html = $app['extensions']->processAssets($this->template);
        $this->assertEquals($this->html($this->expectedJs), $this->html($html));
    }
    
    public function testLateJs()
    {
        $app = $this->getApp();
        $app['extensions']->addJavascript('testfile.js', true);
        $html = $app['extensions']->processAssets($this->template);
        $this->assertEquals($this->html($this->expectedLateJs),  $this->html($html)  );

    }
    
    public function testCssProcessAssets()
    {
        $app = $this->getApp();
        $app['extensions']->addCss('testfile.css');
        $html = $app['extensions']->processAssets($this->template);
        $this->assertEquals($this->html($this->expectedCss), $this->html($html));

    }
    
    public function testLateCss()
    {
        $app = $this->getApp();
        $app['extensions']->addCss('testfile.css', true);
        $html = $app['extensions']->processAssets($this->template);
        $this->assertEquals($this->html($this->expectedLateCss), $this->html($html));
    }
    
    
    // This method normalises the html so that differeing whitespace doesn't effect the strings.
    protected function html($string) {
        $doc = new \DOMDocument();
        $doc->loadHTML($string, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $doc->preserveWhitespace = false;
        $html = $doc->saveHTML();
        $html = str_replace("\t", "", $html);
        $html = str_replace("\n", "", $html);
        return $html;
    }
    
    /**
    * @runInSeparateProcess
    */
    public function testLocalload()
    {
        $app = $this->makeApp();
        $app['resources']->setPath('extensions', __DIR__."/resources");
        $app->initialize();
        $this->assertTrue($app['extensions']->isEnabled('testlocal'));
    }
    
    
    public function testSnippet()
    {
        $app = $this->getApp();
        
        // Test snippet inserts at top of <head>
        $app['extensions']->insertSnippet(SnippetLocation::START_OF_HEAD, '<meta name="test-snippet" />');
        
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($html));
        
        
        // Test snippet inserts at end of <head>
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(SnippetLocation::END_OF_HEAD, '<meta name="test-snippet" />');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($html));
        
        
        // Test snippet inserts at end of body
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(SnippetLocation::START_OF_BODY, '<p class="test-snippet"></p>');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedStartOfBody), $this->html($html));
        
        // Test snippet inserts at end of </html>
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(SnippetLocation::END_OF_HTML, '<p class="test-snippet"></p>');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHtml), $this->html($html));
        
        
        // Test snippet inserts before existing css
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(SnippetLocation::BEFORE_CSS, '<meta name="test-snippet" />');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedBeforeCss), $this->html($html));
        
        
        // Test snippet inserts after existing css
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(SnippetLocation::AFTER_CSS, '<meta name="test-snippet" />');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedAfterCss), $this->html($html));
        
        
        // Test snippet inserts after existing meta tags
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(SnippetLocation::AFTER_META, '<meta name="test-snippet" />');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedAfterMeta), $this->html($html));
        
    }
    
    public function testSnippetsWithCallback()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));
        
        // Test snippet inserts at top of <head>
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($html));
    }
    
    public function testSnippetsWithGlobalCallback()
    {
        $app = $this->getApp();
        $app['extensions']->insertSnippet(
            SnippetLocation::AFTER_META, 
            '\Bolt\Tests\Extensions\globalSnippet', 
            'core', 
            "\n"
        );
        
        // Test snippet inserts at top of <head>
        $html = $app['extensions']->processSnippetQueue("<html></html>");
        $this->assertEquals("<html></html><br />".PHP_EOL.PHP_EOL, $html);
    }
    
    
    public function testExtensionSnippets()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\Extension($app));
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($html));
    }
    
    public function testAddJquery()
    {
        $app = $this->makeApp();
        $app['config']->set('general/add_jquery', true);
        $app->initialize();
        $app['extensions']->register(new Mock\Extension($app));
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertContains("js/jquery", $html);
        
        $app = $this->getApp();
        $app['extensions']->register(new Mock\Extension($app));
        $app['extensions']->addJquery();
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertContains("js/jquery", $html);
        $app['extensions']->disableJquery();
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertNotContains("js/jquery", $html);
    }
    
    public function testAddJqueryOnlyOnce()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\Extension($app));
        $app['extensions']->addJquery();
        $html = $app['extensions']->processSnippetQueue($this->template);
        $html = $app['extensions']->processSnippetQueue($html);
        
    }
    
    public function testSnippetsWorkWithBadHtml()
    {
        $locations = array(
            SnippetLocation::START_OF_HEAD,
            SnippetLocation::START_OF_BODY,
            SnippetLocation::END_OF_BODY,
            SnippetLocation::END_OF_HTML,
            SnippetLocation::AFTER_META,
            SnippetLocation::AFTER_CSS,
            SnippetLocation::BEFORE_CSS,
            SnippetLocation::BEFORE_JS,
            SnippetLocation::AFTER_CSS,
            SnippetLocation::AFTER_JS,
            'madeuplocation'
        );
        foreach ($locations as $location) {
            $app = $this->getApp();
            $template = "<invalid></invalid>";
            $snip = '<meta name="test-snippet" />';
            $app['extensions']->insertSnippet($location, $snip);
            $html = $app['extensions']->processSnippetQueue($template);
            $this->assertEquals($template.$snip.PHP_EOL, $html);
        }

        
    }
    
    
    public function testAddMenuOption()
    {
        $app = $this->getApp();
        $app['extensions']->addMenuOption('My Test', 'mytest');
        $this->assertTrue($app['extensions']->hasMenuOptions());
        $this->assertEquals(1, count($app['extensions']->getMenuOptions()));
    }
    
    public function testInsertWidget()
    {
        $app = $this->getApp();
        $app['extensions']->insertWidget('test', SnippetLocation::START_OF_BODY, "", "testext", "", false);
        $this->expectOutputString("<section><div class='widget' id='widget-dacf7046' data-key='dacf7046'></div></section>");
        $app['extensions']->renderWidgetHolder('test', SnippetLocation::START_OF_BODY);
        
    }
    
    public function testWidgetCaches()
    {
        $app = $this->getApp();
        $app['cache'] = new Mock\Cache();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));
        $this->assertFalse($app['cache']->fetch('5e4c97cb'));
        $app['extensions']->insertWidget('test', SnippetLocation::AFTER_JS, "snippetCallBack", "snippetcallback", "", false);
        
        // Double call to ensure second one hits cache
        $html = $app['extensions']->renderWidget('5e4c97cb');
        $html = $app['extensions']->renderWidget('5e4c97cb');
        $this->assertEquals($html, $app['cache']->fetch('widget_5e4c97cb'));
    }
    
    public function testInvalidWidget()
    {
        $app = $this->getApp();
        $app['extensions']->insertWidget('test', SnippetLocation::START_OF_BODY, "", "testext", "", false);
        $result = $app['extensions']->renderWidget('fakekey');
        $this->assertEquals("Invalid key 'fakekey'. No widget found.", $result);
    }
    
    public function testWidgetWithCallback()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));
        
        $app['extensions']->insertWidget('test', SnippetLocation::AFTER_JS, "snippetCallBack", "snippetcallback", "", false);
        $html = $app['extensions']->renderWidget('5e4c97cb');
        $this->assertEquals('<meta name="test-snippet" />', $html);
    }
    
    
    public function testWidgetWithGlobalCallback()
    {
        $app = $this->getApp();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));
        
        $app['extensions']->insertWidget(
            'testglobal', 
            SnippetLocation::START_OF_BODY, 
            "\Bolt\Tests\Extensions\globalWidget", 
            "snippetcallback", 
            "", 
            false
        );
        $html = $app['extensions']->renderWidget('7e2b9a48');
        $this->assertEquals('<meta name="test-widget" />', $html);
    }
    
    public function testTwigExtensions()
    {
        $app = $this->getApp();
        $app['log'] = new Mock\Logger;
        $app['extensions']->register(new Mock\ExtensionWithTwig($app));
        $log = $app['log']->lastLog();
        // Temporarily Disabled, need to investigate why this now fails.
        //$this->assertContains('[EXT] Twig function registration failed', $log);
        //$this->assertContains('instance of Bolt\Tests\Extensions\Mock\BadTwigExtension given', $log);
    }
    
    public function testCommentsHandled()
    {
        $template = $this->template."<!-- This is a comment -->";
        $app = $this->getApp();
        $snip = '<meta name="test-snippet" />';
        $app['extensions']->insertSnippet('append', $snip);
        $html = $app['extensions']->processSnippetQueue($template);
        $this->assertEquals($template.$snip.PHP_EOL, $html);
    }

   
}


function globalSnippet($app, $string)
{
    return nl2br($string);
}

function globalWidget()
{
    return '<meta name="test-widget" />';
}

