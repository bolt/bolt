<?php

namespace Bolt\Tests\Extensions;

use Bolt\Asset\Target;

/**
 * Class to test correct operation and locations of extensions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SnippetsTest extends AbstractExtensionsUnitTest
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
        $app = parent::getApp($boot);
        $app['asset.file.hash.factory'] = $app->protect(function ($fileName) {
            return md5($fileName);
        });

        return $app;
    }

    // This method normalises the html so that differing whitespace doesn't effect the strings.
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

        // Trim trailing and leading whitespace
        $html = implode("\n", array_map('trim', explode("\n", $html)));

        return $html;
    }

    // This method does a simple minification of the HTML, as it removes whitespace between tags.
    protected function minify($html)
    {
        $search = [
            '/\>[^\S]+/s', // strip whitespaces after tags, except space
            '/[^\S]+\</s', // strip whitespaces before tags, except space
            '/(\s)+/s',     // shorten multiple whitespace sequences
        ];

        $replace = [
            '>',
            '<',
            '\\1',
        ];

        $html = preg_replace($search, $replace, $this->html($html));

        return $html;
    }

    public function testSnippet()
    {
        $this->markTestIncomplete('Update required');

        $this->tearDown();
        $app = $this->getApp();

        // Test snippet inserts at top of <head>
        $app['extensions']->insertSnippet(Target::START_OF_HEAD, '<meta name="test-snippet" />');

        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($html));

        // Test snippet inserts at end of <head>
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(Target::END_OF_HEAD, '<meta name="test-snippet" />');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($html));

        // Test snippet inserts at end of body
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(Target::START_OF_BODY, '<p class="test-snippet"></p>');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedStartOfBody), $this->html($html));

        // Test snippet inserts at end of </html>
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(Target::END_OF_HTML, '<p class="test-snippet"></p>');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHtml), $this->html($html));

        // Test snippet inserts before existing css
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(Target::BEFORE_CSS, '<meta name="test-snippet" />');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedBeforeCss), $this->html($html));

        // Test snippet inserts after existing css
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(Target::AFTER_CSS, '<meta name="test-snippet" />');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedAfterCss), $this->html($html));

        // Test snippet inserts after existing meta tags
        $app['extensions']->clearSnippetQueue();
        $app['extensions']->insertSnippet(Target::AFTER_META, '<meta name="test-snippet" />');
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedAfterMeta), $this->html($html));
    }

    public function testSnippetsWithCallback()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->getApp();
        $app['extensions']->register(new Mock\SnippetCallbackExtension($app));

        // Test snippet inserts at top of <head>
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedStartOfHead), $this->html($html));
    }

    public function testSnippetsWithGlobalCallback()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->getApp();
        $app['extensions']->insertSnippet(
            Target::AFTER_META,
            '\Bolt\Tests\Extensions\globalSnippet',
            'core',
            "\n"
        );

        // Test snippet inserts at top of <head>
        $html = $app['extensions']->processSnippetQueue('<html></html>');
        $this->assertEquals('<html></html><br />' . PHP_EOL . PHP_EOL, $html);
    }

    public function testExtensionSnippets()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->getApp();
        $app['extensions']->register(new Mock\Extension($app));
        $html = $app['extensions']->processSnippetQueue($this->template);
        $this->assertEquals($this->html($this->expectedEndOfHead), $this->html($html));
    }

    public function testSnippetsWorkWithBadHtml()
    {
        $this->markTestIncomplete('Update required');

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
            $app['extensions']->insertSnippet($location, $snip);
            $html = $app['extensions']->processSnippetQueue($template);
            $this->assertEquals($template . $snip . PHP_EOL, $html);
        }
    }

    public function testTwigExtensions()
    {
        $this->markTestIncomplete('Update required');

        $app = $this->getApp();
        $app['logger.system'] = new Mock\Logger();
        $app['extensions']->register(new Mock\ExtensionWithTwig($app));
        $log = $app['logger.system']->lastLog();
        // Temporarily Disabled, need to investigate why this now fails.
        //$this->assertContains('[EXT] Twig function registration failed', $log);
        //$this->assertContains('instance of Bolt\Tests\Extensions\Mock\BadTwigExtension given', $log);
    }

    public function testCommentsHandled()
    {
        $this->markTestIncomplete('Update required');

        $template = $this->template . '<!-- This is a comment -->';
        $app = $this->getApp();
        $snip = '<meta name="test-snippet" />';
        $app['extensions']->insertSnippet('append', $snip);
        $html = $app['extensions']->processSnippetQueue($template);
        $this->assertEquals($template . $snip . PHP_EOL, $html);
    }
}

function globalSnippet($string)
{
    return nl2br($string);
}
