<?php

namespace Bolt\Tests\Menu;

use Bolt\Legacy\Content;
use Bolt\Legacy\Storage;
use Bolt\Menu\MenuBuilder;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Menu/MenuBuilder.
 *
 * @author Sufijen Bani <bolt@sbani.net>
 */
class MenuBuilderTest extends BoltUnitTest
{
    /**
     * @return array
     */
    public static function populateItemFromRecordProvider()
    {
        $tests = [];

        $tests[] = [
            [
                'title' => 'a',
                'label' => 'b',
                'link'  => 'c',
            ],
            false,
            [
                'title' => 'a',
                'label' => 'b',
                'link'  => 'c',
            ],
            'f',
        ];

        $tests[] = [
            [
                'title' => 'a',
                'label' => 'b',
                'link'  => 'f',
            ],
            [
                'title'    => 'd',
                'subtitle' => 'e',
            ],
            [
                'title' => 'a',
                'label' => 'b',
                'link'  => 'c',
            ],
            'f',
        ];

        $tests[] = [
            [
                'title' => 'a',
                'label' => '',
                'link'  => 'f',
            ],
            [
                'title'    => '',
                'subtitle' => 'e',
            ],
            [
                'title' => 'a',
                'label' => '',
                'link'  => 'c',
            ],
            'f',
        ];

        $tests[] = [
            [
                'title' => '',
                'label' => 'b',
                'link'  => 'f',
            ],
            [
                'title'    => 'd',
                'subtitle' => '',
            ],
            [
                'title' => '',
                'label' => 'b',
                'link'  => 'c',
            ],
            'f',
        ];

        $tests[] = [
            [
                'title' => 'a',
                'label' => 'd',
                'link'  => 'f',
            ],
            [
                'title'    => 'd',
                'subtitle' => 'e',
            ],
            [
                'title' => 'a',
                'label' => '',
                'link'  => 'c',
            ],
            'f',
        ];

        $tests[] = [
            [
                'title' => 'e',
                'label' => 'b',
                'link'  => 'f',
            ],
            [
                'title'    => 'd',
                'subtitle' => 'e',
            ],
            [
                'title' => '',
                'label' => 'b',
                'link'  => 'c',
            ],
            'f',
        ];

        $tests[] = [
            [
                'title' => 'a',
                'label' => '',
                'link'  => 'f',
            ],
            [
                'title'    => '',
                'subtitle' => 'e',
            ],
            [
                'title' => 'a',
                'label' => '',
                'link'  => 'c',
            ],
            'f',
        ];

        $tests[] = [
            [
                'title' => 'a',
                'label' => 'b',
                'link'  => 'f',
            ],
            [
                'title'    => '',
                'subtitle' => '',
            ],
            [
                'title' => 'a',
                'label' => 'b',
                'link'  => 'c',
            ],
            'f',
        ];

        $tests[] = [
            [
                'title' => 'a',
                'label' => 'b',
                'link'  => 'f',
            ],
            [
                'title'    => '',
                'subtitle' => '',
            ],
            [
                'title' => 'a',
                'label' => 'b',
                'link'  => 'c',
            ],
            'f',
        ];

        return $tests;
    }

    /**
     * @dataProvider populateItemFromRecordProvider
     *
     * @param array      $expected
     * @param array|null $content
     * @param array      $item
     * @param string     $link
     *
     * @throws \ReflectionException
     */
    public function testPopulateItemFromRecord($expected, $content, $item, $link)
    {
        $app = $this->getApp();
        $app['request'] = Request::createFromGlobals();

        $contentMock = null;
        if ($content !== false) {
            $contentMock = $this->getMockBuilder(Content::class)
                ->setMethods(['getContent', 'link'])
                ->setConstructorArgs([$app])
                ->getMock()
            ;
            $contentMock->expects($this->once())
                ->method('link')
                ->will($this->returnValue($link));

            foreach ($content as $k => $v) {
                $contentMock[$k] = $v;
            }
        }

        $storage = $this->getMockBuilder(Storage::class)
            ->setMethods(['getContent', 'link'])
            ->setConstructorArgs([$app])
            ->getMock()
        ;
        $storage->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($contentMock));

        $this->setService('storage', $storage);

        $mb = new MenuBuilder($app);

        $method = new \ReflectionMethod(
            get_class($mb),
            'populateItemFromRecord'
        );
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke($mb, $item, 'dummy'));
    }
}
