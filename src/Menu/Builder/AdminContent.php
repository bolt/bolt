<?php

namespace Bolt\Menu\Builder;

use Bolt\Collection\Bag;
use Bolt\Menu\MenuEntry;
use Bolt\Translation\Translator as Trans;

/**
 * Bolt admin (back-end) content menu builder.
 *
 * @internal Backwards compatibility not guaranteed on this class presently.
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

        // ContentTypes, where show_in_menu is set true
        $contentTypes = $this->contentTypes->filter(function ($k, $v) {
            return $v['show_in_menu'] === true;
        });
        foreach ($contentTypes as $name => $contentType) {
            $this->addContentType($mainContentRoot, $name, $contentType);
        }

        // ContentTypes, where show_in_menu is set to a custom value or false
        $this->addOtherContentTypes($contentRoot);

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
        // Named ContentType root
        $contentTypeEntry = $contentRoot->add(
            MenuEntry::create($contentTypeKey)
                ->setRoute('overview', ['contenttypeslug' => $contentTypeKey])
                ->setLabel($contentType->get('name'))
                ->setIcon($contentType->get('icon_many'))
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
     * Add the "Other content" menu.
     *
     * @param MenuEntry $contentRoot
     */
    private function addOtherContentTypes(MenuEntry $contentRoot)
    {
        $contentTypes = $this->contentTypes->filter(function ($k, $v) {
            return $v['show_in_menu'] !== true;
        });
        if ($contentTypes->isEmpty()) {
            return;
        }

        // Other content root
        $otherEntry = $contentRoot->add(
            MenuEntry::create('other')
                ->setLabel(Trans::__('general.phrase.other-content'))
                ->setIcon('fa:th-list')
                ->setPermission('dashboard')
        );

        // Other content children
        foreach ($contentTypes as $contentTypeKey => $contentType) {
            /** @var Bag $contentType */
            $otherEntry->add(
                MenuEntry::create($contentTypeKey)
                    ->setRoute('overview', ['contenttypeslug' => $contentTypeKey])
                    ->setLabel($contentType->get('name'))
                    ->setIcon($contentType->get('icon_many', 'fa:th-list'))
                    ->setPermission('contenttype:' . $contentTypeKey)
            );
        }
    }
}
