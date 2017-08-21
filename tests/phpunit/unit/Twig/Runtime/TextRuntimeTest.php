<?php

namespace Bolt\Tests\Twig\Runtime;

use Bolt\Common\Json;
use Bolt\Tests\BoltUnitTest;
use Bolt\Twig\Runtime\TextRuntime;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Class to test Bolt\Twig\Runtime\TextRuntime.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TextRuntimeTest extends BoltUnitTest
{
    /** @var MockObject */
    public $phpMock;

    public function setUp()
    {
        $this->phpMock = \PHPUnit_Extension_FunctionMocker::start($this, 'Bolt\Twig\Runtime')
            ->mockFunction('setlocale')
            ->getMock()
        ;
    }

    public function testJsonDecode()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $array = [
            'koala'     => 'gum leaves',
            'extension' => 'Clippy',
        ];

        $result = $handler->jsonDecode(Json::dump($array));
        $this->assertSame($array, $result);
    }

    public function testLocaleDateTimeStringNoFormat()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->localeDateTime('2012-06-14 09:07:55');
        $this->assertSame('June 14, 2012 09:07', $result);
    }

    public function testLocaleDateTimeStringWithFormat()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->localeDateTime('2012-06-14 09:07:55', '%Y-%m-%d %H:%M');
        $this->assertSame('2012-06-14 09:07', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLocaleDateTimeCdo()
    {
        $this->phpMock
            ->expects($this->any())
            ->method('setlocale')
            ->will($this->returnValue(false))
        ;

        $app = $this->getApp();
        $logger = $this->getMockMonolog();
        $logger
            ->expects($this->once())
            ->method('error')
        ;
        $this->setService('logger.system', $logger);
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->localeDateTime('2012-06-14 09:07:55', '%B %e, %Y %H:%M');
        $this->assertSame('2012-06-14 09:07:55', $result);
    }

    public function testLocaleDateTimeObjectNoFormat()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $dateTime = new \DateTime('2012-06-14 09:07:55');
        $result = $handler->localeDateTime($dateTime);
        $this->assertSame('June 14, 2012 09:07', $result);
    }

    public function testLocaleDateTimeObjectWithFormat()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $dateTime = new \DateTime('2012-06-14 09:07:55');
        $result = $handler->localeDateTime($dateTime, '%Y-%m-%d %H:%M');
        $this->assertSame('2012-06-14 09:07', $result);
    }

    public function testPregReplaceNoReplacementNoLimit()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->pregReplace('One koala, two koalas, three koalas, four!', '#(ko)a(la|las)#');
        $this->assertSame('One , two s, three s, four!', $result);
    }

    public function testPregReplaceWithReplacementNoLimit()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->pregReplace('One koala, two koalas, three koalas, four!', '#(ko)a(la|las)#', 'clippy');
        $this->assertSame('One clippy, two clippys, three clippys, four!', $result);
    }

    public function testPregReplaceWithReplacementWithLimit()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->pregReplace('One koala, two koalas, three koalas, four!', '#(ko)a(la|las)#', 'clippy', 2);
        $this->assertSame('One clippy, two clippys, three koalas, four!', $result);
    }

    public function testSafeStringNotStrictNoExtra()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->safeString('Skämt åsido satan vilket uruselt tillvägagångsätt');
        $this->assertSame('Skaemt aasido satan vilket uruselt tillvaegagaangsaett', $result);
    }

    public function testSafeStringWithStrictNoExtra()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->safeString('Skämt åsido satan vilket uruselt tillvägagångsätt', true);
        $this->assertSame('skaemt-aasido-satan-vilket-uruselt-tillvaegagaangsaett', $result);
    }

    public function testSafeStringWithStrictWithExtra()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->safeString('Skämt åsido $@tan vilket uruselt tillvägagångsätt', true, '$');
        $this->assertSame('skaemt-aasido-$attan-vilket-uruselt-tillvaegagaangsaett', $result);
    }

    public function testSlugString()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->slug('Köala & Clippy úp thé trèé');
        $this->assertSame('koeala-clippy-up-the-tree', $result);
    }

    public function testSlugArray()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $slug = ['Köala & Clippy', 'úp thé trèé'];
        $result = $handler->slug($slug);
        $this->assertSame('koeala-clippy-up-the-tree', $result);
    }

    public function testTestJsonValid()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $array = ['koala', 'clippy'];
        $result = $handler->testJson(Json::dump($array));
        $this->assertTrue($result);
    }

    public function testTestJsonInvalid()
    {
        $app = $this->getApp();
        $handler = new TextRuntime($app['logger.system'], $app['slugify']);

        $result = $handler->testJson('koala');
        $this->assertFalse($result);
    }
}
