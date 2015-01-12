<?php

namespace Bolt;

use Silex;
use Symfony\Component\Finder\Finder;
use Bolt\Translation\Translator as Trans;

/**
 * Simple search implementation for the Bolt backend.
 *
 * TODO:
 * - permissions
 * - a config.yml for search options
 *
 * @author Xiao-HuTai, xiao@twokings.nl
 *
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
    private $backend;
    private $data;

    public function __construct(Silex\Application $app)
    {
        $this->app     = $app;
        $this->backend = $app['paths']['bolt'];

        $this->initialize();

        // todo: config.yml
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
            $keywords     = array(
                $pluralname,
                $singularname,
                $slug,
                $key,
            );

            $viewContenttype = Trans::__('contenttypes.generic.view', array('%contenttypes%' => $key));
            $newContenttype  = Trans::__('contenttypes.generic.new', array('%contenttype%' => $key));

            if ($this->showViewContenttype) {
                $viewKeywords   = $keywords;
                $viewKeywords[] = $viewContenttype;
                $viewKeywords[] = 'View ' . $pluralname;

                $this->register(
                    array(
                        'keywords' => $viewKeywords,
                        'label' => $viewContenttype,
                        'description' => '',
                        'priority' => self::OMNISEARCH_CONTENTTYPE,
                        'path' => $this->backend . 'overview/' . $slug
                    )
                );
            }

            if ($this->showNewContenttype) {
                $newKeywords    = $keywords;
                $newKeywords[]  = $newContenttype;
                $newKeywords[]  = 'New ' . $singularname;

                $this->register(
                    array(
                        'keywords' => $newKeywords,
                        'label' => $newContenttype,
                        'description' => '',
                        'priority' => self::OMNISEARCH_CONTENTTYPE,
                        'path' => $this->backend . 'editcontent/' . $slug
                    )
                );
            }
        }
    }

    private function initMenuitems()
    {
        // Configuration
        if ($this->showConfiguration) {
            $this->register(
                array(
                    'keywords' => array('Configuration'),
                    'label' => Trans::__('Configuration'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM,
                    'path' => $this->backend . 'file/edit/app/config/config.yml',
                )
            );
            $this->register(
                array(
                    'keywords' => array('Users', 'Configuration'),
                    'label' => Trans::__('Configuration') . ' » ' . Trans::__('Users'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM - 1,
                    'path' => $this->backend . 'users',
                )
            );
            $this->register(
                array(
                    'keywords' => array('Contenttypes', 'Configuration'),
                    'label' => Trans::__('Configuration') . ' » ' . Trans::__('Contenttypes'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM - 2,
                    'path' => $this->backend . 'file/edit/app/config/contenttypes.yml',
                )
            );
            $this->register(
                array(
                    'keywords' => array('Taxonomy', 'Configuration'),
                    'label' => Trans::__('Configuration') . ' » ' . Trans::__('Taxonomy'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM - 3,
                    'path' => $this->backend . 'file/edit/app/config/taxonomy.yml',
                )
            );
            $this->register(
                array(
                    'keywords' => array('Menu setup', 'Configuration'),
                    'label' => Trans::__('Configuration') . ' » ' . Trans::__('Menu setup'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM - 4,
                    'path' => $this->backend . 'file/edit/app/config/menu.yml',
                )
            );
            $this->register(
                array(
                    'keywords' => array('Routing setup', 'Configuration'),
                    'label' => Trans::__('Configuration') . ' » ' . Trans::__('menu.configuration.routing'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM - 5,
                    'path' => $this->backend . 'file/edit/app/config/routing.yml',
                )
            );
        }

        // Maintenance
        if ($this->showMaintenance) {
            $this->register(
                array(
                    'keywords' => array('Extensions', 'Maintenance'),
                    'label' => Trans::__('Maintenance') . ' » ' . Trans::__('Extensions'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM - 6,
                    'path' => $this->backend . 'extensions',
                )
            );
            $this->register(
                array(
                    'keywords' => array('Check database', 'Maintenance'),
                    'label' => Trans::__('Maintenance') . ' » ' . Trans::__('Check database'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM - 7,
                    'path' => $this->backend . 'dbcheck',
                )
            );
            $this->register(
                array(
                    'keywords' => array('Clear the cache', 'Maintenance'),
                    'label' => Trans::__('Maintenance') . ' » ' . Trans::__('Clear the cache'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM - 8,
                    'path' => $this->backend . 'clearcache',
                )
            );
            $this->register(
                array(
                    'keywords' => array('Activity log', 'Maintenance'),
                    'label' => Trans::__('Maintenance') . ' » ' . Trans::__('Activity log'),
                    'description' => '',
                    'priority' => self::OMNISEARCH_MENUITEM - 9,
                    'path' => $this->backend . 'activitylog',
                )
            );
        }
    }

    private function initExtensions()
    {
        if (!$this->showExtensions) {
            return;
        }

        $extensionsmenu = $this->app['extensions']->getMenuoptions();
        if ($extensionsmenu) {
            $index = 0;
            foreach ($extensionsmenu as $extension) {
                $this->register(
                    array(
                        'keywords' => array($extension['label'], 'Extensions'),
                        'label' => Trans::__('Extensions') . ' » ' . $extension['label'],
                        'description' => '',
                        'priority' => self::OMNISEARCH_EXTENSION - $index,
                        'path' => $this->backend . $extension['path'],
                    )
                );

                $index--;
            }
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
        $options = array();

        $this->find($query, '/theme', '*.twig', $query, -10); // find in file contents
        $this->find($query, '/theme', '*' . $query . '*.twig', false, 10); // find in filenames, '/'.preg_quote($query).'.*\.twig$/i';
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
            // todo: Do we want to add this at the beginning, at the end,
            // or maybe after x results.

            $options[] = array(
                'keywords' => array('Omnisearch'),
                'label' => sprintf("%s", Trans::__('Omnisearch')),
                'description' => '',
                'priority' => self::OMNISEARCH_LANDINGPAGE,
                'path' => $this->backend . 'omnisearch?q=' . $query,
            );
        }

        usort($options, array($this, 'compareOptions'));

        return $options;
    }

    /**
     * Find in files
     *
     * @param type        $query
     * @param string      $folder
     * @param string      $name
     * @param bool|string $contains
     * @param int         $priority
     */
    private function find($query, $folder = '/theme', $name = '*.twig', $contains = false, $priority = 0)
    {
        if (!$this->showFiles) {
            return;
        }

        $finder = new Finder();
        $finder->files()
                  ->ignoreVCS(true)
                  ->notName('*~')
                  ->in($this->app['resources']->getPath('root') . $folder);

        if ($name) {
            $finder->name($name);
        }

        if ($contains) {
            $finder->contains($contains);
        }

        foreach ($finder as $file) {
            $relativePathname = $file->getRelativePathname();
            $filename         = $file->getFilename();

            $this->register(
                array(
                    'label' => sprintf("%s » <span>%s</span>", Trans::__('Edit file'), $filename),
                    'path' => $this->backend . 'file/edit/theme/' . $relativePathname,
                    'description' => '',
                    'priority' => self::OMNISEARCH_FILE + $priority,
                    'keywords' => array('Edit file', $filename, $query)
                )
            );
        }
    }

    /**
     * Search in database
     *
     * @param string $query
     * @param bool   $withRecord
     */
    private function search($query, $withRecord = false)
    {
        if (!$this->showRecords) {
            return;
        }

        $searchresults = $this->app['storage']->searchContent($query);
        $searchresults = $searchresults['results'];

        $index = 0;
        foreach ($searchresults as $result) {
            $contentid           = $result->id;
            $contenttypeslug     = $result->contenttype['slug'];
            $contenttitle        = $result->getTitle();
            $contenttypesingular = $result->contenttype['singular_name'];

            $item = array(
                'label' => sprintf('%s %s № %s » <span>%s</span>', Trans::__('Edit'), $contenttypesingular, $contentid, $contenttitle),
                'path' => $this->backend . 'editcontent/' . $contenttypeslug . '/' . $contentid,
                'description' => '',
                'keywords' => array($query),
                'priority' => self::OMNISEARCH_CONTENT - $index,
            );

            if ($withRecord) {
                $item['record'] = $result;
            }

            $this->register($item);

            $index++;
        }
    }

    private function matches($sentence, $word)
    {
        return stripos($sentence, $word) !== false;
    }

    /**
     * OmnisearchOption implements Comparable
     *
     * @param  array $a
     * @param  array $b
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
}
