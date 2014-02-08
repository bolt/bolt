<?php
namespace MetaTags;
use Bolt\Extensions\Snippets\Location as SnippetLocation;

class Extension extends \Bolt\BaseExtension
{

    private $title;
    private $metas;
    private $record;
    private $executed = false;

    function info()
    {

        $data = array(
            'name' => "MetaTags",
            'description' => "Sets meta tags for search engine optimization (SEO) purposes.",
            'author' => "Xiao-Hu Tai",
            'link' => "http://bolt.cm",
            'version' => "0.1",
            'required_bolt_version' => "1.3",
            'highest_bolt_version' => "1.3",
            'type' => "SEO",
            'first_releasedate' => "2013-11-12",
            'latest_releasedate' => "2013-11-14",
            'dependencies' => "",
            'priority' => 10
        );

        return $data;

    }

    function initialize()
    {
        $this->config = isset($this->config) ? $this->config : array();
        $this->metas  = isset($this->config['meta']) ? $this->config['meta'] : array();

        // Run this after controller execution (in order to get the current $record),
        // but before the snippets are run (so that meta tags are added).
        $this->addTwigFunction('metatitle', 'metatitle');
        $this->app->after(array($this, "afterCallback"), 1);
    }


    // Specifically for <meta> tags
    function afterCallback() 
    {

        $record = $this->getRecord();
        $metas  = $this->setMetasFromRecord($record);

    }

    private function getRecord() 
    {

        if (isset($this->record)) {
            return $this->record;
        }

        $globalTwigVars = $this->app['twig']->getGlobals('record');

        if (isset($globalTwigVars['record'])) {
            $record = $globalTwigVars['record'];
        } else {
            $record = false;
        }

        return $record;

    }

    private function setMetasFromRecord($record)
    {
        // Only execute once. This may be invoked by {{ metatitle() }} or in the
        // after callback function.
        if ($this->executed) {
            return;
        }

        if ($record instanceof \Bolt\Content) {
            foreach ($this->metas as $name => $fields) {
                $value   = false;
                $params  = array();
                $filters = array();

                foreach ($fields as $field) {

                    if (is_array($field)) {
                        foreach ($field as $key => $options) {
                            $field   = $key;

                            if (isset($options['params'])) {
                                $params = $options['params'];
                            }
                            if (isset($options['filters'])) {
                                $filters = $options['filters'];
                            }
                            break;
                        }
                    }

                    if (empty($params)) {
                        $value    = $record->$field();
                    } else {
                        // Need reflection for function calls with custom parameters
                        try {
                            $function = new \ReflectionMethod($record, $field);
                            $value    = $function->invokeArgs($record, $params);
                        } catch(\ReflectionException $e) {
                            // echo $e->getMessage();
                        }
                    }

                    $value = (string)$value; // instanceof Twig_Markup

                    if (!empty($value)) {
                        break;
                    }
                }

                // add filters
                foreach ($filters as $filter) {
                    $value = preg_replace('/\s+/', ' ', $filter( $value ));
                }

                if (!empty($value)) {
                    $metatag = $this->meta($name, $value);
                    if ($metatag) {
                        $this->insertSnippet(SnippetLocation::AFTER_META, $metatag);
                    }
                }
            }
        }

        $this->executed = true;
    }

    function metatitle($separator = '', $sitename = '')
    {
        $record = $this->getRecord();
        $metas  = $this->setMetasFromRecord($record);

        if (!empty($separator) && !empty($sitename)) {
            $separator = " $separator ";
        }

        // This feature is specifically for homepages, where $sitename is
        // sufficient as a page title.
        if (!empty($sitename)) {
            if ( empty($this->title) || $this->title=='(empty)' ) {
                return $sitename;
            }
        }

        return $this->title . $separator . $sitename;
    }

    function meta($name, $content)
    {
        switch ($name) {

            case 'title':
                $this->title = $content;
                return false;

            default:
                $keyname = 'name';

                // For Open Graph Meta Tags, see http://ogp.me/
                if (strpos($name, 'og:') === 0) {
                    $keyname = 'property';
                }

                return sprintf('<meta %s="%s" content="%s">', $keyname, $name, $content);
        }
        
    }

}
