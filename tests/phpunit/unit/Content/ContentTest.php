<?php

namespace Bolt\Tests\Content;

use Bolt\Legacy\Content;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ContentTest extends BoltUnitTest
{
    public function testgetValues()
    {
        $app = $this->getApp();
        $content = new Content($app, 'pages');

        $content->setValue('title', 'Test Page');
        $content->setValue('image', ['file' => 'image1.jpg', 'title' => 'Test image']);

        $values = $content->getValues(true);

        $this->assertEquals('Test Page', $values['title']);
        $this->assertEquals('{"file":"image1.jpg","title":"Test image"}', $values['image']);
    }

    public function testsetValues()
    {
    }

    public function testsetValue()
    {
    }

    public function testsetFromPost()
    {
    }

    public function testsetContenttype()
    {
    }

    public function testsetTaxonomy()
    {
    }

    public function testsortTaxonomy()
    {
    }

    public function testsetRelation()
    {
    }

    public function testgetTaxonomyType()
    {
    }

    public function testsetGroup()
    {
    }

    public function testgetDecodedValue()
    {
    }

    public function testGetRenderedValue()
    {
        $app = $this->getApp();
        $mockContent = $this->getMockBuilder(Content::class)
            ->setConstructorArgs([$app, 'pages'])
            ->setMethods(['getDecodedValue'])
            ->getMock()
        ;
        $mockContent
            ->expects($this->atLeastOnce())
            ->method('getDecodedValue')
            ->with('title')
        ;

        /** @var \Bolt\Legacy\Content $mockContent */
        $mockContent->setValue('title', 'koala');
        $mockContent->getRenderedValue('title');
    }

    public function testpreParse()
    {
    }

    public function testgetTemplateContext()
    {
    }

    public function testget()
    {
    }

    public function testgetTitle()
    {
    }

    public function testgetTitleColumnName()
    {
    }

    public function testgetImage()
    {
    }

    public function testgetReference()
    {
    }

    public function testeditlink()
    {
    }

    public function testlink()
    {
    }

    public function testprevious()
    {
    }

    public function testnext()
    {
    }

    public function testrelated()
    {
    }

    public function testfieldinfo()
    {
    }

    public function testfieldtype()
    {
    }

    public function testexcerpt()
    {
    }

    public function testrss_safe()
    {
    }

    public function testweighSearchResult()
    {
    }

    public function testgetSearchResultWeight()
    {
    }

    public function testoffsetExists()
    {
    }

    public function testoffsetGet()
    {
    }

    public function testoffsetSet()
    {
    }

    public function testoffsetUnset()
    {
    }

    public function testExcerptGracefulMiscallOfStripFields()
    {
        $app = $this->getApp();

        /** @var Content $content */
        $content = $app['storage']->getEmptyContent('pages');
        $content->setValue('body', 'dummy body');
        /** @var \Twig_Markup $result */
        $result = $content->getExcerpt(200, null, null, 'miscall stripfield as string');

        $this->assertEquals('dummy body', (string) $result);
    }

    public function testExcerptStripFields()
    {
        $app = $this->getApp();

        /** @var Content $content */
        $content = $app['storage']->getEmptyContent('pages');
        $content->setValue('body', 'dummy body');
        $content->setValue('title', 'dummy title');

        $result = $content->getExcerpt(200, null, null, ['body']);

        $this->assertNotContains('dummy body', (string) $result);
    }

    public function testNullExcerptStripFields()
    {
        $app = $this->getApp();

        /** @var Content $content */
        $content = $app['storage']->getEmptyContent('pages');
        $content->setValue('body', 'dummy');
        $result = $content->getExcerpt();

        $this->assertEquals('dummy', (string) $result);
    }
}
