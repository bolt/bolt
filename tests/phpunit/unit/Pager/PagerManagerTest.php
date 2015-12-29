<?php

namespace Bolt\Tests\Pager;

use Bolt\Pager\PagerManager;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * PagerManagerTest for class PagerManager
 *
 * @package Bolt\Tests\Pager
 * @author Rix Beck <rix@neologik.hu>
 */
class PagerManagerTest extends BoltUnitTest
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
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $manager = new PagerManager($app);
        $this->assertEquals($expected, $manager->makeParameterId($suffix));
    }

    /**
     * @return array
     */
    public function makelinkProvider()
    {
        $tests = [];

        $tests[] = [
            'acategory',
            '?a=b',
            '?a=b&page_acategory=',
        ];

        $tests[] = [
            '',
            '?a=b',
            '?a=b',
        ];

        $tests[] = [
            'acategory',
            '?a=b&page_acategory=5',
            '?a=b&page_acategory=',
        ];

        $tests[] = [
            'acategory',
            '?a=b&page_acategory=5',
            '?a=b&page_acategory=',
        ];

        /*
                $tests[] = [
                    '?a=b&page_acategory=',
                    [
                        'for' => 'acategory',
                    ],
                    [
                        'a' => 'b',
                        'page' => 5
                    ],
                ];

                $tests[] = [
                    '?a=b&page_acategory=6&page=',
                    [
                        'for' => '',
                    ],
                    [
                        'a' => 'b',
                        'page' => 5,
                        'page_acategory' => 6
                    ],
                ];

                $tests[] = [
                    '?a=b&page=5&page_acategory=',
                    [
                        'for' => 'acategory',
                    ],
                    [
                        'a' => 'b',
                        'page' => 5,
                        'page_acategory' => 6
                    ],
                ];
                */

        return $tests;
    }

    /**
     * @dataProvider makelinkProvider
     */
    public function testMakelink($linkFor, $query, $pagers, $expected)
    {
        $app = $this->getApp();
        $app['request'] = Request::create($query);

        $manager = new PagerManager($app);

        $this->assertEquals($expected, $manager->makelink($linkFor));
    }

}
