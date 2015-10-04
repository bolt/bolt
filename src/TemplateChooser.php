<?php

namespace Bolt;

use Silex;

/**
 * A class for choosing whichever template should be used.
 */
class TemplateChooser
{
    /** @var Silex\Application */
    private $app;

    /**
     * Constructor.
     *
     * @param Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Choose a template for the homepage.
     *
     * @param \Bolt\Legacy\Content|\Bolt\Legacy\Content[] $content
     *
     * @return string
     */
    public function homepage($content)
    {
        // First candidate: Global config.yml file.
        $template = $this->app['config']->get('general/homepage_template');

        // Second candidate: Theme-specific config.yml file.
        if ($this->app['config']->get('theme/homepage_template')) {
            $template = $this->app['config']->get('theme/homepage_template');
        }

        // Fallback if no content: index.twig
        if (empty($content) && empty($template)) {
            $template = 'index.twig';
        }

        // Fallback with content: use record() or listing() to choose template
        if (empty($template)) {
            if (is_array($content)) {
                $first = current($content);
                return $this->listing($first->contenttype);
            } else {
                return $this->record($content);
            }
        } else {
            return $template;
        }
    }

    /**
     * Choose a template for a single record page, e.g.:
     * - '/page/about'
     * - '/entry/lorum-ipsum'
     *
     * Refactor note: Using a FQCN for the hint here as a `use` statement causes
     * a fatal in the unit tests… 'cause PHP and class_alias() versus namespaces.
     *
     * @param \Bolt\Legacy\Content $record
     * @param array                $data
     *
     * @return string
     */
    public function record($record, $data = null)
    {
        // First candidate: global config.yml
        $template = $this->app['config']->get('general/record_template');

        // Second candidate: Theme-specific config.yml file.
        if ($this->app['config']->get('theme/record_template')) {
            $template = $this->app['config']->get('theme/record_template');
        }

        // Third candidate: a template with the same filename as the name of
        // the contenttype.
        if (isset($record->contenttype['singular_slug'])) {
            $templatefile = $this->app['resources']->getPath('templatespath/' . $record->contenttype['singular_slug'] . '.twig');
            if (is_readable($templatefile)) {
                $template = $record->contenttype['singular_slug'] . '.twig';
            }
        }

        // Fourth candidate: defined specificaly in the contenttype.
        if (isset($record->contenttype['record_template'])) {
            $templatefile = $this->app['resources']->getPath('templatespath/' . $record->contenttype['record_template']);
            if (file_exists($templatefile)) {
                $template = $record->contenttype['record_template'];
            }
        }

        // Fifth candidate: An entity has a templateselect field, and it's set.
        if (isset($record->contenttype['fields'])) {
            foreach ($record->contenttype['fields'] as $name => $field) {
                if ($field['type'] == 'templateselect' && $data !== null && !empty($data[$name])) {
                    $template = $data[$name];
                }

                if ($field['type'] == 'templateselect' && !empty($record[$name])) {
                    $template = $record[$name];
                }
            }
        }

        // Sixth candidate: A legacy Content record has a templateselect field, and it's set.
        if (isset($record->contenttype['fields'])) {
            foreach ($record->contenttype['fields'] as $name => $field) {
                if ($field['type'] == 'templateselect' && !empty($record->values[$name])) {
                    $template = $record->values[$name];
                }
            }
        }

        return $template;
    }

    /**
     * Select a template for listing pages.
     *
     * @param array $contenttype
     *
     * @return string
     */
    public function listing($contenttype)
    {
        // First candidate: Global config.yml
        $template = $this->app['config']->get('general/listing_template');

        // Second candidate: Theme-specific config.yml file.
        if ($this->app['config']->get('theme/listing_template')) {
            $template = $this->app['config']->get('theme/listing_template');
        }

        // Third candidate: a template with the same filename as the name of
        // the contenttype.
        $filename = $this->app['resources']->getPath('templatespath/' . $contenttype['slug'] . '.twig');
        if (file_exists($filename) && is_readable($filename)) {
            $template = $contenttype['slug'] . '.twig';
        }

        // Fourth candidate: defined specificaly in the contenttype.
        if (!empty($contenttype['listing_template'])) {
            $template = $contenttype['listing_template'];
        }

        return $template;
    }

    /**
     * Select a template for taxonomy.
     *
     * @param string $taxonomyslug
     *
     * @return string
     */
    public function taxonomy($taxonomyslug)
    {
        // First candidate: Global config.yml
        $template = $this->app['config']->get('general/listing_template');

        // Second candidate: Theme-specific config.yml file.
        if ($this->app['config']->get('theme/listing_template')) {
            $template = $this->app['config']->get('theme/listing_template');
        }

        // Third candidate: defined specifically in the taxonomy
        if ($this->app['config']->get('taxonomy/' . $taxonomyslug . '/listing_template')) {
            $template = $this->app['config']->get('taxonomy/' . $taxonomyslug . '/listing_template');
        }

        return $template;
    }

    /**
     * Select a search template.
     *
     * @return string
     */
    public function search()
    {
        // First candidate: listing config setting.
        $template = $this->app['config']->get('general/listing_template');

        // Second candidate: specific search setting in global config.
        if ($this->app['config']->get('general/search_results_template')) {
            $template = $this->app['config']->get('general/search_results_template');
        }

        // Third candidate: specific search setting in global config.
        if ($this->app['config']->get('theme/search_results_template')) {
            $template = $this->app['config']->get('theme/search_results_template');
        }

        return $template;
    }

    /**
     * Select a template to use for the "maintenance" page.
     *
     * @return string
     */
    public function maintenance()
    {
        // First candidate: global config.
        $template = $this->app['config']->get('general/maintenance_template');

        // Second candidate: specific search setting in global config.
        if ($this->app['config']->get('theme/maintenance_template')) {
            $template = $this->app['config']->get('theme/maintenance_template');
        }

        return $template;
    }
}
