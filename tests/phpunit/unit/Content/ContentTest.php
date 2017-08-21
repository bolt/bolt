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
}
