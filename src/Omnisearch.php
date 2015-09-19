<?php

namespace Bolt;

use Bolt\Translation\Translator as Trans;
use Silex;
use Symfony\Component\Finder\Finder;

/**
 * Simple search implementation for the Bolt backend.
 *
 * TODO:
 * - permissions
 * - a config.yml for search options
 *
 * @author Xiao-HuTai, xiao@twokings.nl
 */
class Omnisearch
{
    const OMNISEARCH_LANDINGPAGE = 99999;
    const OMNISEARCH_CONTENTTYPE = 9999;
    const OMNISEARCH_MENUITEM    = 5000;
    const OMNISEARCH_EXTENSION   = 3000;
    const OMNISEARCH_CONTENT     = 2000;
    const OMNISEARCH_FILE        = 1000;

    private $showNewContenttype  = true;
    private $showViewContenttype = true;
    private $showConfiguration   = true;
    private $showMaintenance     = true;
    private $showExtensions      = true;
    private $showFiles           = true;
    private $showRecords         = true;

    // Show the option to the landing page for search results.
    private $showLandingpage     = true;

    private $app;
    private $data;

    public function __construct(Silex\Application $app)
    {
        $this->app = $app;

        $this->initialize();
    }

    public function initialize()
    {
        $this->initContenttypes();
        $this->initMenuitems();
        $this->initExtensions();
    }

    private function initContenttypes()
    {
        $contenttypes = $this->app['config']->get('contenttypes');

        foreach ($contenttypes as $key => $value) {
            $pluralname   = $value['name'];
            $singularname = $value['singular_name'];
            $slug         = $value['slug'];
            $keywords     = [
                $pluralname,
                $singularname,
                $slug,
                $key,
            ];

            $viewContenttype = Trans::__('contenttypes.generic.view', ['%contenttypes%' => $key]);
            $newContenttype  = Trans::__('contenttypes.generic.new', ['%contenttype%' => $key]);

            if ($this->showViewContenttype) {
                $viewKeywords   = $keywords;
                $viewKeywords[] = $viewContenttype;
                $viewKeywords[] = 'View ' . $pluralname;

                $this->register(
                    [
                        'keywords'    => $viewKeywords,
                        'label'       => $viewContenttype,
                        'description' => '',
                        'priority'    => self::OMNISEARCH_CONTENTTYPE,
                        'path'        => $this->generatePath('overview', ['contenttypeslug' => $slug]),
                    ]
                );
            }

            if ($this->showNewContenttype) {
                $newKeywords    = $keywords;
                $newKeywords[]  = $newContenttype;
                $newKeywords[]  = 'New ' . $singularname;

                $this->register(
                    [
                        'keywords'    => $newKeywords,
                        'label'       => $newContenttype,
                        'description' => '',
                        'priority'    => self::OMNISEARCH_CONTENTTYPE,
                        'path'        => $this->generatePath('editcontent', ['contenttypeslug' => $slug]),
                    ]
                );
            }
        }
    }

    private function initMenuitems()
    {
        // Configuration
        if ($this->showConfiguration) {
            $this->register(
                [
                    'keywords'    => ['Configuration'],
                    'label'       => Trans::__('Configuration'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM,
                    'path'        => $this->generatePath('fileedit', ['namespace' => 'config', 'file' => 'config.yml']),
                ]
            );
            $this->register(
                [
                    'keywords'    => ['Users', 'Configuration'],
                    'label'       => Trans::__('Configuration') . ' » ' . Trans::__('Users'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 1,
                    'path'        => $this->generatePath('users'),
                ]
            );
            $this->register(
                [
                    'keywords'    => ['Contenttypes', 'Configuration'],
                    'label'       => Trans::__('Configuration') . ' » ' . Trans::__('Contenttypes'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 2,
                    'path'        => $this->generatePath('fileedit', ['namespace' => 'config', 'file' => 'contenttypes.yml']),
                ]
            );
            $this->register(
                [
                    'keywords'    => ['Taxonomy', 'Configuration'],
                    'label'       => Trans::__('Configuration') . ' » ' . Trans::__('Taxonomy'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 3,
                    'path'        => $this->generatePath('fileedit', ['namespace' => 'config', 'file' => 'taxonomy.yml']),
                ]
            );
            $this->register(
                [
                    'keywords'    => ['Menu setup', 'Configuration'],
                    'label'       => Trans::__('Configuration') . ' » ' . Trans::__('Menu setup'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 4,
                    'path'        => $this->generatePath('fileedit', ['namespace' => 'config', 'file' => 'menu.yml']),
                ]
            );
            $this->register(
                [
                    'keywords'    => ['Routing setup', 'Configuration'],
                    'label'       => Trans::__('Configuration') . ' » ' . Trans::__('menu.configuration.routing'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 5,
                    'path'        => $this->generatePath('fileedit', ['namespace' => 'config', 'file' => 'routing.yml']),
                ]
            );
        }

        // Maintenance
        if ($this->showMaintenance) {
            $this->register(
                [
                    'keywords'    => ['Extensions', 'Maintenance'],
                    'label'       => Trans::__('Maintenance') . ' » ' . Trans::__('Extensions'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 6,
                    'path'        => $this->generatePath('extend'),
                ]
            );
            $this->register(
                [
                    'keywords'    => ['Check database', 'Maintenance'],
                    'label'       => Trans::__('Maintenance') . ' » ' . Trans::__('Check database'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 7,
                    'path'        => $this->generatePath('dbcheck'),
                ]
            );
            $this->register(
                [
                    'keywords'    => ['Clear the cache', 'Maintenance'],
                    'label'       => Trans::__('Maintenance') . ' » ' . Trans::__('Clear the cache'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 8,
                    'path'        => $this->generatePath('clearcache'),
                ]
            );
            $this->register(
                [
                    'keywords'    => ['Change log', 'Maintenance'],
                    'label'       => Trans::__('Maintenance') . ' » ' . Trans::__('logs.change-log'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 9,
                    'path'        => $this->generatePath('changelog'),
                ]
            );
            $this->register(
                [
                    'keywords'    => ['System log', 'Maintenance'],
                    'label'       => Trans::__('Maintenance') . ' » ' . Trans::__('logs.system-log'),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_MENUITEM - 10,
                    'path'        => $this->generatePath('systemlog'),
                ]
            );
        }
    }

    private function initExtensions()
    {
        if (!$this->showExtensions) {
            return;
        }

        $extensionsmenu = $this->app['extensions']->getMenuoptions();
        $index = 0;
        foreach ($extensionsmenu as $extension) {
            $this->register(
                [
                    'keywords'    => [$extension['label'], 'Extensions'],
                    'label'       => Trans::__('Extensions') . ' » ' . $extension['label'],
                    'description' => '',
                    'priority'    => self::OMNISEARCH_EXTENSION - $index,
                    'path'        => $extension['path'],
                ]
            );

            $index--;
        }
    }

    public function register($options)
    {
        // options
        // $options['label'];       // label shown in the search results
        // $options['description']; // currently unused
        // $options['keywords'];    // array with descriptions to match for
        // $options['priority'];    // higher number, higher priority
        // $options['path'];        // the URL to go to

        // automatically adds the translations
        $keywords = $options['keywords'];
        foreach ($keywords as $keyword) {
            $options['keywords'][] = Trans::__($keyword);
        }

        $this->data[$options['path']] = $options;
    }

    public function query($query, $withRecord = false)
    {
        $options = [];

        $this->find($query, 'theme', '*.twig', $query, -10); // find in file contents
        $this->find($query, 'theme', '*' . $query . '*.twig', false, 10); // find in filenames
        $this->search($query, $withRecord);

        foreach ($this->data as $item) {
            $matches = $this->matches($item['path'], $query);

            if (!$matches) {
                foreach ($item['keywords'] as $keyword) {
                    if ($this->matches($keyword, $query)) {
                        $matches = true;
                        break;
                    }
                }
            }

            if ($matches) {
                $options[] = $item;
            }
        }

        if ($this->showLandingpage) {
            $options[] = [
                'keywords'    => ['Omnisearch'],
                'label'       => sprintf('%s', Trans::__('Omnisearch')),
                'description' => '',
                'priority'    => self::OMNISEARCH_LANDINGPAGE,
                'path'        => $this->generatePath('omnisearch-results', ['q' => $query]),
            ];
        }

        usort($options, [$this, 'compareOptions']);

        return $options;
    }

    /**
     * Find in files.
     *
     * @param string      $query
     * @param string      $path
     * @param string      $name
     * @param bool|string $contains
     * @param int         $priority
     */
    private function find($query, $path = 'theme', $name = '*.twig', $contains = false, $priority = 0)
    {
        if (!$this->showFiles) {
            return;
        }

        $finder = new Finder();
        $finder->files()
                  ->ignoreVCS(true)
                  ->notName('*~')
                  ->in($this->app['resources']->getPath($path));

        if ($name) {
            $finder->name($name);
        }

        if ($contains) {
            $finder->contains($contains);
        }

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {
            $relativePathname = $file->getRelativePathname();
            $filename         = $file->getFilename();

            $this->register(
                [
                    'label'       => sprintf("%s » <span>%s</span>", Trans::__('Edit file'), $filename),
                    'path'        => $this->generatePath('fileedit', ['namespace' => 'theme', 'file' => $relativePathname]),
                    'description' => '',
                    'priority'    => self::OMNISEARCH_FILE + $priority,
                    'keywords'    => ['Edit file', $filename, $query]
                ]
            );
        }
    }

    /**
     * Search in database.
     *
     * @param string $query
     * @param bool   $withRecord
     */
    private function search($query, $withRecord = false)
    {
        if (!$this->showRecords) {
            return;
        }
        $user = $this->app['users']->getCurrentUser();

        $searchresults = $this->app['storage']->searchContent($query);
        /** @var Content[] $searchresults */
        $searchresults = $searchresults['results'];

        $index = 0;
        foreach ($searchresults as $result) {
            $item = [
                'label' => sprintf(
                    '%s %s № %s » <span>%s</span>',
                    Trans::__('Edit'),
                    $result->contenttype['singular_name'],
                    $result->id,
                    $result->getTitle()
                ),
                'path'        => $result->editlink(),
                'description' => '',
                'keywords'    => [$query],
                'priority'    => self::OMNISEARCH_CONTENT - $index++,
            ];

            if ($withRecord) {
                $item['record'] = $result;
                $item['permissions'] = $this->app['permissions']->getContentTypeUserPermissions($result->contenttype['slug'], $user);
            }

            $this->register($item);
        }
    }

    private function matches($sentence, $word)
    {
        return stripos($sentence, $word) !== false;
    }

    /**
     * OmnisearchOption implements Comparable.
     *
     * @param array $a
     * @param array $b
     *
     * @return int
     */
    private function compareOptions($a, $b)
    {
        $comparison = $b['priority'] - $a['priority'];

        if ($comparison == 0) {
            return strcasecmp($a['path'], $b['path']);
        }

        return $comparison;
    }

    private function generatePath($route, $parameters = [])
    {
        return $this->app['url_generator']->generate($route, $parameters);
    }
}
