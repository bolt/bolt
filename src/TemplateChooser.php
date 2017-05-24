<?php

namespace Bolt;

/**
 * A class for choosing whichever template should be used.
 */
class TemplateChooser
{
    /** @var Config */
    private $config;

    /**
     * Constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Choose a template for the homepage.
     *
     * @param \Bolt\Legacy\Content|\Bolt\Legacy\Content[] $content
     *
     * @return string[]
     */
    public function homepage($content)
    {
        $templates = [];

        // First candidate: Theme-specific config.yml file.
        if ($template = $this->config->get('theme/homepage_template')) {
            $templates[] = $template;
        }

        // Second candidate: Global config.yml file.
        if ($template = $this->config->get('general/homepage_template')) {
            $templates[] = $template;
        }

        if (empty($content)) {
            // Fallback if no content: index.twig
            $templates[] = 'index.twig';
        } elseif (is_array($content)) {
            // Fallback with multiple content: use listing() to choose template
            $first = reset($content);
            $templates = array_merge($templates, $this->listing($first->contenttype));
        } else {
            // Fallback with single content: use record() to choose template
            $templates = array_merge($templates, $this->record($content));
        }

        return $templates;
    }

    /**
     * Choose a template for a single record page, e.g.:
     * - '/page/about'
     * - '/entry/lorum-ipsum'.
     *
     * Refactor note: Using a FQCN for the hint here as a `use` statement causes
     * a fatal in the unit tests… 'cause PHP and class_alias() versus namespaces.
     *
     * @param object $record
     * @param array  $data
     *
     * @return string[]
     */
    public function record($record, $data = null)
    {
        $templates = [];

        // First candidate: A legacy Content record has a templateselect field, and it's set.
        if (isset($record->contenttype['fields'])) {
            foreach ($record->contenttype['fields'] as $name => $field) {
                if ($field['type'] == 'templateselect' && !empty($record->values[$name])) {
                    $templates[] = $record->values[$name];
                }
            }
        }

        // Second candidate: An entity has a templateselect field, and it's set.
        if (isset($record->contenttype['fields'])) {
            foreach ($record->contenttype['fields'] as $name => $field) {
                if ($field['type'] == 'templateselect' && !empty($record[$name])) {
                    $templates[] = $record[$name];
                }

                if ($field['type'] == 'templateselect' && $data !== null && !empty($data[$name])) {
                    $templates[] = $data[$name];
                }
            }
        }

        // Third candidate: defined specifically in the contenttype.
        if (isset($record->contenttype['record_template'])) {
            $templates[] = $record->contenttype['record_template'];
        }

        // Fourth candidate: a template with the same filename as the name of
        // the contenttype.
        if (isset($record->contenttype['singular_slug'])) {
            $templates[] = $record->contenttype['singular_slug'] . '.twig';
        }

        // Fifth candidate: Theme-specific config.yml file.
        if ($template = $this->config->get('theme/record_template')) {
            $templates[] = $template;
        }

        // Sixth candidate: global config.yml
        $templates[] = $this->config->get('general/record_template');

        // Seventh candidate: fallback to 'record.twig'
        $templates[] = 'record.twig';

        return $templates;
    }

    /**
     * Select a template for listing pages.
     *
     * @param array $contenttype
     *
     * @return string[]
     */
    public function listing($contenttype)
    {
        $templates = [];

        // First candidate: defined specifically in the contenttype.
        if (!empty($contenttype['listing_template'])) {
            $templates[] = $contenttype['listing_template'];
        }

        // Second candidate: a template with the same filename as the name of
        // the contenttype.
        $templates[] = $contenttype['slug'] . '.twig';

        // Third candidate: Theme-specific config.yml file.
        if ($template = $this->config->get('theme/listing_template')) {
            $templates[] = $template;
        }

        // Fourth candidate: Global config.yml
        $templates[] = $this->config->get('general/listing_template');

        // Fifth candidate: fallback to 'listing.twig'
        $templates[] = 'listing.twig';

        return $templates;
    }

    /**
     * Select a template for taxonomy.
     *
     * @param string $taxonomyslug
     *
     * @return string[]
     */
    public function taxonomy($taxonomyslug)
    {
        $templates = [];

        // First candidate: defined specifically in the taxonomy
        if ($template = $this->config->get('taxonomy/' . $taxonomyslug . '/listing_template')) {
            $templates[] = $template;
        }

        // Second candidate: Theme-specific config.yml file.
        if ($template = $this->config->get('theme/listing_template')) {
            $templates[] = $template;
        }

        // Third candidate: Global config.yml
        $templates[] = $this->config->get('general/listing_template');

        return $templates;
    }

    /**
     * Select a search template.
     *
     * @return string[]
     */
    public function search()
    {
        $templates = [];

        // First candidate: specific search setting in global config.
        if ($template = $this->config->get('theme/search_results_template')) {
            $templates[] = $template;
        }

        // Second candidate: specific search setting in global config.
        if ($template = $this->config->get('general/search_results_template')) {
            $templates[] = $template;
        }

        // Third candidate: listing config setting.
        $templates[] = $this->config->get('general/listing_template');

        return $templates;
    }

    /**
     * Select a template to use for the "maintenance" page.
     *
     * @return string[]
     */
    public function maintenance()
    {
        $templates = [];

        // First candidate: Theme-specific config.
        if ($template = $this->config->get('theme/maintenance_template')) {
            $templates[] = $template;
        }

        // Second candidate: global config.
        $templates[] = $this->config->get('general/maintenance_template');

        return $templates;
    }
}
