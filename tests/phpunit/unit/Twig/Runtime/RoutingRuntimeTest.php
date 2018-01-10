<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\RoutingRuntime;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test Bolt\Twig\Runtime\RoutingRuntime.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class RoutingRuntimeTest extends BoltUnitTest
{
    public function testHtmlLang()
    {
        $app = $this->getApp();
        $app['locale'] = 'en_Aussie_Mate';
        $handler = $this->getRuntime();

        $result = $handler->htmlLang();
        $this->assertSame('en-Aussie-Mate', $result);
    }

    public function testHtmlLangFromRequest()
    {
        $app = $this->getApp();
        $app['locale'] = 'en_Aussie_Mate';
        $handler = $this->getRuntime();

        $request = Request::create('/');
        $request->setLocale('en_American_Bro');
        $app['request_stack']->push($request);

        $result = $handler->htmlLang();
        $this->assertSame('en-American-Bro', $result);
    }

    public function userAgentProvider()
    {
        return [
            'Android'    => ['Mozilla/5.0 (Linux; Android 5.0.1; Nexus 6 Build/LRX22C) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.109 Mobile Safari/537.36'],
            'Blackberry' => ['Mozilla/5.0 (BlackBerry; U) AppleWebKit/601.1.48 (KHTML, like Gecko) Safari/600.8.9'],
            'HTC'        => ['Dalvik/1.6.0 (Linux; U; Android 4.2.2; HTC One X Build/JDQ39)'],
            'IE Mobile'  => ['Mozilla/5.0 (compatible; MSIE 9.0; Windows Phone OS 7.5; Trident/5.0; IEMobile/9.0)'],
            'iPhone'     => ['Mozilla/5.0 (Macintosh; U; PPC Mac OS X 10_5_2; en-gb) AppleWebKit/526+ (KHTML, like Gecko) Version/3.1 iPhone'],
            'iPad'       => ['Mozilla/5.0 (iPad;U;CPU OS 3_2_2 like Mac OS X; en-gb) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B500 Safari/531.21.10'],
            'iPaq'       => ['Mozilla/4.0 (compatible; MSIE 4.01; Windows CE; PPC; 240x320; HP iPAQ h5450)'],
            'iPod'       => ['Mozilla/5.0 (iPod; U; CPU iPhone OS 4_2_1 like Mac OS X; en-gb) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8C148 Safari/6533.18.5'],
            'Nokia'      => ['Mozilla/5.0 (SymbianOS/9.4; Series60/5.0 Nokia5230/51.0.002; Profile/MIDP-2.1 Configuration/CLDC-1.1 ) AppleWebKit/533.4 (KHTML, like Gecko) NokiaBrowser/7.3.1.33 Mobile Safari/533.4'],
            'Playbook'   => ['Mozilla/5.0 (BlackBerry Playbook; U; it) AppleWebKit/537.36 (KHTML, like Gecko) Version/2.1.0.1917 Mobile Safari/537.36'],
            'Smartphone' => ['Oppo smartphone Profile/MIDP-2.0 Configuration/CLDC-1.1'],
            'Invalid'    => ['Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.71 Safari/537.36', false],
        ];
    }

    /**
     * @dataProvider userAgentProvider
     *
     * @param string $userAgent
     * @param bool   $isMobile
     */
    public function testIsMobileClientValid($userAgent, $isMobile = true)
    {
        $app = $this->getApp();
        $handler = $this->getRuntime();

        $request = Request::create('/');
        $request->headers->set('User-Agent', $userAgent);

        $app['request_stack']->push($request);

        $result = $handler->isMobileClient();

        $app['request_stack']->pop();

        $this->assertEquals($isMobile, $result, sprintf('User Agent should %shave been identified as a mobile client', !$isMobile ? 'not ' : ''));
    }

    /**
     * @runInSeparateProcess
     * @requires extension xdebug
     */
    public function testRedirectNoSafe()
    {
        if (phpversion('xdebug') === false) {
            $this->markTestSkipped('No xdebug support enabled.');
        }

        $handler = $this->getRuntime();
        $this->expectOutputRegex('/Redirecting to/i');

        $handler->redirect('/clippy/koala');
        $this->assertContains('location: /clippy/koala', xdebug_get_headers());
    }

    public function testRequestGet()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $request->query->set('koala', 'gum leaves');
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRuntime();

        $result = $handler->request('koala', 'GET', true);
        $this->assertSame('gum leaves', $result);
    }

    public function testRequestPost()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $request->request->set('koala', 'gum leaves');
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRuntime();

        $result = $handler->request('koala', 'POST', true);
        $this->assertSame('gum leaves', $result);
    }

    public function testRequestPatch()
    {
        $app = $this->getApp();
        $request = Request::createFromGlobals();
        $request->attributes->set('koala', 'gum leaves');
        $app['request'] = $request;
        $app['request_stack']->push($request);
        $handler = $this->getRuntime();

        $result = $handler->request('koala', 'PATCH', true);
        $this->assertSame('gum leaves', $result);
    }

    protected function getRuntime()
    {
        $app = $this->getApp();

        return new RoutingRuntime(
            $app['canonical'],
            $app['request_stack'],
            $app['locale'],
            $app['url_generator'],
            $app['users']
        );
    }
}
