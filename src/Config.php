<?php

namespace Bolt;

use Bolt\Configuration\LowlevelException;
use Bolt\Library as Lib;
use Symfony\Component\Yaml;

/**
 * Class for our config object. Implemented as an extension of RecursiveArrayAccess
 *
 * @author Bob den Otter, bob@twokings.nl
 */
class Config
{
    protected $paths;

    private $app;
    private $data;
    private $defaultConfig = array();
    private $reservedFieldNames = array(
        'id', 'slug', 'datecreated', 'datechanged', 'datepublish', 'datedepublish', 'ownerid', 'username', 'status', 'link'
    );

    public $fields;

    private static $yamlParser;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        if (!$this->loadCache()) {
            $this->getConfig();
            $this->saveCache();

            // if we have to reload the config, we will also want to make sure the DB integrity is checked.
            Database\IntegrityChecker::invalidate();
        }

        $this->setTwigPath();
        $this->setCKPath();
        $this->fields = new Field\Manager();
    }

    /**
     * @param  string $basename
     * @param  array  $default
     * @param  mixed  $defaultConfigPath TRUE: use default config path
     *                                   FALSE: just use the raw basename
     *                                   string: use the given string as config
     *                                   file path
     * @return array
     */
    private function parseConfigYaml($basename, $default = array(), $defaultConfigPath = true)
    {
        if (!self::$yamlParser) {
            self::$yamlParser = new Yaml\Parser();
        }

        if (is_string($defaultConfigPath)) {
            $prefix = preg_replace('/\/+$/', '', $defaultConfigPath) . '/';
        } else {
            if ($defaultConfigPath) {
                $prefix = $this->app['resources']->getPath('config') . '/';
            } else {
                $prefix = '';
            }
        }

        $filename = $prefix . $basename;

        if (is_readable($filename)) {
            $yml = self::$yamlParser->parse(file_get_contents($filename) . "\n");

            // To prevent an edge-case where an existing-but-empty .yml file returns
            // something else (`NULL`) than a non-existing files (`array()`), we
            // check the result instead of returning it blindly.
            if (!empty($yml)) {
                return $yml;
            } else {
                return $default;
            }
        }

        return $default;
    }

    /**
     * Set a config value, using a path. For example:
     *
     * $app['config']->set('general/branding/name', 'Bolt');
     *
     * @param  string $path
     * @param  mixed  $value
     * @return bool
     */
    public function set($path, $value)
    {
        $path = explode('/', $path);

        // Only do something if we get at least one key.
        if (empty($path[0])) {
            $logline = "Config: can't set empty path to '" . (string) $value . "'";
            $this->app['log']->add($logline, 3, '', 'config');

            return false;
        }

        $part = & $this->data;

        foreach ($path as $key) {
            if (!isset($part[$key])) {
                $part[$key] = array();
            }

            $part = & $part[$key];
        }

        $part = $value;

        return true;
    }

    /**
     * Get a config value, using a path. For example:
     *
     * $var = $config->get('general/wysiwyg/ck/contentsCss');
     *
     * @param  string $path
     * @param  string $default
     * @return mixed
     */
    public function get($path, $default = null)
    {
        $path = explode('/', $path);

        // Only do something if we get at least one key.
        if (empty($path[0]) || !isset($this->data[$path[0]])) {
            return false;
        }

        $part = & $this->data;
        $value = null;

        foreach ($path as $key) {
            if (!isset($part[$key])) {
                $value = null;
                break;
            }

            $value = $part[$key];
            $part = & $part[$key];
        }

        if ($value !== null) {
            return $value;
        }

        return $default;
    }

    /**
     * Load the configuration from the various YML files.
     */
    public function getConfig()
    {
        $config = array();

        // Read the config and merge it. (note: We use temp variables to prevent
        // "Only variables should be passed by reference")
        $tempconfig            = $this->parseConfigYaml('config.yml');
        $tempconfiglocal       = $this->parseConfigYaml('config_local.yml');

        $config['general']     = Lib::array_merge_recursive_distinct($tempconfig, $tempconfiglocal);
        $config['taxonomy']    = $this->parseConfigYaml('taxonomy.yml');
        $tempContentTypes      = $this->parseConfigYaml('contenttypes.yml');
        $config['menu']        = $this->parseConfigYaml('menu.yml');
        $config['routing']     = $this->parseConfigYaml('routing.yml');
        $config['permissions'] = $this->parseConfigYaml('permissions.yml');
        $config['extensions']  = array();

        // fetch the theme config. requires special treatment due to the path
        $this->app['resources']->initializeConfig($config);
        $paths = $this->app['resources']->getPaths();
        $themeConfigFile = $paths['themepath'] . '/config.yml';
        $config['theme'] = $this->parseConfigYaml($themeConfigFile, array(), false);

        // @todo: If no config files can be found, get them from bolt.cm/files/default/

        $this->paths = $this->app['resources']->getPaths();
        $this->setDefaults();

        // Make sure old settings for 'contentsCss' are still picked up correctly
        if (isset($config['general']['wysiwyg']['ck']['contentsCss'])) {
            $config['general']['wysiwyg']['ck']['contentsCss'] = array(
                1 => $config['general']['wysiwyg']['ck']['contentsCss']
            );
        }

        // Make sure old settings for 'accept_file_types' are not still picked up. Before 1.5.4 we used to store them
        // as a regex-like string, and we switched to an array. If we find the old style, fall back to the defaults.
        if (isset($config['general']['accept_file_types']) && !is_array($config['general']['accept_file_types'])) {
            unset($config['general']['accept_file_types']);
        }

        // Merge the array with the defaults. Setting the required values that aren't already set.
        $config['general'] = Lib::array_merge_recursive_distinct($this->defaultConfig, $config['general']);

        // Make sure the cookie_domain for the sessions is set properly.
        if (empty($config['general']['cookies_domain'])) {
            if (isset($_SERVER['HTTP_HOST'])) {
                $hostname = $_SERVER['HTTP_HOST'];
            } elseif (isset($_SERVER['SERVER_NAME'])) {
                $hostname = $_SERVER['SERVER_NAME'];
            } else {
                $hostname = '';
            }

            // Don't set the domain for a cookie on a "TLD" - like 'localhost', or if the server_name is an IP-address
            if ((strpos($hostname, '.') > 0) && preg_match("/[a-z0-9]/i", $hostname)) {
                if (preg_match("/^www[0-9]*./", $hostname)) {
                    $config['general']['cookies_domain'] = '.' . preg_replace("/^www[0-9]*./", '', $hostname);
                } else {
                    $config['general']['cookies_domain'] = '.' . $hostname;
                }
                // Make sure we don't have consecutive '.'-s in the cookies_domain..
                $config['general']['cookies_domain'] = str_replace('..', '.', $config['general']['cookies_domain']);
            } else {
                $config['general']['cookies_domain'] = '';
            }
        }

        // Make sure Bolt's mount point is OK:
        $config['general']['branding']['path'] = '/' . safeString($config['general']['branding']['path']);

        // Make sure $config['taxonomy'] is an array. (if the file is empty, YAML parses it as NULL)
        if (empty($config['taxonomy'])) {
            $config['taxonomy'] = array();
        }

        // Clean up taxonomies
        foreach ($config['taxonomy'] as $key => $value) {
            if (!isset($config['taxonomy'][$key]['name'])) {
                $config['taxonomy'][$key]['name'] = ucwords($config['taxonomy'][$key]['slug']);
            }
            if (!isset($config['taxonomy'][$key]['singular_name'])) {
                if (isset($config['taxonomy'][$key]['singular_slug'])) {
                    $config['taxonomy'][$key]['singular_name'] = ucwords($config['taxonomy'][$key]['singular_slug']);
                } else {
                    $config['taxonomy'][$key]['singular_name'] = ucwords($config['taxonomy'][$key]['slug']);
                }
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
                foreach ($config['taxonomy'][$key]['options'] as $optionkey => $optionvalue) {
                    if (is_numeric($optionkey)) {
                        $optionkey = makeSlug($optionvalue);
                    }
                    $options[$optionkey] = $optionvalue;
                }
                $config['taxonomy'][$key]['options'] = $options;
            }

            // If taxonomy is like tags, set 'tagcloud' to true by default.
            if (($config['taxonomy'][$key]['behaves_like'] == 'tags') && (!isset($config['taxonomy'][$key]['tagcloud']))) {
                $config['taxonomy'][$key]['tagcloud'] = true;
            }
        }

        // Clean up contenttypes
        $config['contenttypes'] = array();
        foreach ($tempContentTypes as $key => $temp) {

            // If the slug isn't set, and the 'key' isn't numeric, use that as the slug.
            if (!isset($temp['slug']) && !is_numeric($key)) {
                $temp['slug'] = makeSlug($key);
            }

            // If neither 'name' nor 'slug' is set, we need to warn the user. Same goes for when
            // neither 'singular_name' nor 'singular_slug' is set.
            if (!isset($temp['name']) && !isset($temp['slug'])) {
                $error = sprintf("In contenttype <code>%s</code>, neither 'name' nor 'slug' is set. Please edit <code>contenttypes.yml</code>, and correct this.", $key);
                throw new LowlevelException($error);
            }
            if (!isset($temp['singular_name']) && !isset($temp['singular_slug'])) {
                $error = sprintf("In contenttype <code>%s</code>, neither 'singular_name' nor 'singular_slug' is set. Please edit <code>contenttypes.yml</code>, and correct this.", $key);
                throw new LowlevelException($error);
            }

            if (!isset($temp['slug'])) {
                $temp['slug'] = makeSlug($temp['name']);
            }
            if (!isset($temp['singular_slug'])) {
                $temp['singular_slug'] = makeSlug($temp['singular_name']);
            }
            if (!isset($temp['show_on_dashboard'])) {
                $temp['show_on_dashboard'] = true;
            }
            if (!isset($temp['show_in_menu'])) {
                $temp['show_in_menu'] = true;
            }
            if (!isset($temp['sort'])) {
                $temp['sort'] = '';
            }
            if (!isset($temp['default_status'])) {
                $temp['default_status'] = 'draft';
            }
            // Make sure all fields are lowercase and 'safe'.
            $tempfields = $temp['fields'];
            $temp['fields'] = array();

            // Set a default group and groups array.
            $currentgroup = false;
            $temp['groups'] = array();

            foreach ($tempfields as $key => $value) {
                $key = str_replace('-', '_', strtolower(safeString($key, true)));
                $temp['fields'][$key] = $value;

                // If field is a "file" type, make sure the 'extensions' are set, and it's an array.
                if ($temp['fields'][$key]['type'] == 'file' || $temp['fields'][$key]['type'] == 'filelist') {
                    if (empty($temp['fields'][$key]['extensions'])) {
                        $temp['fields'][$key]['extensions'] = array_intersect(
                            array('doc', 'docx', 'txt', 'md', 'pdf', 'xls', 'xlsx', 'ppt', 'pptx', 'csv'),
                            $config['general']['accept_file_types']
                        );
                    }

                    if (!is_array($temp['fields'][$key]['extensions'])) {
                        $temp['fields'][$key]['extensions'] = array($temp['fields'][$key]['extensions']);
                    }
                }

                // If field is an "image" type, make sure the 'extensions' are set, and it's an array.
                if ($temp['fields'][$key]['type'] == 'image' || $temp['fields'][$key]['type'] == 'imagelist') {
                    if (empty($temp['fields'][$key]['extensions'])) {
                        $temp['fields'][$key]['extensions'] = array_intersect(
                            array('gif', 'jpg', 'jpeg', 'png'),
                            $config['general']['accept_file_types']
                        );
                    }

                    if (!is_array($temp['fields'][$key]['extensions'])) {
                        $temp['fields'][$key]['extensions'] = array($temp['fields'][$key]['extensions']);
                    }
                }

                // If the field has a 'group', make sure it's added to the 'groups' array, so we can turn
                // them into tabs while rendering. This also makes sure that once you started with a group,
                // all others have a group too.
                if (!empty($temp['fields'][$key]['group'])) {
                    $currentgroup = $temp['fields'][$key]['group'];
                    $temp['groups'][] = $currentgroup;
                } else {
                    $temp['fields'][$key]['group'] = $currentgroup;
                }

            }

            // Make sure the 'uses' of the slug is an array.
            if (isset($temp['fields']['slug']) && isset($temp['fields']['slug']['uses']) &&
                !is_array($temp['fields']['slug']['uses'])
            ) {
                $temp['fields']['slug']['uses'] = array($temp['fields']['slug']['uses']);
            }

            // Make sure taxonomy is an array.
            if (isset($temp['taxonomy']) && !is_array($temp['taxonomy'])) {
                $temp['taxonomy'] = array($temp['taxonomy']);
            }

            // when adding relations, make sure they're added by their slug. Not their 'name' or 'singular name'.
            if (!empty($temp['relations']) && is_array($temp['relations'])) {
                foreach ($temp['relations'] as $relkey => $relation) {
                    if ($relkey != makeSlug($relkey)) {
                        $temp['relations'][makeSlug($relkey)] = $temp['relations'][$relkey];
                        unset($temp['relations'][$relkey]);
                    }
                }
            }

            // Make sure the 'groups' has unique elements, if there are any.
            if (!empty($temp['groups'])) {
                $temp['groups'] = array_unique($temp['groups']);
            } else {
                unset($temp['groups']);
            }

            $config['contenttypes'][$temp['slug']] = $temp;
        }

        // Set all the distinctive arrays as part of our Config object.
        $this->data = $config;
    }

    /**
     * Sanity checks for doubles in in contenttypes.
     */
    public function checkConfig()
    {
        $slugs = array();

        $wrongctype = false;

        foreach ($this->data['contenttypes'] as $key => $ct) {
            /**
             * Make sure any field that has a 'uses' parameter actually points to a field that exists.
             * For example, this will show a notice:
             * entries:
             *   name: Entries
             *     singular_name: Entry
             *     fields:
             *       title:
             *         type: text
             *         class: large
             *       slug:
             *         type: slug
             *         uses: name
             */
            foreach ($ct['fields'] as $fieldname => $field) {
                // Verify that the contenttype doesn't try to add fields that are reserved.
                if ($fieldname != 'slug' && in_array($fieldname, $this->reservedFieldNames)) {
                    $error = Lib::__(
                        'contenttypes.generic.reserved-name',
                        array('%contenttype%' => $key, '%field%' => $fieldname)
                    );
                    $this->app['session']->getFlashBag()->set('error', $error);

                    return;
                }

                // Check 'uses'. If it's an array, split it up, and check the separate parts. We also need to check
                // for the fields that are always present, like 'id'.
                if (is_array($field) && !empty($field['uses'])) {
                    foreach ($field['uses'] as $useField) {
                        if (!empty($field['uses']) && empty($ct['fields'][$useField]) && !in_array($useField, $this->reservedFieldNames)) {
                            $error = Lib::__(
                                'contenttypes.generic.wrong-use-field',
                                array('%contenttype%' => $key, '%field%' => $fieldname, '%uses%' => $useField)
                            );
                            $this->app['session']->getFlashBag()->set('error', $error);

                            return;
                        }
                    }
                }

                // Make sure we have a 'label', 'class', 'variant' and 'default'.
                if (!isset($field['label'])) {
                    $this->set("contenttypes/{$key}/fields/{$fieldname}/label", '');
                }
                if (!isset($field['class'])) {
                    $this->set("contenttypes/{$key}/fields/{$fieldname}/class", 'form-control');
                } else {
                    $this->set("contenttypes/{$key}/fields/{$fieldname}/class", 'form-control ' . $field['class']);
                }
                if (!isset($field['variant'])) {
                    $this->set("contenttypes/{$key}/fields/{$fieldname}/variant", '');
                }
                if (!isset($field['default'])) {
                    $this->set("contenttypes/{$key}/fields/{$fieldname}/default", '');
                }
                if (!isset($field['pattern'])) {
                    $this->set("contenttypes/{$key}/fields/{$fieldname}/pattern", '');
                }

                // Make sure the 'type' is in the list of allowed types
                if (!isset($field['type']) || !$this->fields->has($field['type'])) {
                    $error = Lib::__(
                        'contenttypes.generic.no-proper-type',
                        array('%contenttype%' => $key, '%field%' => $fieldname, '%type%' =>
                         $field['type'])
                    );
                    $this->app['session']->getFlashBag()->set('error', $error);
                    $wrongctype = true && $this->app['users']->getCurrentUsername();
                }
            }

            // Keep a running score of used slugs..
            if (!isset($slugs[$ct['slug']])) {
                $slugs[$ct['slug']] = 0;
            }
            $slugs[$ct['slug']]++;
            if (!isset($slugs[$ct['singular_slug']])) {
                $slugs[$ct['singular_slug']] = 0;
            }
            if ($ct['singular_slug'] != $ct['slug']) {
                $slugs[$ct['singular_slug']]++;
            }
        }

        // Check DB-tables integrity
        if (!$wrongctype && $this->app['integritychecker']->needsCheck() &&
           (count($this->app['integritychecker']->checkTablesIntegrity()) > 0) &&
            $this->app['users']->getCurrentUsername()) {
            $msg = Lib::__(
                "The database needs to be updated/repaired. Go to 'Settings' > '<a href=\"%link%\">Check Database</a>' to do this now.",
                array('%link%' => path('dbcheck'))
            );
            $this->app['session']->getFlashBag()->set('error', $msg);

            return;
        }

        // Sanity checks for taxonomy.yml
        foreach ($this->data['taxonomy'] as $key => $taxo) {
            // Show some helpful warnings if slugs or keys are not set correctly.
            if ($taxo['slug'] != $key) {
                $error = Lib::__(
                    "The identifier and slug for '%taxonomytype%' are the not the same ('%slug%' vs. '%taxonomytype%'). Please edit taxonomy.yml, and make them match to prevent inconsistencies between database storage and your templates.",
                    array('%taxonomytype%' => $key, '%slug%' => $taxo['slug'])
                );
                $this->app['session']->getFlashBag()->set('error', $error);

                return;
            }
        }

        // if there aren't any other errors, check for duplicates across contenttypes..
        if (!$this->app['session']->getFlashBag()->has('error')) {
            foreach ($slugs as $slug => $count) {
                if ($count > 1) {
                    $error = Lib::__(
                        "The slug '%slug%' is used in more than one contenttype. Please edit contenttypes.yml, and make them distinct.",
                        array('%slug%' => $slug)
                    );
                    $this->app['session']->getFlashBag()->set('error', $error);

                    return;
                }
            }
        }
    }

    /**
     * A getter to access the fields manager
     *
     * @return Field\Manager
     **/
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Assume sensible defaults for a number of options.
     */
    private function setDefaults()
    {
        $this->defaultConfig = array(
            'database'                    => array('prefix' => 'bolt_'),
            'sitename'                    => 'Default Bolt site',
            'homepage'                    => 'page/*',
            'homepage_template'           => 'index.twig',
            'locale'                      => 'en_GB',
            'recordsperpage'              => 10,
            'recordsperdashboardwidget'   => 5,
            'debug'                       => false,
            'debug_show_loggedoff'        => false,
            'debug_error_level'           => 6135,
            // equivalent to E_ALL &~ E_NOTICE &~ E_DEPRECATED &~ E_USER_DEPRECATED
            'debug_enable_whoops'         => true,
            'debug_permission_audit_mode' => false,
            'frontend_permission_checks'  => false,
            'strict_variables'            => false,
            'theme'                       => 'default',
            'debug_compressjs'            => true,
            'debug_compresscss'           => true,
            'listing_template'            => 'listing.twig',
            'listing_records'             => '5',
            'listing_sort'                => 'datepublish DESC',
            'caching'                     => array(
                'config'    => true,
                'rendering' => false,
                'templates' => false,
                'request'   => false
            ),
            'wysiwyg'                     => array(
                'images'      => false,
                'tables'      => false,
                'fontcolor'   => false,
                'align'       => false,
                'subsuper'    => false,
                'embed'       => true,
                'anchor'      => false,
                'underline'   => false,
                'strike'      => false,
                'blockquote'  => true,
                'codesnippet' => false,
                'specialchar' => false,
                'ck'          => array(
                    'allowedContent'          => true,
                    'autoParagraph'           => true,
                    'contentsCss'             => array(
                        $this->paths['app'] . 'view/lib/ckeditor/contents.css',
                        $this->paths['app'] . 'view/css/ckeditor.css',
                    ),
                    'filebrowserWindowWidth'  => 640,
                    'filebrowserWindowHeight' => 480
                ),
                'filebrowser' => array(
                    'browseUrl'      => $this->paths['async'] . 'filebrowser/',
                    'imageBrowseUrl' => $this->paths['bolt'] . 'files/files'
                ),
            ),
            'canonical'                   => !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
            'developer_notices'           => false,
            'cookies_use_remoteaddr'      => true,
            'cookies_use_browseragent'    => false,
            'cookies_use_httphost'        => true,
            'cookies_https_only'          => false,
            'cookies_lifetime'            => 14 * 24 * 3600,
            'thumbnails'                  => array(
                'default_thumbnail' => array(160, 120),
                'default_image'     => array(1000, 750),
                'quality'           => 75,
                'cropping'          => 'crop',
                'notfound_image'    => 'view/img/default_notfound.png',
                'error_image'       => 'view/img/default_error.png'
            ),
            'accept_file_types'           => explode(",", "twig,html,js,css,scss,gif,jpg,jpeg,png,ico,zip,tgz,txt,md,doc,docx,pdf,epub,xls,xlsx,csv,ppt,pptx,mp3,ogg,wav,m4a,mp4,m4v,ogv,wmv,avi,webm,svg"),
            'hash_strength'               => 10,
            'branding'                    => array(
                'name'        => 'Bolt',
                'path'        => '/bolt',
                'provided_by' => array()
            ),
            'maintenance_mode'            => false
        );
    }

    private function setTwigPath()
    {
        $themepath = $this->app['resources']->getPath("theme");
        $end = $this->getWhichEnd($this->get('general/branding/path'));

        if ($end == 'frontend' && file_exists($themepath)) {
            $twigpath = array($themepath);
        } else {
            $twigpath = array(realpath($this->app['resources']->getPath('app') . '/view/twig'));
        }

        // If the template path doesn't exist, attempt to set a Flash error on the dashboard.
        if (! file_exists($themepath) && isset($this->app['session']) && (gettype($this->app['session']) == 'object')) {
            $error = "Template folder 'theme/" . basename($this->get('general/theme')) . "' does not exist, or is not writable.";
            $this->app['session']->getFlashBag()->set('error', $error);
        }

        // We add these later, because the order is important: By having theme/ourtheme first,
        // files in that folder will take precedence. For instance when overriding the menu template.
        $twigpath[] = realpath($this->app['resources']->getPath('app') . '/theme_defaults');

        $this->data['twigpath'] = $twigpath;
    }

    public function setCKPath()
    {
        $this->paths = $this->app['resources']->getPaths();

        // Make sure the paths for CKeditor config are always set correctly..
        $this->set(
            'general/wysiwyg/ck/contentsCss',
            array(
                $this->paths['app'] . 'view/lib/ckeditor/contents.css',
                $this->paths['app'] . 'view/css/ckeditor.css'
            )
        );
        $this->set('general/wysiwyg/filebrowser/browseUrl', $this->app['resources']->getUrl('async') . 'filebrowser/');
        $this->set(
            'general/wysiwyg/filebrowser/imageBrowseUrl',
            $this->app['resources']->getUrl('bolt') . 'files/files/'
        );
    }

    private function loadCache()
    {
        /* Get the timestamps for the config files. config_local defaults to '0', because if it isn't present,
           it shouldn't trigger an update for the cache, while the others should.
        */
        $timestamps = array(
            file_exists(BOLT_CONFIG_DIR . '/config.yml') ? filemtime(BOLT_CONFIG_DIR . '/config.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR . '/taxonomy.yml') ? filemtime(BOLT_CONFIG_DIR . '/taxonomy.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR . '/contenttypes.yml') ? filemtime(BOLT_CONFIG_DIR . '/contenttypes.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR . '/menu.yml') ? filemtime(BOLT_CONFIG_DIR . '/menu.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR . '/routing.yml') ? filemtime(BOLT_CONFIG_DIR . '/routing.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR . '/permissions.yml') ? filemtime(BOLT_CONFIG_DIR . '/permissions.yml') : 10000000000,
            file_exists(BOLT_CONFIG_DIR . '/config_local.yml') ? filemtime(BOLT_CONFIG_DIR . '/config_local.yml') : 0,
        );
        if (file_exists($this->app['resources']->getPath('cache') . '/config_cache.php')) {
            $cachetimestamp = filemtime($this->app['resources']->getPath('cache') . '/config_cache.php');
        } else {
            $cachetimestamp = 0;
        }

        if ($cachetimestamp > max($timestamps)) {
            $this->data = Lib::loadSerialize($this->app['resources']->getPath('cache') . '/config_cache.php');

            // Check if we loaded actual data.
            if (count($this->data) < 4 || empty($this->data['general'])) {
                return false;
            }

            // Check to make sure the version is still the same. If not, effectively invalidate the
            // cached config to force a reload.
            if (!isset($this->data['version']) || ($this->data['version'] != $this->app->getVersion())) {
                return false;
            }

            // Trigger the config loaded event on the resource manager
            $this->app['resources']->initializeConfig($this->data);

            // Yup, all seems to be right.
            return true;

        }

        return false;
    }

    private function saveCache()
    {
        // Store the version number along with the config.
        $this->data['version'] = $this->app->getVersion();

        if ($this->get('general/caching/config')) {
            saveSerialize($this->app['resources']->getPath('cache') . '/config_cache.php', $this->data);

            return;
        }

        @unlink($this->app['resources']->getPath('cache') . '/config_cache.php');
    }

    /**
     * Get an associative array with the correct options for the chosen database type.
     *
     * @return array
     */

    public function getDBOptions()
    {
        $configdb = $this->data['general']['database'];

        if (isset($configdb['driver']) && in_array($configdb['driver'], array('pdo_sqlite', 'sqlite'))) {
            $basename = isset($configdb['databasename']) ? basename($configdb['databasename']) : 'bolt';
            if (getExtension($basename) != 'db') {
                $basename .= '.db';
            }

            if (isset($configdb["path"])) {
                $configpaths = $this->app['resources']->getPaths();
                if (substr($configdb['path'], 0, 1) !== "/") {
                    $configdb['path'] = $configpaths["rootpath"] . '/' . $configdb['path'];
                }
            }

            $dboptions = array(
                'driver' => 'pdo_sqlite',
                'path' => isset($configdb['path']) ? realpath($configdb['path']) . '/' . $basename : $this->app['resources']->getPath('database') . '/' . $basename,
                'randomfunction' => 'RANDOM()',
                'memory' => isset($configdb['memory']) ? true : false
            );
        } else {
            // Assume we configured it correctly. Yeehaa!

            if (empty($configdb['password'])) {
                $configdb['password'] = '';
            }

            $driver = (isset($configdb['driver']) ? $configdb['driver'] : 'pdo_mysql');
            $randomfunction = '';
            if (in_array($driver, array('mysql', 'mysqli'))) {
                $driver = 'pdo_mysql';
                $randomfunction = 'RAND()';
            }
            if (in_array($driver, array('postgres', 'postgresql'))) {
                $driver = 'pdo_pgsql';
                $randomfunction = 'RANDOM()';
            }

            $dboptions = array(
                'driver'         => $driver,
                'host'           => (isset($configdb['host']) ? $configdb['host'] : 'localhost'),
                'dbname'         => $configdb['databasename'],
                'user'           => $configdb['username'],
                'password'       => $configdb['password'],
                'randomfunction' => $randomfunction
            );

            $dboptions['charset'] = isset($configdb['charset']) ? $configdb['charset'] : 'utf8';
        }

        switch ($dboptions['driver']) {
            case 'pdo_mysql':
                $dboptions['port'] = isset($configdb['port']) ? $configdb['port'] : '3306';
                $dboptions['reservedwords'] = explode(
                    ',',
                    'accessible,add,all,alter,analyze,and,as,asc,asensitive,before,between,' .
                    'bigint,binary,blob,both,by,call,cascade,case,change,char,character,check,collate,column,condition,constraint,' .
                    'continue,convert,create,cross,current_date,current_time,current_timestamp,current_user,cursor,database,databases,' .
                    'day_hour,day_microsecond,day_minute,day_second,dec,decimal,declare,default,delayed,delete,desc,describe,' .
                    'deterministic,distinct,distinctrow,div,double,drop,dual,each,else,elseif,enclosed,escaped,exists,exit,explain,' .
                    'false,fetch,float,float4,float8,for,force,foreign,from,fulltext,get,grant,group,having,high_priority,hour_microsecond,' .
                    'hour_minute,hour_second,if,ignore,in,index,infile,inner,inout,insensitive,insert,int,int1,int2,int3,int4,int8,' .
                    'integer,interval,into,io_after_gtids,io_before_gtids,is,iterate,join,key,keys,kill,leading,leave,left,like,limit,' .
                    'linear,lines,load,localtime,localtimestamp,lock,long,longblob,longtext,loop,low_priority,master_bind,' .
                    'master_ssl_verify_server_cert,match,maxvalue,mediumblob,mediumint,mediumtext,middleint,minute_microsecond,' .
                    'minute_second,mod,modifies,natural,nonblocking,not,no_write_to_binlog,null,numeric,on,optimize,option,optionally,' .
                    'or,order,out,outer,outfile,partition,precision,primary,procedure,purge,range,read,reads,read_write,real,references,' .
                    'regexp,release,rename,repeat,replace,require,resignal,restrict,return,revoke,right,rlike,schema,schemas,' .
                    'second_microsecond,select,sensitive,separator,set,show,signal,smallint,spatial,specific,sql,sqlexception,sqlstate,' .
                    'sqlwarning,sql_big_result,sql_calc_found_rows,sql_small_result,ssl,starting,straight_join,table,terminated,then,' .
                    'tinyblob,tinyint,tinytext,to,trailing,trigger,true,undo,union,unique,unlock,unsigned,update,usage,use,using,utc_date,' .
                    'utc_time,utc_timestamp,values,varbinary,varchar,varcharacter,varying,when,where,while,with,write,xor,year_month,' .
                    'zerofill,nonblocking'
                );
                break;
            case 'pdo_sqlite':
                $dboptions['reservedwords'] = explode(
                    ',',
                    'abort,action,add,after,all,alter,analyze,and,as,asc,attach,autoincrement,' .
                    'before,begin,between,by,cascade,case,cast,check,collate,column,commit,conflict,constraint,create,cross,current_date,' .
                    'current_time,current_timestamp,database,default,deferrable,deferred,delete,desc,detach,distinct,drop,each,else,end,' .
                    'escape,except,exclusive,exists,explain,fail,for,foreign,from,full,glob,group,having,if,ignore,immediate,in,index,' .
                    'indexed,initially,inner,insert,instead,intersect,into,is,isnull,join,key,left,like,limit,match,natural,no,not,' .
                    'notnull,null,of,offset,on,or,order,outer,plan,pragma,primary,query,raise,references,regexp,reindex,release,rename,' .
                    'replace,restrict,right,rollback'
                );
                break;
            case 'pdo_pgsql':
                $dboptions['port'] = isset($configdb['port']) ? $configdb['port'] : '5432';
                $dboptions['reservedwords'] = explode(
                    ',',
                    'all,analyse,analyze,and,any,as,asc,authorization,between,bigint,binary,bit,' .
                    'boolean,both,case,cast,char,character,check,coalesce,collate,column,constraint,convert,create,cross,current_date,' .
                    'current_time,current_timestamp,current_user,dec,decimal,default,deferrable,desc,distinct,do,else,end,except,exists,' .
                    'extract,float,for,foreign,freeze,from,full,grant,group,having,ilike,in,initially,inner,int,integer,intersect,interval,' .
                    'into,is,isnull,join,leading,left,like,limit,localtime,localtimestamp,natural,nchar,new,none,not,notnull,null,nullif,' .
                    'numeric,off,offset,old,on,only,or,order,outer,overlaps,overlay,placing,position,primary,real,references,right,row,' .
                    'select,session_user,setof,similar,smallint,some,substring,table,then,time,timestamp,to,trailing,treat,trim,union,' .
                    'unique,user,using,varchar,verbose,when,where,false,true'
                );
        }

        return $dboptions;
    }

    /**
     * Utility function to determine which 'end' we're using right now. Can be either "frontend", "backend", "async" or "cli".
     *
     * @param  string $mountpoint
     * @return string
     */
    public function getWhichEnd($mountpoint = '')
    {
        if (empty($mountpoint)) {
            $mountpoint = $this->get('general/branding/path');
        }

        if (empty($_SERVER['REQUEST_URI'])) {
            // We're probably in CLI mode.
            $this->app['end'] = 'cli';

            return 'cli';
        }

        // Set scriptname, take care of odd '/./' in the SCRIPT_NAME, which lightspeed does.
        $scriptname = str_replace('/./', '/', $_SERVER['SCRIPT_NAME']);

        // Get the script's filename, but _without_ REQUEST_URI. We need to str_replace the slashes, because of a
        // weird quirk in dirname on windows: http://nl1.php.net/dirname#refsect1-function.dirname-notes
        $scriptdirname = '#' . str_replace("\\", "/", dirname($scriptname));
        $scripturi = str_replace($scriptdirname, '', '#' . $_SERVER['REQUEST_URI']);
        // make sure it starts with '/', like our mountpoint.
        if (empty($scripturi) || ($scripturi[0] != '/')) {
            $scripturi = '/' . $scripturi;
        }

        // If the request URI starts with '/bolt' or '/async' in the URL, we assume we're in the backend or in async.
        if ((substr($scripturi, 0, strlen($mountpoint)) == $mountpoint)) {
            $end = 'backend';
        } elseif ((substr($scripturi, 0, 6) == 'async/') || (strpos($scripturi, '/async/') !== false)) {
            $end = 'async';
        } else {
            $end = 'frontend';
        }

        $this->app['end'] = $end;

        return $end;
    }
}
