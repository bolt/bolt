<?php
namespace Bolt\Tests\Helper;

use Bolt\Helpers\MenuBuilder;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Helper/MenuBuilder.
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
     */
    public function testpopulateItemFromRecord($expected, $content, $item, $link)
    {
        $app = $this->getApp();
        $app['request'] = Request::createFromGlobals();

        if (false !== $content) {
            $contentMock = $this->getMock('Bolt\Legacy\Content', ['getContent', 'link'], [$app], '', false);
            $contentMock->expects($this->once())
                ->method('link')
                ->will($this->returnValue($link));

            foreach ($content as $k => $v) {
                $contentMock[$k] = $v;
            }
        }

        $storage = $this->getMock('Bolt\Storage', ['getContent'], [$app]);
        $storage->expects($this->once())
            ->method('getContent')
            ->will($this->returnValue($contentMock));

        $app['storage'] = $storage;

        $mb = new MenuBuilder($app);
        $method = new \ReflectionMethod(
            get_class($mb),
            'populateItemFromRecord'
        );
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke($mb, $item, 'dummy'));
    }
}
