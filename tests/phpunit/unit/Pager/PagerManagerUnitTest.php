<?php
/**
 * Created by PhpStorm.
 * User: rix
 * Date: 2015.12.29.
 * Time: 22:34
 */

namespace Bolt\Tests\Pager;

use Bolt\Pager\Pager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class PagerManagerUnitTest
 *  is intended for testing Bolt\Pager\PagerManager methods in separated way so it is testing ONE method at once.
 *
 * @package Bolt\Tests\Pager
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
        $manager = $this->createPagerManager(Request::create('/'));
        $this->assertEquals($expected, $manager->makeParameterId($suffix));
    }

    public function testMakelink()
    {
        $builder = $this->createPagerManagerMockBuilder(Request::create('/'));

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

        $expected = $decoded = [
            'page' => new Pager(['current' => 2, 'for' => 'page', 'manager' => $manager]),
            'page_wine' => new Pager(['current' => 9999, 'for' => 'wine', 'manager' => $manager]),
        ];

        $manager->expects($this->once())
            ->method('decodeHttpQuery')
            ->willReturn($decoded);

        $this->assertEquals($expected, $manager->decodeHttpQuery());
    }

    public function testEncodeHttpQuery()
    {
        list($manager, $expected) = $this->prepareEncodeHttpQuery();
        $this->assertEquals($expected, $manager->encodeHttpQuery());
    }

    public function testToString()
    {
        list($manager, $expected) = $this->prepareEncodeHttpQuery();
        $this->assertEquals('?'.$expected, (string) $manager);
    }

    public function testOffsetSet()
    {
        $manager = $this->createPagerManagerMockBuilder()
            ->setMethods(['decodeHttpQuery'])
            ->getMock();

        $base = ['current' => 123];
        $manager['some'] = $base;

        $expected['page_some'] = new Pager(array_merge($base, ['manager' => $manager]));
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
                [],
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
                    'A' => ['for' => 'page' ],
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
            ->setMethods(['decodeHttpQuery'])
            ->getMock();

        $pagers = &$this->getProtectedAttrRef($manager, 'pagers');
        $pagers = $data;
        $result = $this->methodInvoker($manager, 'findInitializedPagerId', []);

        $this->assertEquals($expected, $result);
    }

    private function prepareEncodeHttpQuery()
    {
        $manager = $this->createPagerManagerMockBuilder(Request::create('/?some=thing'))
            ->setMethods(['decodeHttpQuery', 'remapPagers'])
            ->getMock();

        $expected = 'some=thing&page=2&page_wine=9999';
        $decoded = [
            'page' => new Pager(['current' => 2, 'for' => 'page', 'manager' => $manager]),
            'page_wine' => new Pager(['current' => 9999, 'for' => 'wine', 'manager' => $manager]),
        ];

        $manager->expects($this->any())
            ->method('decodeHttpQuery')
            ->willReturn($decoded);

        $manager->expects($this->once())
            ->method('remapPagers')
            ->willReturn(['page' => 2, 'page_wine' => 9999]);

        return [$manager, $expected];
    }

}
