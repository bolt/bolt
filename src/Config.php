<?php
namespace Bolt;

use Bolt\Controller\Zone;
use Bolt\Exception\LowlevelException;
use Bolt\Helpers\Arr;
use Bolt\Helpers\Str;
use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Cocur\Slugify\Slugify;
use Eloquent\Pathogen\PathInterface;
use Eloquent\Pathogen\RelativePathInterface;
use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml;
use Symfony\Component\Yaml\Parser;

/**
 * Class for our config object.
 *
 * @author Bob den Otter, bob@twokings.nl
 */
class Config
{
    /** @var Silex\Application */
    protected $app;

    /** @var array */
    protected $data;

    /** @var array */
    protected $defaultConfig = [];

    /** @var array */
    protected $reservedFieldNames = [
        'id', 'slug', 'datecreated', 'datechanged', 'datepublish', 'datedepublish', 'ownerid', 'username', 'status', 'link', 'templatefields'
    ];

    /** @var integer */
    protected $cachetimestamp;

    /**
     * Use {@see Config::getFields} instead.
     * Will be made protected in Bolt 3.0.
     *
     * @var Storage\Field\Manager
     */
    public $fields;

    /** @var boolean */
    public $notify_update;

    /** @var \Symfony\Component\Yaml\Parser */
    protected $yamlParser = false;

    /**
     * @param Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
        $this->fields = new Storage\Field\Manager();
        $this->defaultConfig = $this->getDefaults();

        $this->initialize();
    }

    protected function initialize()
    {
        if (!$this->loadCache()) {
            $this->data = $this->getConfig();
            $this->parseTemplatefields();
            $this->saveCache();

            // if we have to reload the config, we will also want to make sure the DB integrity is checked.
            $this->app['schema']->invalidate();
        } else {

            // In this case the cache is loaded, but because the path of the theme
            // folder is defined in the config file itself, we still need to check
            // retrospectively if we need to invalidate it.
            $this->checkValidCache();
        }

        $this->setCKPath();
    }

    /**
     * Read and parse a YAML configuration file
     *
     * @param string $filename The name of the YAML file to read
     * @param string $path     The (optional) path to the YAML file
     *
     * @return array
     */
    protected function parseConfigYaml($filename, $path = null)
    {
        // Initialise parser
        if ($this->yamlParser === false) {
            $this->yamlParser = new Parser();
        }

        // By default we assume that config files are located in app/config/
        $path = $path ?: $this->app['resources']->getPath('config');
        $filename = $path . '/' . $filename;

        if (!is_readable($filename)) {
            return [];
        }

        $yml = $this->yamlParser->parse(file_get_contents($filename) . "\n");

        // Unset the repeated nodes key after parse
        unset($yml['__nodes']);

        // Invalid, non-existing, or empty files return NULL
        return $yml ?: [];
    }

    /**
     * Set a config value, using a path.
     *
     * For example:
     * $app['config']->set('general/branding/name', 'Bolt');
     *
     * @param string $path
     * @param mixed  $value
     *
     * @return boolean
     */
    public function set($path, $value)
    {
        $path = explode('/', $path);

        // Only do something if we get at least one key.
        if (empty($path[0])) {
            $logline = "Config: can't set empty path to '" . (string) $value . "'";
            $this->app['logger.system']->critical($logline, ['event' => 'config']);

            return false;
        }

        $part = & $this->data;

        foreach ($path as $key) {
            if (!isset($part[$key])) {
                $part[$key] = [];
            }

            $part = & $part[$key];
        }

        $part = $value;

        return true;
    }

    /**
     * Get a config value, using a path.
     *
     * For example:
     * $var = $config->get('general/wysiwyg/ck/contentsCss');
     *
     * @param string       $path
     * @param string|array $default
     *
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
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [];

        $config['general']      = $this->parseGeneral();
        $config['taxonomy']     = $this->parseTaxonomy();
        $config['contenttypes'] = $this->parseContentTypes($config['general']);
        $config['menu']         = $this->parseConfigYaml('menu.yml');
        $config['routing']      = $this->parseConfigYaml('routing.yml');
        $config['permissions']  = $this->parseConfigYaml('permissions.yml');
        $config['extensions']   = [];

        // fetch the theme config. requires special treatment due to the path being dynamic
        $this->app['resources']->initializeConfig($config);
        $config['theme'] = $this->parseTheme($this->app['resources']->getPath('theme'), $config['general']);

        // @todo: If no config files can be found, get them from bolt.cm/files/default/
        return $config;
    }

    /**
     * Read and parse the config.yml and config_local.yml configuration files.
     *
     * @return array
     */
    protected function parseGeneral()
    {
        // Read the config and merge it. (note: We use temp variables to prevent
        // "Only variables should be passed by reference")
        $tempconfig = $this->parseConfigYaml('config.yml');
        $tempconfiglocal = $this->parseConfigYaml('config_local.yml');
        $general = Arr::mergeRecursiveDistinct($tempconfig, $tempconfiglocal);

        // Make sure old settings for 'contentsCss' are still picked up correctly
        if (isset($general['wysiwyg']['ck']['contentsCss'])) {
            $general['wysiwyg']['ck']['contentsCss'] = [
                1 => $general['wysiwyg']['ck']['contentsCss']
            ];
        }

        // Make sure old settings for 'accept_file_types' are not still picked up. Before 1.5.4 we used to store them
        // as a regex-like string, and we switched to an array. If we find the old style, fall back to the defaults.
        if (isset($general['accept_file_types']) && !is_array($general['accept_file_types'])) {
            unset($general['accept_file_types']);
        }

        // Merge the array with the defaults. Setting the required values that aren't already set.
        $general = Arr::mergeRecursiveDistinct($this->defaultConfig, $general);

        // Make sure the cookie_domain for the sessions is set properly.
        if (empty($general['cookies_domain'])) {
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
                    $general['cookies_domain'] = '.' . preg_replace("/^www[0-9]*./", '', $hostname);
                } else {
                    $general['cookies_domain'] = '.' . $hostname;
                }
                // Make sure we don't have consecutive '.'-s in the cookies_domain.
                $general['cookies_domain'] = str_replace('..', '.', $general['cookies_domain']);
            } else {
                $general['cookies_domain'] = '';
            }
        }

        // Make sure Bolt's mount point is OK:
        $general['branding']['path'] = '/' . Str::makeSafe($general['branding']['path']);

        $general['database'] = $this->parseDatabase($general['database']);

        return $general;
    }

    /**
     * Read and parse the taxonomy.yml configuration file.
     *
     * @return array
     */
    protected function parseTaxonomy()
    {
        $taxonomies = $this->parseConfigYaml('taxonomy.yml');

        foreach ($taxonomies as $key => $taxonomy) {
            if (!isset($taxonomy['name'])) {
                $taxonomy['name'] = ucwords($taxonomy['slug']);
            }
            if (!isset($taxonomy['singular_name'])) {
                if (isset($taxonomy['singular_slug'])) {
                    $taxonomy['singular_name'] = ucwords($taxonomy['singular_slug']);
                } else {
                    $taxonomy['singular_name'] = ucwords($taxonomy['slug']);
                }
            }
            if (!isset($taxonomy['slug'])) {
                $taxonomy['slug'] = strtolower(Str::makeSafe($taxonomy['name']));
            }
            if (!isset($taxonomy['singular_slug'])) {
                $taxonomy['singular_slug'] = strtolower(Str::makeSafe($taxonomy['singular_name']));
            }
            if (!isset($taxonomy['has_sortorder'])) {
                $taxonomy['has_sortorder'] = false;
            }
            if (!isset($taxonomy['allow_spaces'])) {
                $taxonomy['allow_spaces'] = false;
            }

            // Make sure the options are $key => $value pairs, and not have implied integers for keys.
            if (!empty($taxonomy['options']) && is_array($taxonomy['options'])) {
                $options = [];
                foreach ($taxonomy['options'] as $optionkey => $optionvalue) {
                    if (is_numeric($optionkey)) {
                        $optionkey = Slugify::create()->slugify($optionvalue);
                    }
                    $options[$optionkey] = $optionvalue;
                }
                $taxonomy['options'] = $options;
            }

            // If taxonomy is like tags, set 'tagcloud' to true by default.
            if (($taxonomy['behaves_like'] == 'tags') && (!isset($taxonomy['tagcloud']))) {
                $taxonomy['tagcloud'] = true;
            }

            $taxonomies[$key] = $taxonomy;
        }

        return $taxonomies;
    }

    /**
     * Read and parse the contenttypes.yml configuration file.
     *
     * @param array $generalConfig
     *
     * @return array
     */
    protected function parseContentTypes(array $generalConfig)
    {
        $contentTypes = [];
        $tempContentTypes = $this->parseConfigYaml('contenttypes.yml');
        foreach ($tempContentTypes as $key => $contentType) {
            $contentType = $this->parseContentType($key, $contentType, $generalConfig);
            $contentTypes[$contentType['slug']] = $contentType;
        }

        return $contentTypes;
    }

    /**
     * Read and parse the current theme's config.yml configuration file.
     *
     * @param string $themePath
     * @param array  $generalConfig
     *
     * @return array
     */
    protected function parseTheme($themePath, array $generalConfig)
    {
        $themeConfig = $this->parseConfigYaml('config.yml', $themePath);

        if ((isset($themeConfig['templatefields'])) && (is_array($themeConfig['templatefields']))) {
            $templateContentTypes = [];

            foreach ($themeConfig['templatefields'] as $template => $templateFields) {
                $fieldsContenttype = [
                    'fields'        => $templateFields,
                    'singular_name' => 'Template Fields ' . $template
                ];

                $templateContentTypes[$template] = $this->parseContentType($template, $fieldsContenttype, $generalConfig);
            }

            $themeConfig['templatefields'] = $templateContentTypes;
        }

        return $themeConfig;
    }

    /**
     * This method pulls the templatefields config from the theme config and appends it
     * to the contenttypes configuration.
     */
    protected function parseTemplatefields()
    {
        $theme = $this->data['theme'];

        if (isset($theme['templatefields'])) {
            foreach ($this->data['contenttypes'] as $key => $ct) {
                foreach ($ct['fields'] as $field) {
                    if (isset($field['type']) && $field['type'] === 'templateselect') {
                        $this->data['contenttypes'][$key]['templatefields'] = $theme['templatefields'];
                    }
                }
            }
        }
    }

    /**
     * Parse a single Contenttype configuration array.
     *
     * @param string $key
     * @param array  $contentType
     * @param array  $generalConfig
     *
     * @throws LowlevelException
     *
     * @return array
     */
    protected function parseContentType($key, $contentType, $generalConfig)
    {
        // If the slug isn't set, and the 'key' isn't numeric, use that as the slug.
        if (!isset($contentType['slug']) && !is_numeric($key)) {
            $contentType['slug'] = Slugify::create()->slugify($key);
        }

        // If neither 'name' nor 'slug' is set, we need to warn the user. Same goes for when
        // neither 'singular_name' nor 'singular_slug' is set.
        if (!isset($contentType['name']) && !isset($contentType['slug'])) {
            $error = sprintf("In contenttype <code>%s</code>, neither 'name' nor 'slug' is set. Please edit <code>contenttypes.yml</code>, and correct this.", $key);
            throw new LowlevelException($error);
        }
        if (!isset($contentType['singular_name']) && !isset($contentType['singular_slug'])) {
            $error = sprintf("In contenttype <code>%s</code>, neither 'singular_name' nor 'singular_slug' is set. Please edit <code>contenttypes.yml</code>, and correct this.", $key);
            throw new LowlevelException($error);
        }

        if (!isset($contentType['slug'])) {
            $contentType['slug'] = Slugify::create()->slugify($contentType['name']);
        }
        if (!isset($contentType['singular_slug'])) {
            $contentType['singular_slug'] = Slugify::create()->slugify($contentType['singular_name']);
        }
        if (!isset($contentType['show_on_dashboard'])) {
            $contentType['show_on_dashboard'] = true;
        }
        if (!isset($contentType['show_in_menu'])) {
            $contentType['show_in_menu'] = true;
        }
        if (!isset($contentType['sort'])) {
            $contentType['sort'] = false;
        }
        if (!isset($contentType['default_status'])) {
            $contentType['default_status'] = 'draft';
        }
        if (!isset($contentType['viewless'])) {
            $contentType['viewless'] = false;
        }
        if (!isset($contentType['liveeditor'])) {
            $contentType['liveeditor'] = true;
        }
        // Override contenttype setting with view and config settings
        if (($contentType['viewless']) || (!$generalConfig['liveeditor'])) {
            $contentType['liveeditor'] = false;
        }
        // Allow explicit setting of a Contenttype's table name suffix. We default
        // to slug if not present as it has been this way since Bolt v1.2.1
        if (!isset($contentType['tablename'])) {
            $contentType['tablename'] = $contentType['slug'];
        } else {
            $contentType['tablename'] = Slugify::create()->slugify($contentType['tablename']);
        }

        list($fields, $groups) = $this->parseFieldsAndGroups($contentType['fields'], $generalConfig);
        $contentType['fields'] = $fields;
        $contentType['groups'] = $groups;

        // Make sure taxonomy is an array.
        if (isset($contentType['taxonomy'])) {
            $contentType['taxonomy'] = (array) $contentType['taxonomy'];
        }

        // when adding relations, make sure they're added by their slug. Not their 'name' or 'singular name'.
        if (!empty($contentType['relations']) && is_array($contentType['relations'])) {
            foreach (array_keys($contentType['relations']) as $relkey) {
                if ($relkey != Slugify::create()->slugify($relkey)) {
                    $contentType['relations'][Slugify::create()->slugify($relkey)] = $contentType['relations'][$relkey];
                    unset($contentType['relations'][$relkey]);
                }
            }
        }

        return $contentType;
    }

    /**
     * Parse a Contenttype's filed and determine the grouping
     *
     * @param array $fields
     * @param array $generalConfig
     *
     * @return array
     */
    protected function parseFieldsAndGroups(array $fields, array $generalConfig)
    {
        $acceptableFileTypes = $generalConfig['accept_file_types'];

        $currentGroup = 'ungrouped';
        $groups = [];
        $hasGroups = false;

        foreach ($fields as $key => $field) {
            unset($fields[$key]);
            $key = str_replace('-', '_', strtolower(Str::makeSafe($key, true)));

            // If field is a "file" type, make sure the 'extensions' are set, and it's an array.
            if ($field['type'] == 'file' || $field['type'] == 'filelist') {
                if (empty($field['extensions'])) {
                    $field['extensions'] = $acceptableFileTypes;
                }

                $field['extensions'] = (array) $field['extensions'];
            }

            // If field is an "image" type, make sure the 'extensions' are set, and it's an array.
            if ($field['type'] == 'image' || $field['type'] == 'imagelist') {
                if (empty($field['extensions'])) {
                    $field['extensions'] = array_intersect(
                        ['gif', 'jpg', 'jpeg', 'png'],
                        $acceptableFileTypes
                    );
                }

                $field['extensions'] = (array) $field['extensions'];
            }

            // Make indexed arrays into associative for select fields
            // e.g.: [ 'yes', 'no' ] => { 'yes': 'yes', 'no': 'no' }
            if ($field['type'] === 'select' && isset($field['values']) && is_array($field['values']) && Arr::isIndexedArray($field['values'])) {
                $field['values'] = array_combine($field['values'], $field['values']);
            }

            if (!empty($field['group'])) {
                $hasGroups = true;
            }

            // Make sure we have these keys and every field has a group set.
            $field = array_replace(
                [
                    'class'   => '',
                    'default' => '',
                    'group'   => $currentGroup,
                    'label'   => '',
                    'variant' => '',
                ],
                $field
            );

            // Collect group data for rendering.
            // Make sure that once you started with group all following have that group, too.
            $currentGroup = $field['group'];
            $groups[$currentGroup] = 1;

            $fields[$key] = $field;
        }

        // Make sure the 'uses' of the slug is an array.
        if (isset($fields['slug']) && isset($fields['slug']['uses'])) {
            $fields['slug']['uses'] = (array) $fields['slug']['uses'];
        }

        return [$fields, $hasGroups ? array_keys($groups) : false];
    }

    /**
     * Parse and fine-tune the database configuration.
     *
     * @param array $options
     *
     * @return array
     */
    protected function parseDatabase(array $options)
    {
        // Make sure prefix ends with underscore
        if (substr($options['prefix'], strlen($options['prefix']) - 1) !== '_') {
            $options['prefix'] .= '_';
        }

        // Parse master connection parameters
        $master = $this->parseConnectionParams($options);
        // Merge master connection into options
        $options = array_replace($options, $master);

        // Add platform specific random functions
        $driver = Str::replaceFirst('pdo_', '', $options['driver']);
        if ($driver === 'sqlite') {
            $options['driver'] = 'pdo_sqlite';
            $options['randomfunction'] = 'RANDOM()';
        } elseif (in_array($driver, ['mysql', 'mysqli'])) {
            $options['driver'] = 'pdo_mysql';
            $options['randomfunction'] = 'RAND()';
        } elseif (in_array($driver, ['pgsql', 'postgres', 'postgresql'])) {
            $options['driver'] = 'pdo_pgsql';
            $options['randomfunction'] = 'RANDOM()';
        }

        // Parse SQLite separately since it has to figure out database path
        if ($driver === 'sqlite') {
            return $this->parseSqliteOptions($options);
        }

        // If no slaves return with single connection
        if (empty($options['slaves'])) {
            return $options;
        }

        // Specify we want a master slave connection
        $options['wrapperClass'] = '\Doctrine\DBAL\Connections\MasterSlaveConnection';

        // Add master connection where MasterSlaveConnection looks for it.
        $options['master'] = $master;

        // Parse each slave connection parameters
        foreach ($options['slaves'] as $name => $slave) {
            $options['slaves'][$name] = $this->parseConnectionParams($slave, $master);
        }

        return $options;
    }

    /**
     * Fine-tune Sqlite configuration parameters.
     *
     * @param array $config
     *
     * @return array
     */
    protected function parseSqliteOptions(array $config)
    {
        if (isset($config['memory']) && $config['memory']) {
            // If in-memory, no need to parse paths
            unset($config['path']);

            return $config;
        } else {
            // Prevent SQLite driver from trying to use in-memory connection
            unset($config['memory']);
        }

        // Get path from config or use database path
        if (isset($config['path'])) {
            $path = $this->app['pathmanager']->create($config['path']);
            // If path is relative, resolve against root path
            if ($path instanceof RelativePathInterface) {
                $path = $path->resolveAgainst($this->app['resources']->getPathObject('root'));
            }
        } else {
            $path = $this->app['resources']->getPathObject('database');
        }

        // If path has filename with extension, use that
        if ($path->hasExtension()) {
            $config['path'] = $path->string();

            return $config;
        }

        // Use database name for filename
        /** @var PathInterface $filename */
        $filename = $this->app['pathmanager']->create(basename($config['dbname']));
        if (!$filename->hasExtension()) {
            $filename = $filename->joinExtensions('db');
        }

        // Join filename with database path
        $config['path'] = $path->joinAtoms($filename)->string();

        return $config;
    }

    /**
     * Parses params to valid connection parameters:
     * - Defaults are merged into the params
     * - Bolt keys are converted to Doctrine keys
     * - Invalid keys are filtered out
     *
     * @param array|string $params
     * @param array        $defaults
     *
     * @return array
     */
    protected function parseConnectionParams($params, $defaults = [])
    {
        // Handle host shortcut
        if (is_string($params)) {
            $params = ['host' => $params];
        }

        // Convert keys from Bolt
        $replacements = [
            'databasename' => 'dbname',
            'username'     => 'user',
        ];
        foreach ($replacements as $old => $new) {
            if (isset($params[$old])) {
                $params[$new] = $params[$old];
                unset($params[$old]);
            }
        }

        // Merge in defaults
        $params = array_replace($defaults, $params);

        // Filter out invalid keys
        $validKeys = [
            'user', 'password', 'host', 'port', 'dbname', 'charset',      // common
            'path', 'memory',                                             // Qqlite
            'unix_socket', 'driverOptions',                               // MySql
            'sslmode',                                                    // PostgreSQL
            'servicename', 'service', 'pooled', 'instancename', 'server', // Oracle
            'persistent',                                                 // SQL Anywhere
        ];
        $params = array_intersect_key($params, array_flip($validKeys));

        return $params;
    }

    /**
     * Sanity checks for doubles in in contenttypes.
     */
    public function checkConfig()
    {
        $slugs = [];

        $wrongctype = false;

        foreach ($this->data['contenttypes'] as $key => $ct) {
            /**
             * Make sure any field that has a 'uses' parameter actually points to a field that exists.
             *
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
                    $error = Trans::__(
                        'contenttypes.generic.reserved-name',
                        ['%contenttype%' => $key, '%field%' => $fieldname]
                    );
                    $this->app['logger.flash']->error($error);

                    return;
                }

                // Check 'uses'. If it's an array, split it up, and check the separate parts. We also need to check
                // for the fields that are always present, like 'id'.
                if (!empty($field['uses']) && is_array($field['uses'])) {
                    foreach ((array) $field['uses'] as $useField) {
                        if (!empty($field['uses']) && empty($ct['fields'][$useField]) && !in_array($useField, $this->reservedFieldNames)) {
                            $error = Trans::__(
                                'contenttypes.generic.wrong-use-field',
                                ['%contenttype%' => $key, '%field%' => $fieldname, '%uses%' => $useField]
                            );
                            $this->app['logger.flash']->error($error);

                            return;
                        }
                    }
                }

                // Make sure the 'type' is in the list of allowed types
                if (!isset($field['type']) || !$this->fields->has($field['type'])) {
                    $error = Trans::__(
                        'contenttypes.generic.no-proper-type', [
                            '%contenttype%' => $key,
                            '%field%'       => $fieldname,
                            '%type%'        => $field['type']
                        ]
                    );
                    $this->app['logger.flash']->error($error);
                    $wrongctype = true && $this->app['users']->getCurrentUsername();
                }
            }

            // Keep a running score of used slugs.
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
        if (!$wrongctype && $this->app['schema']->needsCheck() &&
           ($this->app['schema']->needsUpdate()) &&
            $this->app['users']->getCurrentUsername()) {
            $msg = Trans::__(
                "The database needs to be updated/repaired. Go to 'Configuration' > '<a href=\"%link%\">Check Database</a>' to do this now.",
                ['%link%' => $this->app['url_generator']->generate('dbcheck')]
            );
            $this->app['logger.flash']->error($msg);

            return;
        }

        // Sanity checks for taxonomy.yml
        foreach ($this->data['taxonomy'] as $key => $taxo) {
            // Show some helpful warnings if slugs or keys are not set correctly.
            if ($taxo['slug'] != $key) {
                $error = Trans::__(
                    "The identifier and slug for '%taxonomytype%' are the not the same ('%slug%' vs. '%taxonomytype%'). Please edit taxonomy.yml, and make them match to prevent inconsistencies between database storage and your templates.",
                    ['%taxonomytype%' => $key, '%slug%' => $taxo['slug']]
                );
                $this->app['logger.flash']->error($error);

                return;
            }
        }

        // if there aren't any other errors, check for duplicates across contenttypes.
        if (!$this->app['logger.flash']->has('error')) {
            foreach ($slugs as $slug => $count) {
                if ($count > 1) {
                    $error = Trans::__(
                        "The slug '%slug%' is used in more than one contenttype. Please edit contenttypes.yml, and make them distinct.",
                        ['%slug%' => $slug]
                    );
                    $this->app['logger.flash']->error($error);

                    return;
                }
            }
        }
    }

    /**
     * A getter to access the fields manager.
     *
     * @return Storage\Field\Manager
     **/
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Assume sensible defaults for a number of options.
     */
    protected function getDefaults()
    {
        return [
            'database'    => [
                'driver'         => 'sqlite',
                'host'           => 'localhost',
                'slaves'         => [],
                'dbname'         => 'bolt',
                'prefix'         => 'bolt_',
                'charset'        => 'utf8',
                'randomfunction' => '',
            ],
            'sitename'                    => 'Default Bolt site',
            'homepage'                    => 'page/*',
            'locale'                      => 'en_GB',
            'recordsperpage'              => 10,
            'recordsperdashboardwidget'   => 5,
            'systemlog'                   => [
                'enabled' => true
            ],
            'changelog'                   => [
                'enabled' => false
            ],
            'debuglog'                    => [
                'enabled'  => false,
                'level'    => 'DEBUG',
                'filename' => 'bolt-debug.log'
            ],
            'debug'                       => false,
            'debug_show_loggedoff'        => false,
            'debug_error_level'           => 6135,
            // equivalent to E_ALL &~ E_NOTICE &~ E_DEPRECATED &~ E_USER_DEPRECATED
            'debug_enable_whoops'         => true,
            'debug_permission_audit_mode' => false,
            'strict_variables'            => false,
            'theme'                       => 'default',
            'debug_compressjs'            => true,
            'debug_compresscss'           => true,
            'listing_template'            => 'listing.twig',
            'listing_records'             => '5',
            'listing_sort'                => 'datepublish DESC',
            'caching'                     => [
                'config'    => true,
                'rendering' => false,
                'templates' => false,
                'request'   => false
            ],
            'wysiwyg'                     => [
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
                'styles'      => false,
                'ck'          => [
                    'allowedContent'          => true,
                    'autoParagraph'           => true,
                    'contentsCss'             => [
                        $this->app['resources']->getUrl('app') . 'view/css/ckeditor-contents.css',
                        $this->app['resources']->getUrl('app') . 'view/css/ckeditor.css',
                    ],
                    'filebrowserWindowWidth'  => 640,
                    'filebrowserWindowHeight' => 480
                ],
                'filebrowser' => [
                    'browseUrl'      => $this->app['resources']->getUrl('async') . 'recordbrowser/',
                    'imageBrowseUrl' => $this->app['resources']->getUrl('bolt') . 'files/files'
                ],
            ],
            'liveeditor'                  => true,
            'canonical'                   => !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
            'developer_notices'           => false,
            'cookies_use_remoteaddr'      => true,
            'cookies_use_browseragent'    => false,
            'cookies_use_httphost'        => true,
            'cookies_lifetime'            => 14 * 24 * 3600,
            'enforce_ssl'                 => false,
            'thumbnails'                  => [
                'default_thumbnail' => [160, 120],
                'default_image'     => [1000, 750],
                'quality'           => 75,
                'cropping'          => 'crop',
                'notfound_image'    => 'view/img/default_notfound.png',
                'error_image'       => 'view/img/default_error.png'
            ],
            'accept_file_types'           => explode(',', 'twig,html,js,css,scss,gif,jpg,jpeg,png,ico,zip,tgz,txt,md,doc,docx,pdf,epub,xls,xlsx,csv,ppt,pptx,mp3,ogg,wav,m4a,mp4,m4v,ogv,wmv,avi,webm,svg'),
            'hash_strength'               => 10,
            'branding'                    => [
                'name'        => 'Bolt',
                'path'        => '/bolt',
                'provided_by' => []
            ],
            'maintenance_mode'            => false,
            'headers'                     => [
                'x_frame_options'     => true
            ]
        ];
    }

    /**
     * Build an array of Twig paths.
     *
     * @return string[]
     */
    public function getTwigPath()
    {
        $themepath = $this->app['resources']->getPath('templatespath');

        $twigpath = [];

        if (file_exists($themepath)) {
            $twigpath[] = $themepath;
        } else {
            // If the template path doesn't exist, flash error on the dashboard.
            $relativethemepath = basename($this->get('general/theme'));
            $theme = $this->get('theme');
            if (isset($theme['template_directory'])) {
                $relativethemepath .= '/' . $this->get('theme/template_directory');
            }

            $error = "Template folder 'theme/" . $relativethemepath . "' does not exist, or is not writable.";
            $this->app['logger.flash']->error($error);
        }

        // We add these later, because the order is important: By having theme/ourtheme first,
        // files in that folder will take precedence. For instance when overriding the menu template.
        $twigpath[] = realpath($this->app['resources']->getPath('app/theme_defaults'));

        return $twigpath;
    }

    /**
     * Will be made protected in Bolt 3.0.
     */
    public function setCKPath()
    {
        $app = $this->app['resources']->getUrl('app');

        // Make sure the paths for CKeditor config are always set correctly.
        $this->set(
            'general/wysiwyg/ck/contentsCss',
            [
                $app . 'view/css/ckeditor-contents.css',
                $app . 'view/css/ckeditor.css'
            ]
        );
        $this->set('general/wysiwyg/filebrowser/browseUrl', $this->app['resources']->getUrl('async') . 'recordbrowser/');
        $this->set(
            'general/wysiwyg/filebrowser/imageBrowseUrl',
            $this->app['resources']->getUrl('bolt') . 'files/files/'
        );
    }

    /**
     * Attempt to load cached configuration files.
     *
     * @return boolean
     */
    protected function loadCache()
    {
        $dir = $this->app['resources']->getPath('config');
        /* Get the timestamps for the config files. config_local defaults to '0', because if it isn't present,
           it shouldn't trigger an update for the cache, while the others should.
        */
        $timestamps = [
            file_exists($dir . '/config.yml') ? filemtime($dir . '/config.yml') : 10000000000,
            file_exists($dir . '/taxonomy.yml') ? filemtime($dir . '/taxonomy.yml') : 10000000000,
            file_exists($dir . '/contenttypes.yml') ? filemtime($dir . '/contenttypes.yml') : 10000000000,
            file_exists($dir . '/menu.yml') ? filemtime($dir . '/menu.yml') : 10000000000,
            file_exists($dir . '/routing.yml') ? filemtime($dir . '/routing.yml') : 10000000000,
            file_exists($dir . '/permissions.yml') ? filemtime($dir . '/permissions.yml') : 10000000000,
            file_exists($dir . '/config_local.yml') ? filemtime($dir . '/config_local.yml') : 0,
        ];
        if (file_exists($this->app['resources']->getPath('cache/config_cache.php'))) {
            $this->cachetimestamp = filemtime($this->app['resources']->getPath('cache/config_cache.php'));
        } else {
            $this->cachetimestamp = 0;
        }

        if ($this->cachetimestamp > max($timestamps)) {
            $this->data = Lib::loadSerialize($this->app['resources']->getPath('cache/config_cache.php'));

            // Check if we loaded actual data.
            if (count($this->data) < 4 || empty($this->data['general'])) {
                return false;
            }

            // Check to make sure the version is still the same. If not, effectively invalidate the
            // cached config to force a reload.
            if (!isset($this->data['version']) || ($this->data['version'] != $this->app['bolt_long_version'])) {
                // The logger and the flashbags aren't available yet, so we set a flag to notify the user later.
                $this->notify_update = true;
                return false;
            }

            // Trigger the config loaded event on the resource manager
            $this->app['resources']->initializeConfig($this->data);

            // Yup, all seems to be right.
            return true;
        }

        return false;
    }

    /**
     * Cache built configuration parameters.
     */
    protected function saveCache()
    {
        // Store the version number along with the config.
        $this->data['version'] = $this->app['bolt_long_version'];

        if ($this->get('general/caching/config')) {
            Lib::saveSerialize($this->app['resources']->getPath('cache/config_cache.php'), $this->data);

            return;
        }

        @unlink($this->app['resources']->getPath('cache/config_cache.php'));
    }

    /**
     * Check if cache timeout has occured.
     */
    protected function checkValidCache()
    {
        // Check the timestamp for the theme's config.yml
        $paths = $this->app['resources']->getPaths();
        $themeConfigFile = $paths['themepath'] . '/config.yml';
        // Note: we need to check if it exists, _and_ it's too old. Not _or_, hence the '0'
        $configTimestamp = file_exists($themeConfigFile) ? filemtime($themeConfigFile) : 0;

        if ($this->cachetimestamp <= $configTimestamp) {
            // Invalidate cache for next request.
            @unlink($paths['cache'] . '/config_cache.php');
        }
    }

    /**
     * Get a timestamp, corrected to the timezone.
     *
     * @return string Timestamp
     */
    public function getTimestamp($when)
    {
        $timezone = $this->get('general/timezone');
        $now = date_format(new \DateTime($when, new \DateTimeZone($timezone)), 'Y-m-d H:i:s');

        return $now;
    }

    /**
     * Get the current timestamp, corrected to the timezone.
     *
     * @return string Current timestamp
     */
    public function getCurrentTimestamp()
    {
        $timezone = $this->get('general/timezone');
        $now = date_format(new \DateTime($timezone), 'Y-m-d H:i:s');

        return $now;
    }

    /**
     * Use get('general/database') instead
     *
     * @deprecated Since 2.1, to be removed in 3.0.
     *
     * @return array
     */
    public function getDBOptions()
    {
        return $this->get('general/database');
    }

    /**
     * Use {@see Zone} instead with a {@see Request}.
     *
     * Going forward, decisions determined by current request
     * should be done in an app or route middleware.
     * Application should be setup agnostic to the current request.
     *
     * Route middlewares apply only to a certain route or group of routes.
     * See {@see \Bolt\Controller\Async\AsyncBase::before} for an example.
     *
     * App middlewares apply to all routes.
     * See classes in \Bolt\EventListener for examples of these.
     * These middlewares could also be filtered by checking for Zone inside of listener.
     *
     * @deprecated Since 2.3, to be removed in 3.0.
     *
     * @return string
     */
    public function getWhichEnd()
    {
        $zone = $this->determineZone();
        $this->app['end'] = $zone; // This is also deprecated
        return $zone;
    }

    private function determineZone()
    {
        if (PHP_SAPI === 'cli') {
            return 'cli';
        }
        /** @var \Symfony\Component\HttpFoundation\RequestStack $stack */
        $stack = $this->app['request_stack'];
        $request = $stack->getCurrentRequest() ?: Request::createFromGlobals();

        if ($zone = Zone::get($request)) {
            return $zone;
        }

        /** @var \Bolt\EventListener\ZoneGuesser $guesser */
        $guesser = $this->app['listener.zone_guesser'];
        return $guesser->setZone($request);
    }
}
