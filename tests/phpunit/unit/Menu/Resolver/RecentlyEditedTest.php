<?php

namespace Bolt\Tests\Menu\Builder;

use Bolt\Collection\Bag;
use Bolt\Menu\MenuEntry;
use Bolt\Menu\Resolver\RecentlyEdited;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository;
use Bolt\Tests\BoltUnitTest;
use Doctrine\DBAL\Query\QueryBuilder;
use Parsedown;

/**
 * Class to test src/Menu/Resolver/RecentlyEdited.
 *
 * @author Jarek Jakubowski <egger1991@gmail.com>
 */
class RecentlyEditedTest extends BoltUnitTest
{
    public static function resolveProvider()
    {
        return [
            [
                MenuEntry::create('main')
                    ->add(MenuEntry::create('entry1'))->parent()
                    ->add(MenuEntry::create('other')
                        ->setGroup(true)
                        ->add(MenuEntry::create('othergroupentry'))->parent())
                    ->parent(),
                MenuEntry::create('main')
                    ->add(MenuEntry::create('entry1'))->parent()
                    ->add(MenuEntry::create('other')
                        ->setGroup(true)
                        ->add(MenuEntry::create('othergroupentry'))->parent())
                    ->parent(),
                Bag::fromRecursive([
                    'entry1' => [
                        'show_in_menu' => true,
                    ],
                    'othergroupentry' => [
                        'show_in_menu' => false,
                    ],
                ]),
            ],
            [
                MenuEntry::create('main')
                    ->add(MenuEntry::create('entry1')
                        ->add(MenuEntry::create('singleton'))->parent())
                    ->parent()
                    ->add(MenuEntry::create('entry2')
                        ->add(MenuEntry::create('singleton'))->parent())
                    ->parent()
                    ->add(MenuEntry::create('other')
                        ->setGroup(true)
                        ->add(MenuEntry::create('othergroupentry1')
                            ->add(MenuEntry::create('singleton'))->parent())
                        ->parent()
                        ->add(MenuEntry::create('othergroupentry2')
                            ->add(MenuEntry::create('singleton'))->parent())
                        ->parent())
                    ->parent(),
                MenuEntry::create('main')
                    ->add(MenuEntry::create('entry1'))->parent()
                    ->add(MenuEntry::create('entry2'))->parent()
                    ->add(MenuEntry::create('other')
                        ->setGroup(true)
                        ->add(MenuEntry::create('othergroupentry1'))->parent()
                        ->add(MenuEntry::create('othergroupentry2'))->parent())
                    ->parent(),
                Bag::fromRecursive([
                    'entry1' => [
                        'show_in_menu' => true,
                        'singleton'    => true,
                    ],
                    'othergroupentry1' => [
                        'show_in_menu' => false,
                        'singleton'    => true,
                    ],
                    'entry2' => [
                        'show_in_menu' => true,
                        'singleton'    => true,
                    ],
                    'othergroupentry2' => [
                        'show_in_menu' => false,
                        'singleton'    => true,
                    ],
                ]),
            ],
            [
                MenuEntry::create('main')
                    ->add(MenuEntry::create('entry1'))->parent()
                    ->add(MenuEntry::create('group1')
                        ->setGroup(true)
                        ->add(MenuEntry::create('group1entry1'))->parent()
                        ->add(MenuEntry::create('group1entry2')
                            ->add(MenuEntry::create('singleton'))->parent())
                        ->parent())
                    ->parent()
                    ->add(MenuEntry::create('entry2')
                        ->add(MenuEntry::create('singleton'))->parent())
                    ->parent()
                    ->add(MenuEntry::create('entry3'))->parent()
                    ->add(MenuEntry::create('group2')
                        ->setGroup(true)
                        ->add(MenuEntry::create('group2entry1'))->parent()
                        ->add(MenuEntry::create('group2entry2'))->parent())
                    ->parent()
                    ->add(MenuEntry::create('entry4'))->parent()
                    ->add(MenuEntry::create('other')
                        ->setGroup(true)
                        ->add(MenuEntry::create('othergroupentry1'))->parent()
                        ->add(MenuEntry::create('othergroupentry2'))->parent())
                    ->parent(),
                MenuEntry::create('main')
                    ->add(MenuEntry::create('entry1'))->parent()
                    ->add(MenuEntry::create('group1')
                        ->setGroup(true)
                        ->add(MenuEntry::create('group1entry1'))->parent()
                        ->add(MenuEntry::create('group1entry2'))->parent())
                    ->parent()
                    ->add(MenuEntry::create('entry2'))->parent()
                    ->add(MenuEntry::create('entry3'))->parent()
                    ->add(MenuEntry::create('group2')
                        ->setGroup(true)
                        ->add(MenuEntry::create('group2entry1'))->parent()
                        ->add(MenuEntry::create('group2entry2'))->parent())
                    ->parent()
                    ->add(MenuEntry::create('entry4'))->parent()
                    ->add(MenuEntry::create('other')
                        ->setGroup(true)
                        ->add(MenuEntry::create('othergroupentry1'))->parent()
                        ->add(MenuEntry::create('othergroupentry2'))->parent())
                    ->parent(),
                Bag::fromRecursive([
                    'entry1' => [
                        'show_in_menu' => true,
                    ],
                    'othergroupentry1' => [
                        'show_in_menu' => false,
                    ],
                    'group1entry1' => [
                        'show_in_menu' => 'group1',
                    ],
                    'entry2' => [
                        'show_in_menu' => true,
                        'singleton'    => true,
                    ],
                    'entry3' => [
                        'show_in_menu' => true,
                    ],
                    'group2entry1' => [
                        'show_in_menu' => 'group2',
                    ],
                    'group1entry2' => [
                        'show_in_menu' => 'group1',
                        'singleton'    => true,
                    ],
                    'othergroupentry2' => [
                        'show_in_menu' => false,
                    ],
                    'group2entry2' => [
                        'show_in_menu' => 'group2',
                    ],
                    'entry4' => [
                        'show_in_menu' => true,
                    ],
                ]),
            ],
        ];
    }

    /**
     * @dataProvider resolveProvider
     *
     * @param MenuEntry $expectedMain
     * @param Bag       $contentTypes
     */
    public function testResolve($expectedMain, $entryMain, $contentTypes)
    {
        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->any())
            ->method('setMaxResults')
            ->willReturn($qb)
        ;
        $qb->expects($this->any())
            ->method('orderBy')
            ->willReturn($qb)
        ;

        $repo = $this->createMock(Repository::class);
        $repo->expects($this->any())
            ->method('findWith')
            ->willReturn([new Content()])
        ;
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb)
        ;

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo)
        ;

        $re = new RecentlyEdited($em, $this->createMock(ParseDown::class));

        $expected = MenuEntry::create('root')
            ->add(
                MenuEntry::create('content')->add($expectedMain)->parent()
            )
            ->parent()
        ;
        $entry = MenuEntry::create('root')
            ->add(
                MenuEntry::create('content')->add($entryMain)->parent()
            )
            ->parent()
        ;

        $re->resolve($entry, $contentTypes);

        $this->assertEquals($this->extractTestedDataFromMenuEntries($expected), $this->extractTestedDataFromMenuEntries($entry));
    }

    /**
     * @param MenuEntry $menuEntry
     *
     * @return array
     */
    private function extractTestedDataFromMenuEntries($menuEntry)
    {
        return [
            'name'     => $menuEntry->getName(),
            'group'    => $menuEntry->isGroup(),
            'children' => array_map(function ($child) {
                return $this->extractTestedDataFromMenuEntries($child);
            }, array_filter($menuEntry->children(), function ($child) {
                return $child->getName() !== 'recent';
            })),
        ];
    }
}
