<?php

namespace Bolt\Tests\Pager;

use Bolt\Pager;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * PagerTest for class Pager
 *
 * @package Bolt\Tests\Pager
 */
class PagerTest extends BoltUnitTest
{
    /**
     * @return array
     */
    public function makeParameterIdProvider()
    {
        return [
            ['page_a', 'a'],
            ['page_1', 1],
            ['page', ''],
        ];
    }

    /**
     * @dataProvider makeParameterIdProvider
     */
    public function testMakeParameterId($expected, $suffix)
    {
        $this->assertEquals($expected, Pager::makeParameterId($suffix));
    }

    /**
     * @return array
     */
    public function makelinkProvider()
    {
        $tests = [];

        $tests[] = [
            'bylink',
            [
                'link' => 'bylink',
                'for'  => 'foo'
            ],
            [
                'a' => 'b',
            ],
        ];

        $tests[] = [
            '?a=b&page_acategory=',
            [
                'for'  => 'acategory'
            ],
            [
                'a' => 'b',
            ],
        ];

        $tests[] = [
            '?a=b&page=',
            [
                'for'  => ''
            ],
            [
                'a' => 'b',
            ],
        ];

        $tests[] = [
            '?a=b&page_acategory=',
            [
                'for'  => 'acategory',
            ],
            [
                'a'              => 'b',
                'page_acategory' => 5
            ],
        ];

        $tests[] = [
            '?a=b&page_acategory=',
            [
                'for'  => 'acategory',
            ],
            [
                'a'    => 'b',
                'page' => 5
            ],
        ];

        $tests[] = [
            '?a=b&page_acategory=6&page=',
            [
                'for'  => '',
            ],
            [
                'a'              => 'b',
                'page'           => 5,
                'page_acategory' => 6
            ],
        ];

        $tests[] = [
            '?a=b&page=5&page_acategory=',
            [
                'for'  => 'acategory',
            ],
            [
                'a'              => 'b',
                'page'           => 5,
                'page_acategory' => 6
            ],
        ];

        return $tests;
    }

    /**
     * @dataProvider makelinkProvider
     */
    public function testMakelink($expected, $pageArray, $params)
    {
        $app = $this->getApp();
        $_GET = $params;
        $app['request'] = Request::createFromGlobals();

        $pager = new Pager($pageArray, $app);

        $this->assertEquals($expected, $pager->makelink());
    }

    /**
     * From here on its the recursive part where you can get some headaches
     */
    public function testRecursiveMakelink()
    {
        $test = [
            'for'        => 'acategory',
            'showing_to' => [
                'link'  => 'reclink',
            ],
        ];

        $app = $this->getApp();
        $app['request'] = Request::createFromGlobals();

        $pager = new Pager($test, $app);
        $this->assertInstanceOf('Bolt\Pager', $pager->showing_to);
    }
}
