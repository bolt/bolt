<?php

namespace Bolt\Menu;

use Bolt\Translation\Translator as Trans;

/**
 * Bolt admin (back-end) area menu builder.
 *
 * @internal Backwards compatibility not guaranteed on this class presently.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
final class AdminMenuBuilder
{
    /**
     * Build the menus.
     *
     * @param MenuEntry $root
     *
     * @return MenuEntry
     */
    public function build(MenuEntry $root)
    {
        $this->addConfiguration($root);
        $this->addFileManagement($root);
        $this->addTranslations($root);
        $this->addExtend($root);

        return $root;
    }

    /**
     * Configuration menus.
     *
     * @param MenuEntry $root
     */
    private function addConfiguration(MenuEntry $root)
    {
        // Main configuration
        $configEntry = $root->add(
            (new MenuEntry('config', 'config'))
                ->setLabel(Trans::__('general.phrase.configuration'))
                ->setIcon('fa:cogs')
                ->setPermission('settings')
        );

        // Users & Permissions
        $configEntry->add(
            (new MenuEntry('users'))
                ->setRoute('users')
                ->setLabel(Trans::__('general.phrase.users-permissions'))
                ->setIcon('fa:group')
                ->setPermission('users')
        );

        // Main configuration
        $configEntry->add(
            (new MenuEntry('config_main'))
                ->setRoute('fileedit', ['namespace' => 'config', 'file' => 'config.yml'])
                ->setLabel(Trans::__('general.phrase.configuration-main'))
                ->setIcon('fa:cog')
                ->setPermission('files:config')
        );

        // ContentTypes
        $configEntry->add(
            (new MenuEntry('config_contenttypes'))
                ->setRoute('fileedit', ['namespace' => 'config', 'file' => 'contenttypes.yml'])
                ->setLabel(Trans::__('general.phrase.content-types'))
                ->setIcon('fa:paint-brush')
                ->setPermission('files:config')
        );

        // Taxonomy
        $configEntry->add(
            (new MenuEntry('config_taxonomy'))
                ->setRoute('fileedit', ['namespace' => 'config', 'file' => 'taxonomy.yml'])
                ->setLabel(Trans::__('general.phrase.taxonomy'))
                ->setIcon('fa:tags')
                ->setPermission('files:config')
        );

        // Menus
        $configEntry->add(
            (new MenuEntry('config_menu'))
                ->setRoute('fileedit', ['namespace' => 'config', 'file' => 'menu.yml'])
                ->setLabel(Trans::__('general.phrase.menu-setup'))
                ->setIcon('fa:list')
                ->setPermission('files:config')
        );

        // Routing
        $configEntry->add(
            (new MenuEntry('config_routing'))
                ->setRoute('fileedit', ['namespace' => 'config', 'file' => 'routing.yml'])
                ->setLabel(Trans::__('menu.configuration.routing'))
                ->setIcon('fa:random')
                ->setPermission('files:config')
        );

        // Database checks
        $configEntry->add(
            (new MenuEntry('dbcheck'))
                ->setRoute('dbcheck')
                ->setLabel(Trans::__('general.phrase.check-database'))
                ->setIcon('fa:database')
                ->setPermission('dbupdate')
        );

        // Cache flush
        $configEntry->add(
            (new MenuEntry('clearcache'))
                ->setRoute('clearcache')
                ->setLabel(Trans::__('general.phrase.clear-cache'))
                ->setIcon('fa:eraser')
                ->setPermission('clearcache')
        );

        // Change log
        $configEntry->add(
            (new MenuEntry('log_change'))
                ->setRoute('changelog')
                ->setLabel(Trans::__('logs.change-log'))
                ->setIcon('fa:archive')
                ->setPermission('changelog')
        );

        // System log
        $configEntry->add(
            (new MenuEntry('log_system'))
                ->setRoute('systemlog')
                ->setLabel(Trans::__('logs.system-log'))
                ->setIcon('fa:archive')
                ->setPermission('systemlog')
        );
    }

    /**
     * File management menus.
     *
     * @param MenuEntry $root
     */
    private function addFileManagement(MenuEntry $root)
    {
        $fileEntry = $root->add(
            (new MenuEntry('files', 'files'))
                ->setLabel(Trans::__('general.phrase.extensions'))
                ->setIcon('fa:cubes')
                ->setPermission('extensions')
        );

        // Uploaded files
        $fileEntry->add(
            (new MenuEntry('files_uploads'))
                ->setRoute('files')
                ->setLabel(Trans::__('general.phrase.general.phrase.uploaded-files'))
                ->setIcon('fa:folder-open-o')
                ->setPermission('files:uploads')
        );

        // Themes
        $fileEntry->add(
            (new MenuEntry('files_themes'))
                ->setRoute('files', ['namespace' => 'themes'])
                ->setLabel(Trans::__('general.phrase.view-edit-templates'))
                ->setIcon('fa:desktop')
                ->setPermission('files:theme')
        );
    }

    /**
     * Translations menus.
     *
     * @param MenuEntry $root
     */
    private function addTranslations(MenuEntry $root)
    {
        $translationEntry = $root->add(
            (new MenuEntry('translations', 'tr'))
                ->setLabel(Trans::__('general.phrase.translations'))
                ->setPermission('translation')
        );

        // Messages
        $translationEntry->add(
            (new MenuEntry('tr_messages'))
                ->setRoute('translation', ['domain' => 'messages'])
                ->setLabel(Trans::__('general.phrase.messages'))
                ->setIcon('fa:flag')
                ->setPermission('translation')
        );

        // Long messages
        $translationEntry->add(
            (new MenuEntry('tr_long_messages'))
                ->setRoute('translation', ['domain' => 'infos'])
                ->setLabel(Trans::__('general.phrase.long-messages'))
                ->setIcon('fa:flag')
                ->setPermission('translation')
        );
    }

    /**
     * Extensions menus.
     *
     * @param MenuEntry $root
     */
    private function addExtend(MenuEntry $root)
    {
        $root->add(
            (new MenuEntry('extensions', 'extensions'))
                ->setLabel(Trans::__('general.phrase.extensions-overview'))
                ->setIcon('fa:cubes')
                ->setPermission('extensions')
        );
    }
}
