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

        $this->initialize();
    }

    public function initialize()
    {
    }

    public function homepage()
    {

        $template = $this->app['config']->get('general/homepage_template');
        $chosen = 'homepage config';

        if (empty($template)) {
            $template = 'index.twig';
            $chosen = 'homepage fallback';
        }

        $this->app['log']->setValue('templatechosen', $this->app['config']->get('general/theme') . "/$template ($chosen)");

        return $template;

    }

    public function record($record) 
    {

        $template = $this->app['config']->get('general/record_template');
        $chosen = 'config';

        $templatefile = $this->app['paths']['themepath'] . '/' . $record->contenttype['singular_slug'] . '.twig';
        if (is_readable($templatefile)) {
            $template = $record->contenttype['singular_slug'] . ".twig";
            $chosen = 'singular_slug';
        }

        if (isset($record->contenttype['record_template'])) {
            $templatefile = $this->app['paths']['themepath'] . '/' . $record->contenttype['record_template'];
            if (file_exists($templatefile)) {
                $template = $record->contenttype['record_template'];
                $chosen = 'contenttype';
            }
        }

        foreach ($record->contenttype['fields'] as $name => $field) {
            if ($field['type'] == 'templateselect' && !empty($this->values[$name])) {
                $template = $this->values[$name];
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
        // Then, select which template to use, based on our 'cascading templates rules'
        if (!empty($contenttype['listing_template'])) {
            $template = $contenttype['listing_template'];
            $chosen = 'contenttype';
        } else {
            $filename = $this->app['paths']['themepath'] . '/' . $contenttype['slug'] . '.twig';
            if (file_exists($filename) && is_readable($filename)) {
                $template = $contenttype['slug'] . '.twig';
                $chosen = 'slug';
            } else {
                $template = $this->app['config']->get('general/listing_template');
                $chosen = 'config';

            }
        }    

        $this->app['log']->setValue('templatechosen', $this->app['config']->get('general/theme') . "/$template ($chosen)");

        return $template;

    }

    /**
     * Select a template for taxonomy.
     */
    public function taxonomy($taxonomyslug)
    {

        // Set the template based on the (optional) setting in taxonomy.yml, or fall back to default listing template
        if ($this->app['config']->get('taxonomy/' . $taxonomyslug . '/listing_template')) {
            $template = $this->app['config']->get('taxonomy/' . $taxonomyslug . '/listing_template');
        } else {
            $template = $this->app['config']->get('general/listing_template');
        }

        $chosen = 'taxonomy';

        $this->app['log']->setValue('templatechosen', $this->app['config']->get('general/theme') . "/$template ($chosen)");

        return $template;
    }


    /**
     * Select a search template. 
     */
    public function search()
    {

        $template = $this->app['config']->get('general/search_results_template', $this->app['config']->get('general/listing_template'));

        return $template;

    }

    /**
     * Select a template to use for the "maintenance" page. 
     */
    public function maintenance() 
    {

        $template = $this->app['config']->get('general/maintenance_template');

        return $template;
    }

}
