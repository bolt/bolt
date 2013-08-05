<?php

namespace Bolt;

use Doctrine\Common\Cache\FilesystemCache;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class for our config object. Implemented as an extension of RecursiveArrayAccess
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 */
class Config extends \Bolt\RecursiveArrayAccess
{

    function __construct($data = array()) {
        parent::__construct($data);

        $this->getConfig();

    }

    function getConfig()
    {

        // Read the config
        $yamlparser = new \Symfony\Component\Yaml\Parser();
        $config['general'] = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/config.yml') . "\n");
        $config['taxonomy'] = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/taxonomy.yml') . "\n");
        $tempcontenttypes = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/contenttypes.yml') . "\n");
        $config['menu'] = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/menu.yml') . "\n");

        // @todo: What is this? Do we want this 'local' config?
        if(file_exists(BOLT_CONFIG_DIR.'/config_local.yml')) {
            $localconfig = $yamlparser->parse(file_get_contents(BOLT_CONFIG_DIR.'/config_local.yml') . "\n");
            $config['general'] = array_merge($config['general'], $localconfig);
        }

        // @todo: If no config files can be found, get them from bolt.cm/files/default/

        $this->paths = getPaths($config);
        $this->setDefaults();

        if (isset($config['general']['wysiwyg']['ck']['contentsCss'])) {
            $config['general']['wysiwyg']['ck']['contentsCss'] = array(1 => $config['general']['wysiwyg']['ck']['contentsCss']);
        }
        $config['general'] = array_merge_recursive_distinct($this->defaultconfig, $config['general']);

        // Make sure the cookie_domain for the sessions is set properly.
        if (empty($config['general']['cookies_domain'])) {

            // Don't set the domain for a cookie on a "TLD" - like 'localhost', or if the server_name is an IP-address
            if (isset($_SERVER["SERVER_NAME"]) && (strpos($_SERVER["SERVER_NAME"], ".") > 0) && preg_match("/[a-z0-9]/i", $_SERVER["SERVER_NAME"]) ) {
                if (preg_match("/^www[0-9]*./",$_SERVER["SERVER_NAME"])) {
                    $config['general']['cookies_domain'] = "." . preg_replace("/^www[0-9]*./", "", $_SERVER["SERVER_NAME"]);
                } else {
                    $config['general']['cookies_domain'] = "." .$_SERVER["SERVER_NAME"];
                }
                // Make sure we don't have consecutive '.'-s in the cookies_domain..
                $config['general']['cookies_domain'] = str_replace("..", ".", $config['general']['cookies_domain']);
            } else {
                $config['general']['cookies_domain'] = "";
            }
        }

        // Make sure Bolt's mount point is OK:
        $config['general']['branding']['path'] = "/" . safeString($config['general']['branding']['path']);

        // Clean up taxonomies
        foreach ($config['taxonomy'] as $key => $value) {
            if (!isset($config['taxonomy'][$key]['name'])) {
                $config['taxonomy'][$key]['name'] = ucwords($config['taxonomy'][$key]['slug']);
            }
            if (!isset($config['taxonomy'][$key]['singular_name'])) {
                $config['taxonomy'][$key]['singular_name'] = ucwords($config['taxonomy'][$key]['singular_slug']);
            }
            if (!isset($config['taxonomy'][$key]['slug'])) {
                $config['taxonomy'][$key]['slug'] = strtolower(safeString($config['taxonomy'][$key]['name']));
            }
            if (!isset($config['taxonomy'][$key]['singular_slug'])) {
                $config['taxonomy'][$key]['singular_slug'] = strtolower(safeString($config['taxonomy'][$key]['singular_name']));
            }
            if (!isset($config['taxonomy'][$key]['has_sortorder'])) {
                $config['taxonomy'][$key]['has_sortorder'] = false;
            }

            // Make sure the options are $key => $value pairs, and not have implied integers for keys.
            if (!empty($config['taxonomy'][$key]['options']) && is_array($config['taxonomy'][$key]['options'])) {
                $options = array();
                foreach($config['taxonomy'][$key]['options'] as $optionkey => $value) {
                    if (is_numeric($optionkey)) {
                        $optionkey = strtolower(safeString($value));
                    }
                    $options[$optionkey] = $value;
                }
                $config['taxonomy'][$key]['options'] = $options;
            }

        }

        // Clean up contenttypes
        $config['contenttypes'] = array();
        foreach ($tempcontenttypes as $temp) {
            if (!isset($temp['slug'])) {
                $temp['slug'] = makeSlug($temp['name']);
            }
            if (!isset($temp['singular_slug'])) {
                $temp['singular_slug'] = makeSlug($temp['singular_name']);
            }
            if (!isset($temp['show_on_dashboard'])) {
                $temp['show_on_dashboard'] = true;
            }
            if (!isset($temp['sort'])) {
                $temp['sort'] = "id";
            }
            // Make sure all fields are lowercase and 'safe'.
            $tempfields = $temp['fields'];
            $temp['fields'] = array();
            foreach($tempfields as $key => $value) {
                $key = str_replace("-", "_", strtolower(safeString($key, true)));
                $temp['fields'][ $key ] = $value;
            }

            if (isset($temp['fields']['slug']) && isset($temp['fields']['slug']['uses']) &&
                !is_array($temp['fields']['slug']['uses'])) {
                $temp['fields']['slug']['uses'] = array($temp['fields']['slug']['uses']);
            }

            // Make sure taxonomy is an array.
            if (isset($temp['taxonomy']) && !is_array($temp['taxonomy'])) {
                $temp['taxonomy'] = array($temp['taxonomy']);
            }

            $config['contenttypes'][ $temp['slug'] ] = $temp;

        }


        $end = getWhichEnd($config['general']);

        // I don't think we can set Twig's path in runtime, so we have to resort to hackishness to set the path..
        $themepath = realpath(__DIR__.'/../../../theme/'. basename($config['general']['theme']));
        if ( isset( $config['general']['theme_path'] ) )
        {
            $themepath = BOLT_PROJECT_ROOT_DIR . $config['general']['theme_path'];
        }
        $config['theme_path'] = $themepath;

        if ( $end == "frontend" && file_exists($themepath) ) {
            $config['twigpath'] = array($themepath);
        } else {
            $config['twigpath'] = array(realpath(__DIR__.'/../../view'));
        }

        // If the template path doesn't exist, attempt to set a Flash error on the dashboard.
        if (!file_exists($themepath) && (gettype($this->app['session']) == "object") ) {
            $this->app['session']->getFlashBag()->set('error', "Template folder 'theme/" . basename($config['general']['theme']) . "' does not exist, or is not writable.");
            $this->app['log']->add("Template folder 'theme/" . basename($config['general']['theme']) . "' does not exist, or is not writable.", 3);
        }

        // We add these later, because the order is important: By having theme/ourtheme first,
        // files in that folder will take precedence. For instance when overriding the menu template.
        $config['twigpath'][] = realpath(__DIR__.'/../../theme_defaults');

        // Set all the distinctive arrays as part of our Config object.
        foreach ($config as $key => $array) {
            $this[$key] = $array;
        }

    }

    /**
     * Assume sensible defaults for a number of options.
     */
    private function setDefaults()
    {

        $this->defaultconfig = array(
            'sitename' => 'Default Bolt site',
            'homepage' => 'page/*',
            'homepage_template' => 'index.twig',
            'locale' => 'en_GB',
            'sitemap' => array(
                'template' => 'sitemap.twig',
                'xml_template' => 'sitemap_xml.twig',
                'ignore' => array(),
            ),
            'recordsperpage' => 10,
            'recordsperdashboardwidget' => 5,
            'debug' => false,
            'debug_show_loggedoff' => false,
            'strict_variables' => false,
            'theme' => "default",
            'debug_compressjs' => true,
            'debug_compresscss' => true,
            'listing_template' => 'listing.twig',
            'listing_records' => '5',
            'listing_sort' => 'datepublish DESC',
            'wysiwyg' => array(
                'images' => true,
                'tables' => false,
                'embed' => false,
                'fontcolor' => false,
                'align' => false,
                'subsuper' => false,
                'embed' => true,
                'anchor' => false,
                'ck' => array(
                    'allowedContent' => true,
                    'autoParagraph' => true,
                    'contentsCss' => array(
                        $this->paths['app'] . 'view/lib/ckeditor/contents.css',
                        $this->paths['app'] . 'view/css/ckeditor.css',
                    ),
                    'filebrowserWindowWidth' => 640,
                    'filebrowserWindowHeight' => 480
                ),
                'filebrowser' => array(
                    'browseUrl' => $this->paths['async'] . "filebrowser/",
                    'imageBrowseUrl' => $this->paths['bolt'] . "files/files"
                ),
            ),
            'canonical' => !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "",
            'developer_notices' => false,
            'cookies_use_remoteaddr' => true,
            'cookies_use_browseragent' => false,
            'cookies_use_httphost' => true,
            'cookies_https_only' => false,
            'cookies_lifetime' => 14*24*3600,
            'thumbnails' => array(
                'default_thumbnail' => array(160, 120),
                'default_image' => array(1000, 750),
                'quality' => 75,
                'cropping' => 'crop',
                'notfound_image' => 'view/img/default_notfound.png',
                'error_image' => 'view/img/default_error.png'
            ),
            'hash_strength' => 10,
            'branding' => array(
                'name' => "Bolt",
                'path' => "/bolt",
                'provided_by' => array()
            ),
            'maintenance_mode' => false
        );


    }

}
