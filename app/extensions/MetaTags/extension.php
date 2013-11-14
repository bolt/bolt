<?php
namespace MetaTags;
use Bolt\Extensions\Snippets\Location as SnippetLocation;

class Extension extends \Bolt\BaseExtension
{

    private $title;

    function info()
    {

        $data = array(
            'name' => "MetaTags",
            'description' => "Sets `<meta>` tags for search engine optimization (SEO) purposes.",
            'author' => "Xiao-Hu Tai",
            'link' => "http://bolt.cm",
            'version' => "0.1",
            'required_bolt_version' => "1.3",
            'highest_bolt_version' => "1.3",
            'type' => "SEO",
            'first_releasedate' => "2013-11-12",
            'latest_releasedate' => "2013-11-12",
            'dependancies' => "",
            'priority' => 10
        );

        return $data;

    }

    function initialize()
    {
        $this->addTwigFunction('metatitle', 'metatitle');

        if( !isset($this->config) ) {
            $this->config = array();
        }

        $result = $this->app['storage']->getContent( $this->app['paths']['current'] );
        $metas  = isset($this->config['meta']) ? $this->config['meta'] : array();

        if ($result instanceof \Bolt\Content) {
            $record = $result;

            foreach ($metas as $name => $fields) {
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
                    $value = $filter( $value );
                }

                if (!empty($value)) {
                    $metatag = $this->meta($name, $value);
                    if ($metatag) {
                        $this->insertSnippet(SnippetLocation::AFTER_META, $metatag);
                    }
                }
            }
        }
    }

    function metatitle($separator = '', $sitename = '')
    {
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
                return sprintf('<meta name="%s" content="%s">', $name, $content);
        }
        
    }

}