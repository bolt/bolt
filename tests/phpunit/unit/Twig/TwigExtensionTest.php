<?php
namespace Bolt\Tests\Twig;

use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\TwigExtension;
use Bolt\Legacy\Content;

/**
 * Class to test src/Library.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TwigExtensionTest extends BoltUnitTest
{
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

    /*
     * Handlers below
     */

    public function testAddData()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'addData');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->addData(null, null);
    }

    public function testCacheHash()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'cacheHash');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->cacheHash(null);
    }

    public function testCurrent()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['record'] = $this->getMockHandler('RecordHandler', 'current');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->current(null);
    }

    public function testDecorateTT()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'decorateTT');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->decorateTT(null);
    }

    public function testEditable()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'editable');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->editable(null, new Content($app), null, null);
    }

    public function testExcerpt()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['record'] = $this->getMockHandler('RecordHandler', 'excerpt');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->excerpt(null, null);
    }

    public function testFileExists()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['utils'] = $this->getMockHandler('UtilsHandler', 'fileExists');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->fileExists(null, null);
    }

    public function testGetUser()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['user'] = $this->getMockHandler('UserHandler', 'getUser');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->getUser(null);
    }

    public function testGetUserId()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['user'] = $this->getMockHandler('UserHandler', 'getUserId');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->getUserId(null);
    }

    public function testHattr()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'hattr');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->hattr(null);
    }

    public function testHclass()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'hclass');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->hclass(null);
    }

    public function testHtmlLang()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'htmlLang');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->htmlLang();
    }

    public function testImage()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['image'] = $this->getMockHandler('ImageHandler', 'image');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->image(null, null, null, null);
    }

    public function testImageInfo()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['image'] = $this->getMockHandler('ImageHandler', 'imageInfo');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->imageInfo(null, null);
    }

    public function testIsAllowed()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['user'] = $this->getMockHandler('UserHandler', 'isAllowed');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->isAllowed(null, null);
    }

    public function testIsChangelogEnabled()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'isChangelogEnabled');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->isChangelogEnabled();
    }

    public function testIsMobileClient()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'isMobileClient');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->isMobileClient(null);
    }

    public function testJsonDecode()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['text'] = $this->getMockHandler('TextHandler', 'jsonDecode');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->jsonDecode(null);
    }

    public function testListContent()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['record'] = $this->getMockHandler('RecordHandler', 'listContent');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->listContent(null, null, null);
    }

    public function testListTemplates()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['record'] = $this->getMockHandler('RecordHandler', 'listTemplates');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->listTemplates(null, null);
    }

    public function testLocaleDateTime()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['text'] = $this->getMockHandler('TextHandler', 'localeDateTime');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->localeDateTime(null, null);
    }

    public function testLogLevel()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'logLevel');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->logLevel(null);
    }

    public function testMarkdown()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'markdown');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->markdown(null);
    }

    public function testMenu()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'menu');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->menu($app['twig'], null, null, null, null);
    }

    public function testOrder()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['array'] = $this->getMockHandler('ArrayHandler', 'order');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->order(null, null, null);
    }

    public function testPager()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['record'] = $this->getMockHandler('RecordHandler', 'pager');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->pager($app['twig'], null, null, null, null);
    }

    public function testPopup()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['image'] = $this->getMockHandler('ImageHandler', 'popup');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->popup(null, null, null, null, null);
    }

    public function testPregReplace()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['text'] = $this->getMockHandler('TextHandler', 'pregReplace');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->pregReplace(null, null, null, null);
    }

    public function testPrintBacktrace()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['utils'] = $this->getMockHandler('UtilsHandler', 'printBacktrace');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->printBacktrace(null, null);
    }

    public function testPrintDump()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['utils'] = $this->getMockHandler('UtilsHandler', 'printDump');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->printDump(null, null);
    }

    public function testPrintFirebug()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['utils'] = $this->getMockHandler('UtilsHandler', 'printFirebug');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->printFirebug(null, null, null);
    }

    public function testRandomQuote()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'randomQuote');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->randomQuote();
    }

    public function testRedirect()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['utils'] = $this->getMockHandler('UtilsHandler', 'redirect');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->redirect(null, null);
    }

    public function testRequest()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['utils'] = $this->getMockHandler('UtilsHandler', 'request');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->request(null, null, null, null);
    }

    public function testSafeString()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['text'] = $this->getMockHandler('TextHandler', 'safeString');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->safeString(null, null, null);
    }

    public function testSelectField()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['record'] = $this->getMockHandler('RecordHandler', 'selectField');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->selectField(null, null, null, null);
    }

    public function testShowImage()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['image'] = $this->getMockHandler('ImageHandler', 'showImage');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->showImage(null, null, null, null);
    }

    public function testShuffle()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['array'] = $this->getMockHandler('ArrayHandler', 'shuffle');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->shuffle(null);
    }

    public function testShy()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'shy');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->shy(null);
    }

    public function testSlug()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['text'] = $this->getMockHandler('TextHandler', 'slug');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->slug(null);
    }

    public function testStacked()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'stacked');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->stacked(null);
    }

    public function testStackItems()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'stackItems');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->stackItems(null);
    }

    public function testTestJson()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['text'] = $this->getMockHandler('TextHandler', 'testJson');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->testJson(null);
    }

    public function testThumbnail()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['image'] = $this->getMockHandler('ImageHandler', 'thumbnail');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->thumbnail(null, null, null, null);
    }

    public function testToken()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['user'] = $this->getMockHandler('UserHandler', 'token');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->token();
    }

    public function testTrans()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'trans');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->trans(null, null);
    }

    public function testTrim()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['record'] = $this->getMockHandler('RecordHandler', 'excerpt');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->trim(null, null);
    }

    public function testTwig()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'twig');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->twig(null, null);
    }

    public function testWidget()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['html'] = $this->getMockHandler('HtmlHandler', 'widget');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->widget(null, null);
    }

    public function testYmllink()
    {
        $app = $this->getApp();
        $handlers = $this->getTwigHandlers($app);
        $handlers['admin'] = $this->getMockHandler('AdminHandler', 'ymllink');
        $twig = new TwigExtension($app, $handlers, true);

        $twig->ymllink(null, null);
    }

    /**
     * @param string $name
     * @param string $method
     *
     * @return \PHPUnit_Framework_MockObject_Builder_InvocationMocker
     */
    protected function getMockHandler($name, $method)
    {
        $app = $this->getApp();
        $name = 'Bolt\\Twig\\Handler\\' . $name;
        $handler = $this->getMock($name, [$method], [$app]);
        $handler
            ->expects($this->once())
            ->method($method)
            ->willReturn(true)
        ;

        return $handler;
    }
}
