<?php

namespace Bolt\Tests\Pager;

use Bolt\Pager\PagerManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * PagerManagerFunctionalTest for class PagerManager
 *
 * @package Bolt\Tests\Pager
 * @author Rix Beck <rix@neologik.hu>
 */
class PagerManagerFunctionalTest extends PagerManagerTestBase
{

    /**
     * @return array
     */
/*    public function decodeHttpQueryProvider()
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
    }*/

    /**
     * @x-dataProvider decodeHttpQueryProvider
     */
/*    public function testDecodeHttpQuery($query, $expected)
    {
        $manager = $this->createPagerManager(Request::create($query));
        $mirror = new PagerManager($this->getApp());
        foreach ($expected as $parid => $pager) {
            if ($pager) {
                $mirror[$parid] = $pager;
            }
        }
        $this->assertEquals($mirror->getPagers(), $manager->decodeHttpQuery());
    }*/

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
}
