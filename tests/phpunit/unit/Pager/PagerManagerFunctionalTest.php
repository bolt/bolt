<?php

namespace Bolt\Tests\Pager;

use Bolt\Pager\PagerManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * PagerManagerFunctionalTest for class PagerManager
 *
 * @package Bolt\Tests\Pager
 *
 * @author Rix Beck <rix@neologik.hu>
 */
class PagerManagerFunctionalTest extends PagerManagerTestBase
{
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
                    ''     => ['for' => 'page', 'current' => 3],
                ],
            ],
            ['?page_some=2&page_others=3&nopage',
                [
                    'some'   => ['for' => 'some', 'current' => 2],
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
        $manager = new PagerManager();
        $manager->initialize(Request::create($query));

        $mirror = new PagerManager();
        $req = &$this->getProtectedAttrRef($mirror, 'request');
        $req = Request::create($query);

        foreach ($expected as $parid => $pager) {
            if ($pager) {
                $mirror[$parid] = $this->createPager($pager);
            }
        }

        $this->assertEquals($mirror->getPagers(), $manager->getPagers());
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
        $manager = $this->createPagerManager();
        $manager->initialize(Request::create($query));
        $this->assertEquals($expected, $manager->makeLink($linkFor));
    }

    public function getPagerProvider()
    {
        return [
            [
                '?a=b&page_acategory=5',
                [
                    'some'   => ['for' => 'some', 'current' => 2],
                    'others' => ['for' => 'others', 'current' => 3, 'totalpages' => 12],
                ],
                '',
                ['for' => 'others', 'current' => 3, 'totalpages' => 12],
            ],
            [
                '?a=b&page_acategory=5',
                [
                    'some'   => ['for' => 'some', 'current' => 2],
                    'others' => ['for' => 'others', 'current' => 3, 'totalpages' => 12],
                ],
                'acategory',
                ['for' => 'acategory', 'current' => 5 ],
            ],
        ];
    }

    /**
     * @dataProvider getPagerProvider
     */
    public function testGetPager($query, $pagers, $contextId, $expected)
    {
        $manager = $this->createPagerManager();
        $manager->initialize(Request::create($query));
        foreach ($pagers as $ctxid => $pager) {
            $manager[$ctxid] = $this->createPager($pager);
        }
        $expected = $this->createPager($expected);
        $expected->setManager($manager);
        $this->assertEquals($expected, $manager->getPager($contextId));
    }

    public function getCurrentPageProvider()
    {
        return [
            [
                '?a=b&page_acategory',
                '',
                1,
            ],
            [
                '?a=b&page_acategory=5',
                'acategory',
                5,
            ],
            [
                '?a=b&page_acategory=5',
                'bcategory',
                1,
            ],
            [
                '?a=b&page=5&page_me=2',
                '',
                5,
            ],
        ];
    }

    /**
     * @dataProvider getCurrentPageProvider
     */
    public function testGetCurrentPage($query, $contextId, $expected)
    {
        $manager = $this->createPagerManager();
        $manager->initialize(Request::create($query));
        $this->assertEquals($expected, $manager->getCurrentPage($contextId));
    }
}
