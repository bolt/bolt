<?php

namespace Bolt\Tests\Pager;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class PagerManagerUnitTest
 *  is intended for testing Bolt\Pager\PagerManager methods in separated way so it is testing ONE method at once.
 *
 * @package Bolt\Tests\Pager
 *
 * @author Rix Beck <rix@neologik.hu>
 */
class PagerManagerUnitTest extends PagerManagerTestBase
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
        $manager = $this->createPagerManager();
        $this->assertEquals($expected, $manager->makeParameterId($suffix));
    }

    public function testMakelink()
    {
        $builder = $this->createPagerManagerMockBuilder();
        $this->initApp();

        $manager = $builder
            ->setMethods(['findPagerId', 'encodeHttpQuery'])
            ->getMock();

        $manager
            ->expects($this->once())
            ->method('findPagerId')
            ->willReturn('page_entries');

        $manager
            ->expects($this->once())
            ->method('encodeHttpQuery')
            ->willReturn('data=2');

        $this->assertEquals('?data=2&page_entries=', $manager->makelink());
    }

    public function testDecodeHttpQuery()
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->setMethods(['decodeHttpQuery'])
            ->getMock();
        $this->initApp();

        $expected = $decoded = [
            'page'      => $this->createPager(['current' => 2, 'for' => 'page', 'manager' => $manager]),
            'page_wine' => $this->createPager(['current' => 9999, 'for' => 'wine', 'manager' => $manager]),
        ];

        $manager->expects($this->once())
            ->method('decodeHttpQuery')
            ->willReturn($decoded);

        $this->assertEquals($expected, $manager->decodeHttpQuery());
    }

    public function testEncodeHttpQuery()
    {
        list($manager, $expected, $parms) = $this->prepareEncodeHttpQuery();
        $this->assertEquals($expected, $manager->encodeHttpQuery($parms));
    }

    public function testToString()
    {
        $app = $this->initApp(Request::create('/?some=thing'));
        list($manager, $expected, $parms) = $this->prepareEncodeHttpQuery();
        $manager->initialize($app['request']);
        $this->assertEquals('?' . $expected, (string) $manager);
    }
    public function testOffsetSet()
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->setMethods(['decodeHttpQuery'])
            ->getMock();

        $base = ['current' => 123];
        $manager['some'] = $this->createPager($base);

        $expected = [];
        $expected['page_some'] = $this->createPager($base);
        $expected['page_some']->setManager($manager);
        $this->assertEquals($expected, \PHPUnit_Framework_Assert::readAttribute($manager, 'pagers'));
    }

    public function testOffsetGet()
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->setMethods(['decodeHttpQuery'])
            ->getMock();

        $pagers = &$this->getProtectedAttrRef($manager, 'pagers');

        $refdata = ['current' => 123];
        $pagers['page_dotz'] = $refdata;

        $this->assertEquals($refdata, $manager['dotz']);
    }

    public function testOffsetExists()
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->setMethods(['decodeHttpQuery'])
            ->getMock();

        $pagers = &$this->getProtectedAttrRef($manager, 'pagers');

        $refdata = ['current' => 123];
        $pagers['page_some'] = $refdata;

        $this->assertTrue($manager->offsetExists('some'));
        unset($pagers['page_some']);
        $this->assertFalse($manager->offsetExists('some'));
    }

    public function testOffsetUnset()
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->setMethods(['decodeHttpQuery'])
            ->getMock();

        $pagers = &$this->getProtectedAttrRef($manager, 'pagers');
        $refdata = ['current' => 123];
        $pagers['page_some'] = $refdata;
        unset($manager['some']);
        $this->assertFalse($manager->offsetExists('some'));
    }

    public function testKeys()
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->setMethods(['decodeHttpQuery'])
            ->getMock();

        $pagers = &$this->getProtectedAttrRef($manager, 'pagers');
        $refkeys = range('A', 'J');
        $refdata = array_combine($refkeys, range(1, 10));
        $pagers = $refdata;
        $this->assertEquals($refkeys, $manager->keys());
    }
    /**
     * @return array
     */
    public function findInitializedPagerIdProvider()
    {
        return [
            [
                [[]],
                '',
            ],
            [
                [
                    'A' => ['for' => 'page', 'current' => ''],
                    'B' => ['for' => 'page', 'current' => ''],
                ],
                '',
            ],
            [
                [
                    'A' => ['for' => 'page', 'totalpages' => ''],
                    'B' => ['for' => 'page', 'current' => ''],
                ],
                'A',
            ],
            [
                [
                    'A' => ['for' => 'page', 'totalpages' => ''],
                    'B' => ['for' => 'page', 'current' => '', 'totalpages' => 3],
                ],
                'A',
            ],
            [
                [
                    'A' => ['for' => 'page'],
                    'B' => ['for' => 'page', 'current' => '', 'totalpages' => 3],
                ],
                'B',
            ],
        ];
    }

    /**
     * @dataProvider findInitializedPagerIdProvider
     */
    public function testFindInitializedPagerId($data, $expected)
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->getMock();

        $pagers = &$this->getProtectedAttrRef($manager, 'pagers');
        foreach ($data as $key => $value) {
            $pagers[$key] = $this->createPager($value);
        }
        $result = $this->methodInvoker($manager, 'findInitializedPagerId', []);

        $this->assertEquals($expected, $result);
    }

    public function testRemapPagers()
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->getMock();
        $pagers = &$this->getProtectedAttrRef($manager, 'pagers');
        $pagers = [
            'A' => $this->createPager(['for' => 'A', 'current' => 1]),
            'B' => $this->createPager(['for' => 'B', 'current' => 2]),
            'C' => $this->createPager(['for' => 'C', 'current' => 3]),
        ];
        $result = $this->methodInvoker($manager, 'remapPagers', []);

        $this->assertEquals(['A' => 1, 'B' => 2, 'C' => 3], $result);
    }

    private function prepareEncodeHttpQuery()
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->setMethods(['decodeHttpQuery', 'remapPagers'])
            ->getMock();

        $expected = 'some=thing&page=2&page_wine=9999';
        $decoded = [
            'page'      => $this->createPager(['current' => 2, 'for' => 'page']),
            'page_wine' => $this->createPager(['current' => 9999, 'for' => 'wine']),
        ];

        $manager->expects($this->any())
            ->method('decodeHttpQuery')
            ->willReturn($decoded);

        $manager->expects($this->once())
            ->method('remapPagers')
            ->willReturn(['page' => 2, 'page_wine' => 9999]);

        return [$manager, $expected, [ 'some' => 'thing']];
    }
}
