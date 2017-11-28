<?php

namespace Bolt\Menu\Builder;

use Bolt\Collection\Bag;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator as Trans;

/**
 * Bolt admin (back-end) content menu builder.
 *
 * @internal backwards compatibility not guaranteed on this class presently
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class AdminContent
{
    /** @var Bag */
    private $contentTypes;

    /**
     * Constructor.
     *
     * @param Bag $contentTypes
     */
    public function __construct(Bag $contentTypes)
    {
        $this->contentTypes = $contentTypes;
    }

    /**
     * Build the "Content" menu.
     *
     * @param MenuEntry $root
     *
     * @return MenuEntry
     */
    public function build(MenuEntry $root)
    {
        // Top level header for ContentTypes
        $contentRoot = $root->add(
            MenuEntry::create('content', '')
                ->setLabel(Trans::__('general.phrase.content'))
                ->setIcon('fa:file-text')
                ->setPermission('dashboard')
        );
        $mainContentRoot = $contentRoot->add(
            MenuEntry::create('main')
                ->setPermission('dashboard')
        );

        foreach ($this->contentTypes as $name => $contentType) {
            if ($contentType->get('show_in_menu') !== true) {
                $this->addGroupedMenu($mainContentRoot, $contentType);
            } else {
                $this->addContentType($mainContentRoot, $name, $contentType);
            }
        }

        $this->fillGroupedMenus($mainContentRoot);

        return $root;
    }

    /**
     * Add a ContentType's menu and sub-menu.
     *
     * @param MenuEntry $contentRoot
     * @param string    $contentTypeKey
     * @param Bag       $contentType
     */
    private function addContentType(MenuEntry $contentRoot, $contentTypeKey, Bag $contentType)
    {
        $icon = $contentType->get('singleton') ? $contentType->get('icon_one') : $contentType->get('icon_many');

        // Named ContentType root
        $contentTypeEntry = $contentRoot->add(
            MenuEntry::create($contentTypeKey)
                ->setRoute('overview', ['contenttypeslug' => $contentTypeKey])
                ->setLabel($contentType->get('name'))
                ->setIcon($icon)
                ->setPermission('contenttype:' . $contentTypeKey)
        );
        // View
        $contentTypeEntry->add(
            MenuEntry::create('view')
                ->setRoute('overview', ['contenttypeslug' => $contentTypeKey])
                ->setLabel(Trans::__('contenttypes.generic.view', ['%contenttypes%' => $contentType->get('name')]))
                ->setIcon($contentType->get('icon_many', 'fa:files-o'))
                ->setPermission('contenttype:' . $contentTypeKey)
        );
        // New
        $contentTypeEntry->add(
            MenuEntry::create('new')
                ->setRoute('editcontent', ['contenttypeslug' => $contentTypeKey])
                ->setLabel(Trans::__('contenttypes.generic.new', ['%contenttype%' => $contentType->get('singular_name')]))
                ->setIcon('fa:plus')
                ->setPermission('contenttype:' . $contentTypeKey . ':create')
        );
    }

    /**
     * Add the "Other content" & similar menu/sub menus.
     *
     * @param MenuEntry $contentRoot
     * @param Bag       $contentType
     */
    private function addGroupedMenu(MenuEntry $contentRoot, Bag $contentType)
    {
        $key = $contentType->get('show_in_menu') ?: 'other';

        $label = $key === 'other' ? Trans::__('general.phrase.other-content') : ucwords($key);

        $contentRoot->add(
            MenuEntry::create(strtolower($key))
                ->setLabel($label)
                ->setIcon('fa:th-list')
                ->setGroup(true)
                ->setPermission('dashboard')
        );
    }

    /**
     * Fill the "Other content" & similar menu/sub menus with entries.
     *
     * @param MenuEntry $contentRoot
     */
    private function fillGroupedMenus(MenuEntry $contentRoot)
    {
        $groups = $this->contentTypes->call(function ($a) {
            $arr = [];
            foreach ($a as $k => $v) {
                if ($v['show_in_menu'] === true) {
                    continue;
                }
                $group = $v['show_in_menu'] ? strtolower($v['show_in_menu']) : 'other';
                $arr[$group][$k] = $v;
            }

            return $arr;
        });
        if ($groups->isEmpty()) {
            return;
        }

        foreach ($groups as $key => $groupContentTypes) {
            $this->addGroupedContentTypes($contentRoot->get($key), $groupContentTypes);
        }
    }

    /**
     * Add a group's children.
     *
     * @param MenuEntry $groupEntry
     * @param array     $contentTypes
     */
    private function addGroupedContentTypes(MenuEntry $groupEntry, array $contentTypes)
    {
        foreach ($contentTypes as $contentTypeKey => $contentType) {
            /** @var Bag $contentType */
            $groupEntry->add(
                MenuEntry::create($contentTypeKey)
                    ->setRoute('overview', ['contenttypeslug' => $contentTypeKey])
                    ->setLabel($contentType->get('name'))
                    ->setIcon($contentType->get('icon_many', 'fa:th-list'))
                    ->setPermission('contenttype:' . $contentTypeKey)
            );
        }
    }
}
