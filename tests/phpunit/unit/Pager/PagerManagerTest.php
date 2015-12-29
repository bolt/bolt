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
        $manager = $this->createPagerManager(Request::create('/'));
        $this->assertEquals($expected, $manager->makeParameterId($suffix));
    }

    public function testOffsetSet()
    {
        $manager = $this->createPagerManager(Request::create('/'));
        $manager['data'] = ['current' => '1', 'totalpages' => 2, 'for' => 'data'];
        var_dump($manager->getPagers());
    }

    /**
     * @return array
     */
    public function decodeHttpQueryProvider()
    {
        return [
            ['?nopagerpar=2', [[]]],
            ['?page',
                [
                    '' => ['for' => 'page', 'current' => ''],
                ],
            ],
            ['?page=3',
                [
                    '' => ['for' => 'page', 'current' => 3],
                ],
            ],
            ['?page=3&nopage=A',
                [
                    '' => ['for' => 'page', 'current' => 3],
                ],
            ],
            ['?page_some=2&nopage',
                [
                    'some' => ['for' => 'some', 'current' => 2],
                ],
            ],
            ['?page_some=2&page=3&nopage',
                [
                    'some' => ['for' => 'some', 'current' => 2],
                    '' => ['for' => 'page', 'current' => 3],
                ],
            ],
            ['?page_some=2&page_others=3&nopage',
                [
                    'some' => ['for' => 'some', 'current' => 2],
                    'others' => ['for' => 'others', 'current' => 3],
                ],
            ],
        ];
    }

    /**
     * @dataProvider decodeHttpQueryProvider
     */
    public function testDecodeHttpQuery($query, $expected)
    {
        $manager = $this->createPagerManager(Request::create($query));
        $mirror = new PagerManager($this->getApp());
        foreach ($expected as $parid => $pager) {
            if ($pager) {
                $mirror[$parid] = $pager;
            }
        }
        $this->assertEquals($mirror->getPagers(), $manager->decodeHttpQuery());
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


        return $tests;
    }

    /**
     * @dataProvider makelinkProvider
     */
    public function testMakelink($linkFor, $query, $expected)
    {
        $manager = $this->createPagerManager(Request::create($query));
        $this->assertEquals($expected, $manager->makelink($linkFor));
    }

    private function createPagerManager($request)
    {
        $app = $this->getApp();
        $app['request'] = $request;

        return new PagerManager($app);
    }
}
