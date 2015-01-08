<?php

namespace Bolt;


/**
 * A class for choosing whichever template should be used.
 *
 */
class TemplateChooser
{

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Choose a template for the homepage.
     */
    public function homepage()
    {
        // First candidate: Global config.yml file.
        $template = $this->app['config']->get('general/homepage_template');
        $chosen = 'homepage config';

        // Second candidate: Theme-specific config.yml file.
        if ($this->app['config']->get('theme/homepage_template')) {
            $template = $this->app['config']->get('theme/homepage_template');
            $chosen = 'homepage config in theme';
        }

        // Fallback: "index.twig"
        if (empty($template)) {
            $template = 'index.twig';
            $chosen = 'homepage fallback';
        }

        $this->app['log']->setValue('templatechosen', $this->app['config']->get('general/theme') . "/$template ($chosen)");

        return $template;

    }

    /**
     * Choose a template for a single record page, like '/page/about' or
     * '/entry/lorum-ipsum'
     */
    public function record(\Bolt\Content $record)
    {
        // First candidate: global config.yml
        $template = $this->app['config']->get('general/record_template');
        $chosen = 'record config';

        // Second candidate: Theme-specific config.yml file.
        if ($this->app['config']->get('theme/record_template')) {
            $template = $this->app['config']->get('theme/record_template');
            $chosen = 'record config in theme';
        }

        // Third candidate: a template with the same filename as the name of
        // the contenttype.
        $templatefile = $this->app['paths']['themepath'] . '/' . $record->contenttype['singular_slug'] . '.twig';
        if (is_readable($templatefile)) {
            $template = $record->contenttype['singular_slug'] . ".twig";
            $chosen = 'singular_slug';
        }

        // Fourth candidate: defined specificaly in the contenttype.
        if (isset($record->contenttype['record_template'])) {
            $templatefile = $this->app['paths']['themepath'] . '/' . $record->contenttype['record_template'];
            if (file_exists($templatefile)) {
                $template = $record->contenttype['record_template'];
                $chosen = 'contenttype';
            }
        }

        // Fifth candidate: The record has a templateselect field, and it's set.
        foreach ($record->contenttype['fields'] as $name => $field) {
            if ($field['type'] == 'templateselect' && !empty($record->values[$name])) {
                $template = $record->values[$name];
                $chosen = 'record';
            }
        }

        $this->app['log']->setValue('templatechosen', $this->app['config']->get('general/theme') . "/$template ($chosen)");

        return $template;

    }

    /**
     * Select a template for listing pages.
     */
    public function listing($contenttype)
    {
        // First candidate: Global config.yml
        $template = $this->app['config']->get('general/listing_template');
        $chosen = 'listing config';

        // Second candidate: Theme-specific config.yml file.
        if ($this->app['config']->get('theme/listing_template')) {
            $template = $this->app['config']->get('theme/listing_template');
            $chosen = 'listing config in theme';
        }

        // Third candidate: a template with the same filename as the name of
        // the contenttype.
        $filename = $this->app['paths']['themepath'] . '/' . $contenttype['slug'] . '.twig';
        if (file_exists($filename) && is_readable($filename)) {
                $template = $contenttype['slug'] . '.twig';
                $chosen = 'slug';
        }

        // Fourth candidate: defined specificaly in the contenttype.
        if (!empty($contenttype['listing_template'])) {
            $template = $contenttype['listing_template'];
            $chosen = 'contenttype';
        }

        $this->app['log']->setValue('templatechosen', $this->app['config']->get('general/theme') . "/$template ($chosen)");

        return $template;

    }

    /**
     * Select a template for taxonomy.
     */
    public function taxonomy($taxonomyslug)
    {
        // First candidate: Global config.yml
        $template = $this->app['config']->get('general/listing_template');
        $chosen = 'taxonomy config';

        // Second candidate: Theme-specific config.yml file.
        if ($this->app['config']->get('theme/listing_template')) {
            $template = $this->app['config']->get('theme/listing_template');
            $chosen = 'taxonomy config in theme';
        }

        // Third candidate: defined specifically in the taxonomy
        if ($this->app['config']->get('taxonomy/' . $taxonomyslug . '/listing_template')) {
            $template = $this->app['config']->get('taxonomy/' . $taxonomyslug . '/listing_template');
            $chosen = 'taxonomy';
        }

        $this->app['log']->setValue('templatechosen', $this->app['config']->get('general/theme') . "/$template ($chosen)");

        return $template;
    }


    /**
     * Select a search template.
     */
    public function search()
    {
        // First candidate: listing config setting.
        $template = $this->app['config']->get('general/listing_template');
        $chosen = "listing config";

        // Second candidate: specific search setting in global config.
        if ($this->app['config']->get('general/search_results_template')) {
            $template = $this->app['config']->get('general/search_results_template');
            $chosen = 'search config';
        }

        // Third candidate: specific search setting in global config.
        if ($this->app['config']->get('theme/search_results_template')) {
            $template = $this->app['config']->get('theme/search_results_template');
            $chosen = 'search config in theme';
        }

        $this->app['log']->setValue('templatechosen', $this->app['config']->get('general/theme') . "/$template ($chosen)");

        return $template;

    }

    /**
     * Select a template to use for the "maintenance" page.
     */
    public function maintenance()
    {
        // First candidate: global config.
        $template = $this->app['config']->get('general/maintenance_template');

        // Second candidate: specific search setting in global config.
        if ($this->app['config']->get('theme/maintenance_template')) {
            $template = $this->app['config']->get('theme/maintenance_template');
            $chosen = 'search config';
        }

        $this->app['log']->setValue('templatechosen', $this->app['config']->get('general/theme') . "/$template ($chosen)");

        return $template;
    }
}
