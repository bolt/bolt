<?php

namespace Bolt\Tests\Asset;

use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to test correct operation and locations of extensions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SnippetsTest extends BoltUnitTest
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

    // This method normalises the html so that differing whitespace doesn't affect the string comparison.
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

    public function snippetProvider()
    {
        return [
            [Target::START_OF_HEAD, $this->expectedStartOfHead, '<meta name="test-snippet" />'],
            [Target::END_OF_HEAD, $this->expectedEndOfHead, '<meta name="test-snippet" />'],
            [Target::START_OF_BODY, $this->expectedStartOfBody, '<p class="test-snippet"></p>'],
            [Target::END_OF_HTML, $this->expectedEndOfHtml, '<p class="test-snippet"></p>'],
            [Target::BEFORE_CSS, $this->expectedBeforeCss, '<meta name="test-snippet" />'],
            [Target::AFTER_CSS, $this->expectedAfterCss, '<meta name="test-snippet" />'],
            [Target::AFTER_META, $this->expectedAfterMeta, '<meta name="test-snippet" />'],

            [Target::START_OF_HEAD, $this->expectedStartOfHead, function () { return '<meta name="test-snippet" />'; }],
        ];
    }

    /**
     * @dataProvider snippetProvider
     *
     * @param string $target
     * @param string $expectation
     * @param string $callback
     */
    public function testSnippets($target, $expectation, $callback)
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $response = new Response($this->template);
        Zone::set($request, Zone::FRONTEND);
        $snippet = (new Snippet())
            ->setLocation($target)
            ->setCallback($callback)
            ->setZone(Zone::FRONTEND)
        ;
        $queue = $app['asset.queue.snippet'];
        $queue->add($snippet);
        $queue->process($request, $response);
        $html = $response->getContent();

        $this->assertEquals($this->html($expectation), $this->html($html));
    }

    public function snippetBadHtmlProvider()
    {
        return [
            [Target::START_OF_HEAD],
            [Target::START_OF_BODY],
            [Target::END_OF_BODY],
            [Target::END_OF_HTML],
            [Target::AFTER_META],
            [Target::AFTER_CSS],
            [Target::BEFORE_CSS],
            [Target::BEFORE_JS],
            [Target::AFTER_CSS],
            [Target::AFTER_JS],
            ['madeuplocation'],
        ];
    }

    /**
     * @dataProvider snippetBadHtmlProvider
     *
     * @param string $target
     */
    public function testSnippetsWorkWithBadHtml($target)
    {
        $app = $this->getApp();
        $template = '<invalid></invalid>';
        $callback = '<meta name="test-snippet" />';

        $request = Request::createFromGlobals();
        $response = new Response($template);
        Zone::set($request, Zone::FRONTEND);
        $snippet = (new Snippet())
            ->setLocation($target)
            ->setCallback($callback)
            ->setZone(Zone::FRONTEND)
        ;
        $queue = $app['asset.queue.snippet'];
        $queue->add($snippet);
        $queue->process($request, $response);
        $html = $response->getContent();

        $this->assertEquals($template . $callback . PHP_EOL, $html);
    }
}

function globalSnippet($string)
{
    return nl2br($string);
}
