<?php

namespace Bolt\Menu;

use Bolt\Translation\Translator as Trans;
use Silex\Application;

/**
 * Bolt admin (back-end) area menu builder.
 *
 * @internal Backwards compatibility not guaranteed on this class presently.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class AdminMenuBuilder
{
    /** @var MenuEntry */
    protected $rootEntry;
    /** @var MenuEntry[] */
    protected $children;

    /**
     * Constructor.
     *
     * @param MenuEntry $rootEntry
     */
    public function __construct(MenuEntry $rootEntry)
    {
        $this->rootEntry = $rootEntry;
    }

    /**
     * Build the menus.
     *
     * @param Application $app
     *
     * @return \Bolt\Menu\MenuEntry
     */
    public function build(Application $app)
    {
        $this->addConfiguration($app);
        $this->addFileManagement($app);
        $this->addTranslations($app);

        return $this->rootEntry;
    }

    /**
     * Configuration menus.
     *
     * @param Application $app
     */
    protected function addConfiguration(Application $app)
    {
        // Main configuration
        $configEntry = (new MenuEntry('config', 'config'))
            ->setLabel(Trans::__('Configuration'))
            ->setIcon('fa:cogs')
            ->setPermission('settings')
        ;

        // Users & Permssions
        $path = $this->rootEntry->getUri() . '/users';
        $accessControlEntry = (new MenuEntry('users', $path))
            ->setLabel(Trans::__('Users & Permissions'))
            ->setIcon('fa:group')
            ->setPermission('users')
        ;
        $configEntry->addChild($accessControlEntry);

        // Main configuration
        $path = $app['url_generator']->generate('fileedit', ['namespace' => 'config', 'file' => 'config.yml']);
        $mainConfigEntry = (new MenuEntry('config_main', $path))
            ->setLabel(Trans::__('Main configuration'))
            ->setIcon('fa:cog')
            ->setPermission('files:config')
        ;
        $configEntry->addChild($mainConfigEntry);

        // ContentTypes
        $path = $app['url_generator']->generate('fileedit', ['namespace' => 'config', 'file' => 'contenttypes.yml']);
        $contentTypesEntry = (new MenuEntry('config_contenttypes', $path))
            ->setLabel(Trans::__('Contenttypes'))
            ->setIcon('fa:paint-brush')
            ->setPermission('files:config')
        ;
        $configEntry->addChild($contentTypesEntry);

        // Taxonomy
        $path = $app['url_generator']->generate('fileedit', ['namespace' => 'config', 'file' => 'taxonomy.yml']);
        $taxonomyEntry = (new MenuEntry('config_taxonomy', $path))
            ->setLabel(Trans::__('Taxonomy'))
            ->setIcon('fa:tags')
            ->setPermission('files:config')
        ;
        $configEntry->addChild($taxonomyEntry);

        // Menus
        $path = $app['url_generator']->generate('fileedit', ['namespace' => 'config', 'file' => 'menu.yml']);
        $menuSetupEntry = (new MenuEntry('config_menu', $path))
            ->setLabel(Trans::__('Menu setup'))
            ->setIcon('fa:list')
            ->setPermission('files:config')
        ;
        $configEntry->addChild($menuSetupEntry);

        // Routing
        $path = $app['url_generator']->generate('fileedit', ['namespace' => 'config', 'file' => 'routing.yml']);
        $routingSetupEntry = (new MenuEntry('config_routing', $path))
            ->setLabel(Trans::__('menu.configuration.routing'))
            ->setIcon('fa:random')
            ->setPermission('files:config')
        ;
        $configEntry->addChild($routingSetupEntry);

        // Database checks
        $path = $this->rootEntry->getUri() . '/dbcheck';
        $databaseEntry = (new MenuEntry('dbcheck', $path))
            ->setLabel(Trans::__('Check database'))
            ->setIcon('fa:database')
            ->setPermission('dbupdate')
        ;
        $configEntry->addChild($databaseEntry);

        // Cache flush
        $path = $this->rootEntry->getUri() . '/clearcache';
        $cacheEntry = (new MenuEntry('clearcache', $path))
            ->setLabel(Trans::__('Clear the cache'))
            ->setIcon('fa:eraser')
            ->setPermission('clearcache')
        ;
        $configEntry->addChild($cacheEntry);

        // Change log
        $path = $this->rootEntry->getUri() . '/changelog';
        $changeLogEntry = (new MenuEntry('log_change', $path))
            ->setLabel(Trans::__('logs.change-log'))
            ->setIcon('fa:archive')
            ->setPermission('changelog')
        ;
        $configEntry->addChild($changeLogEntry);

        // System log
        $path = $this->rootEntry->getUri() . '/systemlog';
        $systemLogEntry = (new MenuEntry('log_system', $path))
            ->setLabel(Trans::__('logs.system-log'))
            ->setIcon('fa:archive')
            ->setPermission('systemlog')
        ;
        $configEntry->addChild($systemLogEntry);

        // Add to root
        $this->rootEntry->addChild($configEntry);
    }

    /**
     * File management menus.
     *
     * @param Application $app
     */
    protected function addFileManagement(Application $app)
    {
        $fileEntry = (new MenuEntry('files', 'files'))
            ->setLabel(Trans::__('Extensions'))
            ->setIcon('fa:cubes')
            ->setPermission('extensions')
        ;

        // Uploaded files
        $path = $app['url_generator']->generate('files', ['namespace' => 'files', 'path' => '']);
        $uploadsEntry = (new MenuEntry('files_uploads', $path))
            ->setLabel(Trans::__('Uploaded files'))
            ->setIcon('fa:folder-open-o')
            ->setPermission('files:uploads')
        ;
        $fileEntry->addChild($uploadsEntry);

        // Themes
        $path = $app['url_generator']->generate('files', ['namespace' => 'theme', 'path' => '']);
        $templatesEntry = (new MenuEntry('files_themes', $path))
            ->setLabel(Trans::__('View/edit Templates'))
            ->setIcon('fa:desktop')
            ->setPermission('files:theme')
        ;
        $fileEntry->addChild($templatesEntry);

        // Add to root
        $this->rootEntry->addChild($fileEntry);
    }

    /**
     * Translations menus.
     *
     * @param Application $app
     */
    protected function addTranslations(Application $app)
    {
        $translationEntry = (new MenuEntry('translations', 'tr'))
            ->setLabel(Trans::__('Translations'))
            ->setPermission('translation')
        ;

        // Messages
        $path = $app['url_generator']->generate('translation', ['domain' => 'messages']);
        $messagesEntry = (new MenuEntry('tr_messages', $path))
            ->setLabel(Trans::__('Messages'))
            ->setIcon('fa:flag')
            ->setPermission('translation')
        ;
        $translationEntry->addChild($messagesEntry);

        // Long messages
        $path = $app['url_generator']->generate('translation', ['domain' => 'infos']);
        $longMessagesEntry = (new MenuEntry('tr_long_messages', $path))
            ->setLabel(Trans::__('Long messages'))
            ->setIcon('fa:flag')
            ->setPermission('translation')
        ;
        $translationEntry->addChild($longMessagesEntry);

        // Contenttypes
        $path = $app['url_generator']->generate('translation', ['domain' => 'contenttypes']);
        $contenttypesEntry = (new MenuEntry('tr_contenttypes', $path))
            ->setLabel(Trans::__('Contenttypes'))
            ->setIcon('fa:flag')
            ->setPermission('translation')
        ;
        $translationEntry->addChild($contenttypesEntry);

        // Add to root
        $this->rootEntry->addChild($translationEntry);
    }
}
