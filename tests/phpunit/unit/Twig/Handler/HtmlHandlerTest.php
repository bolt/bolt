<?php

namespace Bolt\Tests\Twig;

use Bolt\Legacy\Content;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Handler\HtmlHandler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test Bolt\Twig\Handler\HtmlHandler
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class HtmlHandlerTest extends BoltUnitTest
{
    public function testDecorateTT()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $result = $handler->decorateTT('Lorem `ipsum` dolor.');
        $this->assertSame('Lorem <tt>ipsum</tt> dolor.', $result);
    }

    public function testEditableSafe()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $result = $handler->editable('<blink>Drop Bear Warning!</blink>', new Content($app), 'paddock', true);
        $this->assertNull($result);
    }

    public function testEditable()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);
        $content = new Content($app);
        $content->setValues([
            'id'          => 42,
            'contenttype' => ['slug' => 'snail'],
        ]);

        $result = $handler->editable('<blink>Drop Bear Warning!</blink>', $content, 'paddock', false);
        $this->assertSame('<div class="Bolt-editable" data-id="42" data-contenttype="" data-field="paddock"><blink>Drop Bear Warning!</blink></div>', $result);
    }

    public function testHtmlLang()
    {
        $app = $this->getApp();
        $app['locale'] = 'en_Aussie_Mate';
        $handler = new HtmlHandler($app);

        $result = $handler->htmlLang();
        $this->assertSame('en-Aussie-Mate', $result);
    }

    public function testIsMobileClientValid()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        // Android
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 5.0.1; Nexus 6 Build/LRX22C) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.109 Mobile Safari/537.36';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // Blackberry
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (BlackBerry; U) AppleWebKit/601.1.48 (KHTML, like Gecko) Safari/600.8.9';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // HTC
        $_SERVER['HTTP_USER_AGENT'] = 'Dalvik/1.6.0 (Linux; U; Android 4.2.2; HTC One X Build/JDQ39)';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // IE Mobile
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0)';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // iPhone
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; PPC Mac OS X 10_5_2; en-gb) AppleWebKit/526+ (KHTML, like Gecko) Version/3.1 iPhone';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // iPad
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPad;U;CPU OS 3_2_2 like Mac OS X; en-gb) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B500 Safari/531.21.10';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // iPaq
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 4.01; Windows CE; PPC; 240x320; HP iPAQ h5450)';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // iPod
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPod; U; CPU iPhone OS 4_2_1 like Mac OS X; en-gb) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148 Safari/6533.18.5';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // Nokia
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (SymbianOS/9.4; Series60/5.0 Nokia5230/51.0.002; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/533.4 (KHTML, like Gecko) NokiaBrowser/7.3.1.33 Mobile Safari/533.4';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // Playbook
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (BlackBerry Playbook; U; it) AppleWebKit/537.36 (KHTML, like Gecko) Version/2.1.0.1917 Mobile Safari/537.36';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);

        // Smartphone
        $_SERVER['HTTP_USER_AGENT'] = 'Oppo smartphone Profile/MIDP-2.0 Configuration/CLDC-1.1';
        $result = $handler->isMobileClient();
        $this->assertTrue($result);
    }

    public function testIsMobileClientInvalid()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.71 Safari/537.36';
        $result = $handler->isMobileClient();
        $this->assertFalse($result);
    }

    public function testMarkdown()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $markdown = <<<MARKDOWN
# Episode IV
## A New Hope
It is a period of refactor war.
* BPFL
MARKDOWN;

        $html = <<< HTML
<h1>Episode IV</h1>
<h2>A New Hope</h2>
<p>It is a period of refactor war.</p>
<ul>
<li>BPFL</li>
</ul>
HTML;
        $result = $handler->markdown($markdown);
        $this->assertSame($html, $result);
    }

    public function testLink()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $result = $handler->link('http://google.com', 'click');
        $this->assertSame('<a href="http://google.com">click</a>', $result);

        $result = $handler->link('google.com');
        $this->assertSame('<a href="http://google.com">[link]</a>', $result);

        $result = $handler->link('mailto:bob@bolt.cm', 'mail');
        $this->assertSame('<a href="mailto:bob@bolt.cm">mail</a>', $result);

        $result = $handler->link('gooblycook', 'click');
        $this->assertSame('<a href="gooblycook">click</a>', $result);
    }


    public function testMenuSafe()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $result = $handler->menu($app['twig'], 'main', '_sub_menu.twig', ['kitten' => 'fluffy'], true);
        $this->assertNull($result);
    }

    public function testMenuMain()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $app['request'] = $request;
        $app['request_stack']->push($request);

        $handler = new HtmlHandler($app);

        $result = $handler->menu($app['twig'], 'main', 'partials/_sub_menu.twig', ['kitten' => 'fluffy'], false);
        $this->assertRegExp('#<li class="index-1 first">#', $result);
    }

    public function testShy()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $result = $handler->shy('SomePeopleSayTheyAreShyOtherPeopleSayTheyAreNotWhatDoYouSay');
        $this->assertSame('SomePeople&shy;SayTheyAre&shy;ShyOtherPe&shy;opleSayThe&shy;yAreNotWha&shy;tDoYouSay', $result);
    }

    public function testTwig()
    {
        $app = $this->getApp();
        $handler = new HtmlHandler($app);

        $result = $handler->twig("{{ 'koala'|capitalize }}");
        $this->assertSame('Koala', $result);
    }
}
