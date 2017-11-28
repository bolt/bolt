<?php

namespace Bolt\Tests\Menu\Builder;

use Bolt\Collection\Bag;
use Bolt\Menu\Builder\AdminContent;
use Bolt\Menu\MenuEntry;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Menu/Builder/AdminContent.
 *
 * @author Jarek Jakubowski <egger1991@gmail.com>
 */
class AdminContentTest extends BoltUnitTest
{
    public static function buildProvider()
    {
        return [
            [
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
                    ],
                    'othergroupentry1' => [
                        'show_in_menu' => false,
                    ],
                    'entry2' => [
                        'show_in_menu' => true,
                    ],
                    'othergroupentry2' => [
                        'show_in_menu' => false,
                    ],
                ]),
            ],
            [
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
                    ],
                    'entry3' => [
                        'show_in_menu' => true,
                    ],
                    'group2entry1' => [
                        'show_in_menu' => 'group2',
                    ],
                    'group1entry2' => [
                        'show_in_menu' => 'group1',
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
     * @dataProvider buildProvider
     *
     * @param MenuEntry $expectedMain
     * @param Bag       $contentTypes
     */
    public function testBuild($expectedMain, $contentTypes)
    {
        $ac = new AdminContent($contentTypes);
        $result = $ac->build(MenuEntry::create('root'));

        $expected = MenuEntry::create('root')
            ->add(
                MenuEntry::create('content')->add($expectedMain)->parent()
            )
            ->parent()
        ;

        $this->assertEquals($this->extractTestedDataFromMenuEntries($expected), $this->extractTestedDataFromMenuEntries($result));
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
                return $child->getName() !== 'new' && $child->getName() !== 'view';
            })),
        ];
    }
}
