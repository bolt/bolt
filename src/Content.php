<?php

namespace Bolt;

use Bolt\Helpers\Html;
use Bolt\Helpers\Input;
use Bolt\Helpers\Str;
use Bolt\Library as Lib;
use Maid\Maid;
use Silex;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class Content implements \ArrayAccess
{
    public $id;
    public $values = array();
    public $taxonomy;
    public $relation;
    public $contenttype;
    public $user;
    public $sortorder;
    public $config;
    public $group;

    /** @var \Silex\Application */
    protected $app;

    /** @var integer The last time we weight a searchresult */
    private $lastWeight = 0;

    /** @var boolean Whether this is a "real" contenttype or an embedded ones */
    private $isRootType;


    /**
     * @param \Silex\Application $app
     * @param string             $contenttype
     * @param array              $values
     * @param boolean            $isRootType
     */
    public function __construct(Silex\Application $app, $contenttype = '', $values = array(), $isRootType = true)
    {
        $this->app = $app;
        $this->isRootType = $isRootType;

        if (!empty($contenttype)) {
            // Set the contenttype
            $this->setContenttype($contenttype);

            // If this contenttype has a taxonomy with 'grouping', initialize the group.
            if (isset($this->contenttype['taxonomy'])) {
                foreach ($this->contenttype['taxonomy'] as $taxonomytype) {
                    if ($this->app['config']->get('taxonomy/' . $taxonomytype . '/behaves_like') == 'grouping') {
                        $this->setGroup('', '', $taxonomytype);
                    }

                    // add support for taxonomy default value when options is set
                    $defaultValue = $this->app['config']->get('taxonomy/' . $taxonomytype . '/default');
                    $options = $this->app['config']->get('taxonomy/' . $taxonomytype . '/options');
                    if (isset($options) &&
                            isset($defaultValue) &&
                            array_search($defaultValue, array_keys($options)) !== false) {
                        $this->setTaxonomy($taxonomytype, $defaultValue);
                        $this->sortTaxonomy();
                    }
                }
            }
        }

        $this->user = $this->app['users']->getCurrentUser();

        if (!empty($values)) {
            $this->setValues($values);
        } else {
            // Ininitialize fields with empty values.
            if ((is_array($this->contenttype) && is_array($this->contenttype['fields']))) {
                foreach ($this->contenttype['fields'] as $key => $parameters) {
                    // Set the default values.
                    if (isset($parameters['default'])) {
                        $values[$key] = $parameters['default'];
                    } else {
                        $values[$key] = '';
                    }
                }
            }

            if (!empty($this->contenttype['singular_name'])) {
                $contenttypename = $this->contenttype['singular_name'];
            } else {
                $contenttypename = "unknown";
            }
            // Specify an '(undefined contenttype)'.
            $values['name'] = "(undefined $contenttypename)";
            $values['title'] = "(undefined $contenttypename)";

            $this->setValues($values);
        }
    }

    /**
     * Gets a list of the base columns that are hard-coded into all content
     * types (rather than configured through contenttypes.yml).
     */
    public static function getBaseColumns()
    {
        return array(
            'id',
            'slug',
            'datecreated',
            'datechanged',
            'datepublish',
            'datedepublish',
            'ownerid',
            'status',
            'templatefields'
        );
    }

    /**
     * Return a content objects values.
     *
     * @param boolean $json     Set to TRUE to return JSON encoded values for arrays
     * @param boolean $stripped Set to true to strip all of the base fields
     *
     * @return array
     */
    public function getValues($json = false, $stripped = false)
    {
        // Prevent 'slug may not be NULL'
        if (!isset($this->values['slug'])) {
            $this->values['slug'] = '';
        }

        // Return raw values
        if ($json === false) {
            return $this->values;
        }

        $contenttype = $this->contenttype;
        if (!$stripped) {
            $newvalue = $this->values;
        } else {
            $newvalue = array();
        }

        // add the fields for this contenttype,
        if (is_array($contenttype)) {
            foreach ($contenttype['fields'] as $field => $property) {
                switch ($property['type']) {

                    // Set the slug, while we're at it
                    case 'slug':
                        if (!empty($property['uses']) && empty($this->values[$field])) {
                            $uses = '';
                            foreach ($property['uses'] as $usesField) {
                                $uses .= $this->values[$usesField] . ' ';
                            }
                            $newvalue[$field] = $this->app['slugify']->slugify($uses);
                        } elseif (!empty($this->values[$field])) {
                            $newvalue[$field] = $this->app['slugify']->slugify($this->values[$field]);
                        } elseif (empty($this->values[$field]) && $this->values['id']) {
                            $newvalue[$field] = $this->values['id'];
                        }
                        break;

                    case 'video':
                        foreach (array('html', 'responsive') as $subkey) {
                            if (!empty($this->values[$field][$subkey])) {
                                $this->values[$field][$subkey] = (string) $this->values[$field][$subkey];
                            }
                        }
                        if (!empty($this->values[$field]['url'])) {
                            $newvalue[$field] = json_encode($this->values[$field]);
                        } else {
                            $newvalue[$field] = '';
                        }
                        break;

                    case 'geolocation':
                        if (!empty($this->values[$field]['latitude']) && !empty($this->values[$field]['longitude'])) {
                            $newvalue[$field] = json_encode($this->values[$field]);
                        } else {
                            $newvalue[$field] = '';
                        }
                        break;

                    case 'image':
                        if (!empty($this->values[$field]['file'])) {
                            $newvalue[$field] = json_encode($this->values[$field]);
                        } else {
                            $newvalue[$field] = '';
                        }
                        break;

                    case 'imagelist':
                    case 'filelist':
                        if (is_array($this->values[$field])) {
                            $newvalue[$field] = json_encode($this->values[$field]);
                        } elseif (!empty($this->values[$field]) && strlen($this->values[$field]) < 3) {
                            // Don't store '[]'
                            $newvalue[$field] = '';
                        }
                        break;

                    case 'integer':
                        $newvalue[$field] = round($this->values[$field]);
                        break;

                    case 'select':
                        if (is_array($this->values[$field])) {
                            $newvalue[$field] = json_encode($this->values[$field]);
                        }
                        break;

                    case 'html':
                        // Remove &nbsp; characters from CKEditor, unless configured to leave them in.
                        if (!$this->app['config']->get('general/wysiwyg/ck/allowNbsp')) {
                            $newvalue[$field] = str_replace('&nbsp;', ' ', $this->values[$field]);
                        }
                        break;
                    default:
                        $newvalue[$field] = $this->values[$field];
                        break;
                }
            }
        }

        if (!$stripped) {
            if (!empty($this['templatefields'])) {
                $newvalue['templatefields'] = json_encode($this->values['templatefields']->getValues(true, true));
            } else {
                $newvalue['templatefields'] = '';
            }
        }

        return $newvalue;
    }

    /**
     * Set a Contenttype record's values.
     *
     * @param array $values
     */
    public function setValues(array $values)
    {
        // Since Bolt 1.4, we use 'ownerid' instead of 'username' in the DB tables. If we get an array that has an
        // empty 'ownerid', attempt to set it from the 'username'. In $this->setValue the user will be set, regardless
        // of ownerid is an 'id' or a 'username'.
        if (empty($values['ownerid']) && !empty($values['username'])) {
            $values['ownerid'] = $values['username'];
            unset($values['username']);
        }

        foreach ($values as $key => $value) {
            if ($key !== 'templatefields') {
                $this->setValue($key, $value);
            }
        }

        // If default status is set in contentttype.
        if (empty($this->values['status']) && isset($this->contenttype['default_status'])) {
            $this->values['status'] = $this->contenttype['default_status'];
        }

        $serializedFieldTypes = array(
            'geolocation',
            'imagelist',
            'image',
            'file',
            'filelist',
            'video',
            'select',
            'templateselect',
            'checkbox'
        );
        // Check if the values need to be unserialized, and pre-processed.
        foreach ($this->values as $key => $value) {
            if ((in_array($this->fieldtype($key), $serializedFieldTypes)) || ($key == 'templatefields')) {
                if (!empty($value) && is_string($value) && (substr($value, 0, 2) == "a:" || $value[0] === '[' || $value[0] === '{')) {
                    try {
                        $unserdata = Lib::smartUnserialize($value);
                    } catch (\Exception $e) {
                        $unserdata = false;
                    }

                    if ($unserdata !== false) {
                        $this->values[$key] = $unserdata;
                    }
                }
            }

            if ($this->fieldtype($key) == "video" && is_array($this->values[$key]) && !empty($this->values[$key]['url'])) {
                $video = $this->values[$key];

                // update the HTML, according to given width and height
                if (!empty($video['width']) && !empty($video['height'])) {
                    $video['html'] = preg_replace("/width=(['\"])([0-9]+)(['\"])/i", 'width=${1}' . $video['width'] . '${3}', $video['html']);
                    $video['html'] = preg_replace("/height=(['\"])([0-9]+)(['\"])/i", 'height=${1}' . $video['height'] . '${3}', $video['html']);
                }

                $responsiveclass = "responsive-video";

                // See if it's widescreen or not.
                if (!empty($video['height']) && (($video['width'] / $video['height']) > 1.76)) {
                    $responsiveclass .= " widescreen";
                }

                if (strpos($video['url'], "vimeo") !== false) {
                    $responsiveclass .= " vimeo";
                }

                $video['responsive'] = sprintf('<div class="%s">%s</div>', $responsiveclass, $video['html']);

                // Mark them up as Twig_Markup.
                $video['html'] = new \Twig_Markup($video['html'], 'UTF-8');
                $video['responsive'] = new \Twig_Markup($video['responsive'], 'UTF-8');

                $this->values[$key] = $video;
            }

            if ($this->fieldtype($key) == "date" || $this->fieldtype($key) == "datetime") {
                if ($this->values[$key] === "") {
                    $this->values[$key] = null;
                }
            }
        }

        // Template fields need to be done last
        // As the template has to have been selected
        if ($this->isRootType) {
            if (empty($values['templatefields'])) {
                $this->setValue('templatefields', array());
            } else {
                $this->setValue('templatefields', $values['templatefields']);
            }
        }
    }

    /**
     * Set a Contenttype record's individual value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setValue($key, $value)
    {
        // Don't set templateFields if not a real contenttype
        if (($key === 'templatefields') && (!$this->isRootType)) {
            return;
        }

        // Check if the value need to be unserialized.
        if (is_string($value) && substr($value, 0, 2) === "a:") {
            try {
                $unserdata = Lib::smartUnserialize($value);
            } catch (\Exception $e) {
                $unserdata = false;
            }

            if ($unserdata !== false) {
                $value = $unserdata;
            }
        }

        if ($key == 'id') {
            $this->id = $value;
        }

        // Set the user in the object.
        if ($key === 'ownerid' && !empty($value)) {
            $this->user = $this->app['users']->getUser($value);
        }

        // Only set values if they have are actually a field.
        $allowedcolumns = self::getBaseColumns();
        $allowedcolumns[] = 'taxonomy';
        if (!isset($this->contenttype['fields'][$key]) && !in_array($key, $allowedcolumns)) {
            return;
        }

        if (in_array($key, array('datecreated', 'datechanged', 'datepublish', 'datedepublish'))) {
            if (!preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $value)) {
                // @todo Try better date-parsing, instead of just setting it to
                // 'now' (or 'the past' for datedepublish)
                if ($key == 'datedepublish') {
                    $value = null;
                } else {
                    $value = date('Y-m-d H:i:s');
                }
            }
        }

        if ($key === 'templatefields') {
            if ((is_string($value)) || (is_array($value))) {
                if (is_string($value)) {
                    try {
                        $unserdata = Lib::smartUnserialize($value);
                    } catch (\Exception $e) {
                        $unserdata = false;
                    }
                } else {
                    $unserdata = $value;
                }

                if (is_array($unserdata)) {
                    $templateContent = new Content($this->app, $this->getTemplateFieldsContentType(), array(), false);
                    $value = $templateContent;
                    $this->populateTemplateFieldsContenttype($value);
                    $templateContent->setValues($unserdata);
                } else {
                    $value = null;
                }
            }
        }

        if (!isset($this->values['datechanged']) ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datechanged'])) {
            $this->values['datechanged'] = date("Y-m-d H:i:s");
        }

        $this->values[$key] = $value;
    }

    /**
     * Set a Contenttype record values from a HTTP POST.
     *
     * @param array  $values
     * @param string $contenttype
     *
     * @throws \Exception
     *
     * @return void
     */
    public function setFromPost($values, $contenttype)
    {
        $values = Input::cleanPostedData($values);

        if (!$this->id) {
            // this is a new record: current user becomes the owner.
            $user = $this->app['users']->getCurrentUser();
            $this['ownerid'] = $user['id'];
        }

        // If the owner is set explicitly, check if the current user is allowed
        // to do this.
        if (isset($values['ownerid'])) {
            if ($this['ownerid'] != $values['ownerid']) {
                if (!$this->app['users']->isAllowed("contenttype:{$contenttype['slug']}:change-ownership:{$this->id}")) {
                    throw new \Exception("Changing ownership is not allowed.");
                }
                $this['ownerid'] = intval($values['ownerid']);
            }
        }

        // Make sure we have a proper status.
        if (!in_array($values['status'], array('published', 'timed', 'held', 'draft'))) {
            if ($this['status']) {
                $values['status'] = $this['status'];
            } else {
                $values['status'] = "draft";
            }
        }

        // Make sure we only get the current taxonomies, not those that were fetched from the DB.
        $this->taxonomy = array();

        if (!empty($values['taxonomy'])) {
            foreach ($values['taxonomy'] as $taxonomytype => $value) {
                if (!is_array($value)) {
                    $value = explode(",", $value);
                }

                if (isset($values['taxonomy-order'][$taxonomytype])) {
                    foreach ($value as $k => $v) {
                        $value[$k] = $v . "#" . $values['taxonomy-order'][$taxonomytype];
                    }
                }

                $this->taxonomy[$taxonomytype] = $value;
            }
            unset($values['taxonomy']);
            unset($values['taxonomy-order']);
        }

        // Get the relations from the POST-ed values.
        // @todo use $this->setRelation() for this
        if (!empty($values['relation'])) {
            $this->relation = $values['relation'];
            unset($values['relation']);
        } else {
            $this->relation = array();
        }

        // @todo check for allowed file types.

        // Handle file-uploads.
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $file) {
                if (empty($file['name'][0])) {
                    continue; // Skip 'empty' uploads.
                }

                $paths = $this->app['resources']->getPaths();

                $filename = sprintf(
                    '%sfiles/%s/%s',
                    $paths['rootpath'],
                    date('Y-m'),
                    Str::makeSafe($file['name'][0], false, '[]{}()')
                );
                $basename = sprintf('/%s/%s', date('Y-m'), Str::makeSafe($file['name'][0], false, "[]{}()"));

                if ($file['error'][0] != UPLOAD_ERR_OK) {
                    $message = 'Error occured during upload: ' . $file['error'][0] . " - $filename";
                    $this->app['logger.system']->error($message, array('event' => 'upload'));
                    continue;
                }

                if (substr($key, 0, 11) != 'fileupload-') {
                    $message = "Skipped an upload that wasn't for content: $filename";
                    $this->app['logger.system']->error($message, array('event' => 'upload'));
                    continue;
                }

                $fieldname  = substr($key, 11);
                $fileSystem = new Filesystem();

                // Make sure the folder exists.
                $fileSystem->mkdir(dirname($filename));

                // Check if we don't have doubles.
                if (is_file($filename)) {
                    while (is_file($filename)) {
                        $filename = $this->upcountName($filename);
                        $basename = $this->upcountName($basename);
                    }
                }

                if (is_writable(dirname($filename))) {
                    // Yes, we can create the file!
                    move_uploaded_file($file['tmp_name'][0], $filename);
                    $values[$fieldname] = $basename;
                    $this->app['logger.system']->info("Upload: uploaded file '$basename'.", array('event' => 'upload'));
                } else {
                    $this->app['logger.system']->error("Upload: couldn't write upload '$basename'.", array('event' => 'upload'));
                }
            }
        }

        $this->setValues($values);
    }

    /**
     * Get the template associate with a Contenttype field.
     *
     * @return string
     */
    protected function getTemplateFieldsContentType()
    {
        if (!is_array($this->contenttype)) {
            return '';
        }

        if ($templateFieldsConfig = $this->app['config']->get('theme/templatefields')) {
            $template = $this->app['templatechooser']->record($this);
            if (array_key_exists($template, $templateFieldsConfig)) {
                return $templateFieldsConfig[$template];
            }
        }

        return '';
    }

    /**
     * Check if a Contenttype field has a template set.
     *
     * @return boolean
     */
    public function hasTemplateFields()
    {
        if (!is_array($this->contenttype)) {
            return false;
        }

        if ((!$this->contenttype['viewless'])
            && (!empty($this['templatefields']))
            && ($templateFieldsConfig = $this->app['config']->get('theme/templatefields'))) {
                $template = $this->app['templatechooser']->record($this);
                if (array_key_exists($template, $templateFieldsConfig)) {
                    return true;
                }
        }

        return false;
    }

    /**
     * "upcount" a filename: Add (1), (2), etc. for filenames that already exist.
     * Taken from jQuery file upload.
     *
     * @param string $name
     *
     * @return string
     */
    protected function upcountName($name)
    {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            array($this, 'upcountNameCallback'),
            $name,
            1
        );
    }

    /**
     * "upcount" callback helper function
     * Taken from jQuery file upload.
     *
     * @see upcountName()
     *
     * @param array $matches
     *
     * @return string
     */
    protected function upcountNameCallback($matches)
    {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';

        return ' (' . $index . ')' . $ext;
    }

    /**
     * Set the Contenttype for the record.
     *
     * @param array|string $contenttype
     */
    public function setContenttype($contenttype)
    {
        if (is_string($contenttype)) {
            $contenttype = $this->app['storage']->getContenttype($contenttype);
        }

        $this->contenttype = $contenttype;
    }

    /**
     * Set a taxonomy for the current object.
     *
     * @param string       $taxonomytype
     * @param string|array $slug
     * @param string       $name
     * @param integer      $sortorder
     *
     * @return boolean
     */
    public function setTaxonomy($taxonomytype, $slug, $name = '', $sortorder = 0)
    {
        // If $value is an array, recurse over it, adding each one by itself.
        if (is_array($slug)) {
            foreach ($slug as $single) {
                $this->setTaxonomy($taxonomytype, $single, '', $sortorder);
            }

            return true;
        }

        // Only add a taxonomy, if the taxonomytype is actually set in the contenttype
        if (!isset($this->contenttype['taxonomy']) || !in_array($taxonomytype, $this->contenttype['taxonomy'])) {
            return false;
        }

        // Make sure sortorder is set correctly;
        if ($this->app['config']->get('taxonomy/' . $taxonomytype . '/has_sortorder') === false) {
            $sortorder = false;
        } else {
            $sortorder = (int) $sortorder;
            // Note: by doing this we assume a contenttype can have only one taxonomy which has has_sortorder: true.
            $this->sortorder = $sortorder;
        }

        // Make the 'key' of the array an absolute link to the taxonomy.
        try {
            $link = $this->app['url_generator']->generate(
                'taxonomylink',
                array(
                    'taxonomytype' => $taxonomytype,
                    'slug'         => $slug,
                )
            );
        } catch (RouteNotFoundException $e) {
            // Fallback to unique key (yes, also a broken link)
            $link = $taxonomytype . '/' . $slug;
        }

        // Set the 'name', for displaying the pretty name, if there is any.
        if ($this->app['config']->get('taxonomy/' . $taxonomytype . '/options/' . $slug)) {
            $name = $this->app['config']->get('taxonomy/' . $taxonomytype . '/options/' . $slug);
        } elseif (empty($name)) {
            $name = $slug;
        }

        $this->taxonomy[$taxonomytype][$link] = $name;

        // If it's a "grouping" type, set $this->group.
        if ($this->app['config']->get('taxonomy/' . $taxonomytype . '/behaves_like') == 'grouping') {
            $this->setGroup($slug, $name, $taxonomytype, $sortorder);
        }

        return true;
    }

    /**
     * Sort the taxonomy of the current object, based on the order given in taxonomy.yml.
     *
     * @return void
     */
    public function sortTaxonomy()
    {
        if (empty($this->taxonomy)) {
            // Nothing to do here.
            return;
        }

        foreach (array_keys($this->taxonomy) as $type) {
            $taxonomytype = $this->app['config']->get('taxonomy/' . $type);
            // Don't order tags.
            if ($taxonomytype['behaves_like'] == "tags") {
                continue;
            }

            // Order them by the order in the contenttype.
            $new = array();
            foreach ($this->app['config']->get('taxonomy/' . $type . '/options') as $key => $value) {
                if ($foundkey = array_search($key, $this->taxonomy[$type])) {
                    $new[$foundkey] = $value;
                } elseif ($foundkey = array_search($value, $this->taxonomy[$type])) {
                    $new[$foundkey] = $value;
                }
            }
            $this->taxonomy[$type] = $new;
        }
    }

    /**
     * Add a relation.
     *
     * @param string|array $contenttype
     * @param integer      $id
     *
     * @return void
     */
    public function setRelation($contenttype, $id)
    {
        if (!empty($this->relation[$contenttype])) {
            $ids = $this->relation[$contenttype];
        } else {
            $ids = array();
        }

        $ids[] = $id;
        sort($ids);

        $this->relation[$contenttype] = array_unique($ids);
    }

    /**
     * Get a specific taxonomy's type.
     *
     * @param string $type
     *
     * @return string|boolean
     */
    public function getTaxonomyType($type)
    {
        if (isset($this->config['taxonomy'][$type])) {
            return $this->config['taxonomy'][$type];
        } else {
            return false;
        }
    }

    /**
     * Set the 'group', 'groupname' and 'sortorder' properties of the current object.
     *
     * @param string  $group
     * @param string  $name
     * @param string  $taxonomytype
     * @param integer $sortorder
     *
     * @return void
     */
    public function setGroup($group, $name, $taxonomytype, $sortorder = 0)
    {
        $this->group = array(
            'slug' => $group,
            'name' => $name
        );

        $hasSortOrder = $this->app['config']->get('taxonomy/' . $taxonomytype . '/has_sortorder');

        // Only set the sortorder, if the contenttype has a taxonomy that has sortorder
        if ($hasSortOrder !== false) {
            $this->group['order'] = (int) $sortorder;
        }

        // Set the 'index', so we can sort on it later.
        $index = array_search($group, array_keys($this->app['config']->get('taxonomy/' . $taxonomytype . '/options')));

        if ($index !== false) {
            $this->group['index'] = $index;
        } else {
            $this->group['index'] = 2147483647; // Max for 32 bit int.
        }
    }

    /**
     * Get the decoded version of a value of the current object.
     *
     * @param string $name name of the value to get
     *
     * @return mixed The decoded value or null when no value available
     */
    public function getDecodedValue($name)
    {
        $value = null;

        if (isset($this->values[$name])) {
            $fieldtype = $this->fieldtype($name);
            $fieldinfo = $this->fieldinfo($name);
            $allowtwig = !empty($fieldinfo['allowtwig']);

            switch ($fieldtype) {
                case 'markdown':

                    $value = $this->preParse($this->values[$name], $allowtwig);

                    // Parse the field as Markdown, return HTML
                    $value = $this->app['markdown']->text($value);

                    $config = $this->app['config']->get('general/htmlcleaner');
                    $allowed_tags = !empty($config['allowed_tags']) ? $config['allowed_tags'] :
                        array('div', 'p', 'br', 'hr', 's', 'u', 'strong', 'em', 'i', 'b', 'li', 'ul', 'ol', 'blockquote', 'pre', 'code', 'tt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'dd', 'dl', 'dt', 'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr', 'a', 'img');
                    $allowed_attributes = !empty($config['allowed_attributes']) ? $config['allowed_attributes'] :
                        array('id', 'class', 'name', 'value', 'href', 'src');

                    // Sanitize/clean the HTML.
                    $maid = new Maid(
                        array(
                            'output-format'   => 'html',
                            'allowed-tags'    => $allowed_tags,
                            'allowed-attribs' => $allowed_attributes
                        )
                    );
                    $value = $maid->clean($value);
                    $value = new \Twig_Markup($value, 'UTF-8');
                    break;

                case 'html':
                case 'text':
                case 'textarea':

                    $value = $this->preParse($this->values[$name], $allowtwig);
                    $value = new \Twig_Markup($value, 'UTF-8');

                    break;

                case 'imagelist':
                case 'filelist':
                    if (is_string($this->values[$name])) {
                        // Parse the field as JSON, return the array
                        $value = json_decode($this->values[$name]);
                    } else {
                        // Already an array, do nothing.
                        $value = $this->values[$name];
                    }
                    break;

                case 'image':
                    if (is_array($this->values[$name]) && isset($this->values[$name]['file'])) {
                        $value = $this->values[$name]['file'];
                    } else {
                        $value = $this->values[$name];
                    }
                    break;

                default:
                    $value = $this->values[$name];
                    break;
            }
        }

        return $value;
    }

    /**
     * If passed snippet contains Twig tags, parse the string as Twig, and return the results.
     *
     * @param string  $snippet
     * @param boolean $allowtwig
     *
     * @return string
     */
    public function preParse($snippet, $allowtwig)
    {
        // Quickly verify that we actually need to parse the snippet!
        if ($allowtwig && preg_match('/[{][{%#]/', $snippet)) {
            $snippet = html_entity_decode($snippet, ENT_QUOTES, 'UTF-8');

            try {
                return $this->app['safe_render']->render($snippet, $this->getTemplateContext());
            } catch(\Exception $e) {
                return $e->getMessage();
            }
        }

        return $snippet;
    }

    public function getTemplateContext()
    {
        return array(
            'record'                            => $this,
            $this->contenttype['singular_slug'] => $this // Make sure we can also access it as {{ page.title }} for pages, etc.
        );
    }

    /**
     * Magic __call function, used for when templates use {{ content.title }},
     * so we can map it to $this->values['title'].
     *
     * @param string $name      Method name originally called
     * @param array  $arguments Arguments to the call
     *
     * @return mixed return value of the call
     */
    public function __call($name, $arguments)
    {
        $value = $this->getDecodedValue($name);

        if (!is_null($value)) {
            return $value;
        }

        return false;
    }

    /**
     * Pseudo-magic function, used for when templates use {{ content.get(title) }},
     * so we can map it to $this->values['title'].
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        // For fields that are stored as arrays, like 'video'
        if (strpos($name, ".") > 0) {
            list($name, $attr) = explode(".", $name);
            if (!empty($attr) && isset($this->values[$name][$attr])) {
                return $this->values[$name][$attr];
            }
        }

        if (isset($this->values[$name])) {
            return $this->values[$name];
        } else {
            return false;
        }
    }

    /**
     * Get the title, name, caption or subject.
     *
     * @return string
     */
    public function getTitle()
    {
        $titleParts = array();

        foreach ($this->getTitleColumnName() as $fieldName) {
            $titleParts[] = strip_tags($this->values[$fieldName]);
        }

        if (!empty($titleParts)) {
            $title = implode(' ', $titleParts);
        } else {
            // nope, no title was found.
            $title = '(untitled)';
        }

        return $title;
    }

    /**
     * Get the columnname of the title, name, caption or subject.
     *
     * @return string|null
     */
    public function getTitleColumnName()
    {

        // If we specified a specific fieldname or array of fieldnames as 'title'.
        if (!empty($this->contenttype['title_format'])) {
            if (!is_array($this->contenttype['title_format'])) {
                $this->contenttype['title_format'] = array($this->contenttype['title_format']);
            }

            return $this->contenttype['title_format'];
        }

        // Sets the names of some 'common' names for the 'title' column.
        $names = array('title', 'name', 'caption', 'subject');

        // Some localised options as well
        $names = array_merge($names, array('titel', 'naam', 'onderwerp')); // NL
        $names = array_merge($names, array('nom', 'sujet')); // FR
        $names = array_merge($names, array('nombre', 'sujeto')); // ES

        foreach ($names as $name) {
            if (isset($this->values[$name])) {
                return array($name);
            }
        }

        // Otherwise, grab the first field of type 'text', and assume that's the title.
        if (!empty($this->contenttype['fields'])) {
            foreach ($this->contenttype['fields'] as $key => $field) {
                if ($field['type'] == 'text') {
                    return array($key);
                }
            }
        }

        // Nope, no title was found.
        return array();
    }

    /**
     * Get the first image in the content.
     *
     * @return string
     */
    public function getImage()
    {
        // No fields, no image.
        if (empty($this->contenttype['fields'])) {
            return '';
        }

        // Grab the first field of type 'image', and return that.
        foreach ($this->contenttype['fields'] as $key => $field) {
            if ($field['type'] == 'image') {
                // After v1.5.1 we store image data as an array
                if (is_array($this->values[$key])) {
                    return $this->values[$key]['file'];
                }

                return $this->values[$key];
            }
        }

        // otherwise, no image.
        return '';
    }

    /**
     * Get the reference to this record, to uniquely identify this specific record.
     *
     * @return string
     */
    public function getReference()
    {
        $reference = $this->contenttype['singular_slug'] . '/' . $this->values['slug'];

        return $reference;
    }

    /**
     * Creates a link to EDIT this record, if the user is logged in.
     *
     * @return string
     */
    public function editlink()
    {
        $perm = "contenttype:" . $this->contenttype['slug'] . ":edit:" . $this->id;

        if ($this->app['users']->isAllowed($perm)) {
            return Lib::path('editcontent', array('contenttypeslug' => $this->contenttype['slug'], 'id' => $this->id ));
        } else {
            return false;
        }
    }

    /**
     * Creates a URL for the content record.
     *
     * @return string
     */
    public function link()
    {
        if (empty($this->id)) {
            return null;
        }

        list($binding, $route) = $this->getRoute();

        if (!$route) {
            return null;
        }

        $slug = $this->values['slug'];
        if (empty($slug)) {
            $slug = $this->id;
        }
        $link = $this->app['url_generator']->generate(
            $binding,
            array_filter(
                array_merge(
                    $route['defaults'] ?: array(),
                    $this->getRouteRequirementParams($route),
                    array(
                        'contenttypeslug' => $this->contenttype['singular_slug'],
                        'id'              => $this->id,
                        'slug'            => $slug
                    )
                )
            )
        );

        // Strip the query string generated by supplementary parameters.
        // since our $params contained all possible arguments and the ->generate()
        // added all $params which it didn't need in the query-string we can
        // safely strip the query-string.
        // NB. this does mean we don't support routes with query strings
        return preg_replace('/^([^?]*).*$/', '\\1', $link);
    }

    /**
     * Checks if the current record is set as the homepage.
     *
     * @return boolean
     */
    public function isHome()
    {
        $homepage = $this->app['config']->get('general/homepage');

        return (($this->contenttype['singular_slug'].'/'.$this->get('id') == $homepage) ||
           ($this->contenttype['singular_slug'].'/'.$this->get('slug') == $homepage));
    }

    /**
     * Build a Contenttype's route parameters
     *
     * @param array $route
     *
     * @return array
     */
    protected function getRouteRequirementParams(array $route)
    {
        $params = array();
        if (isset($route['requirements'])) {
            foreach ($route['requirements'] as $fieldName => $requirement) {
                if ('\d{4}-\d{2}-\d{2}' === $requirement) {
                    // Special case, if we need to have a date
                    $params[$fieldName] = substr($this->values[$fieldName], 0, 10);
                } elseif (isset($this->taxonomy[$fieldName])) {
                    // Turn something like '/chapters/meta' to 'meta'. Note: we use
                    // two temp vars here, to prevent "Only variables should be passed
                    // by reference"-notices.
                    $tempKeys = array_keys($this->taxonomy[$fieldName]);
                    $tempValues = explode('/', array_shift($tempKeys));
                    $params[$fieldName] = array_pop($tempValues);
                } elseif (isset($this->values[$fieldName])) {
                    $params[$fieldName] = $this->values[$fieldName];
                } else {
                    // unkown
                    $params[$fieldName] = null;
                }
            }
        }

        return $params;
    }

    /**
     * Retrieves the first route applicable to the content as a two-element array consisting of the binding and the
     * route array. Returns `null` if there is no applicable route.
     *
     * @return array|null
     */
    protected function getRoute()
    {
        $allroutes = $this->app['config']->get('routing');

        // First, try to find a custom route that's applicable
        foreach ($allroutes as $binding => $route) {
            if ($this->isApplicableRoute($route)) {
                return array($binding, $route);
            }
        }

        // Just return the 'generic' contentlink route.
        if (!empty($allroutes['contentlink'])) {
            return array('contentlink', $allroutes['contentlink']);
        }

        return null;
    }

    /**
     * Check if a route is applicable to this record.
     *
     * @param array $route
     *
     * @return boolean
     */
    protected function isApplicableRoute(array $route)
    {
        return (isset($route['contenttype']) && $route['contenttype'] === $this->contenttype['singular_slug']) ||
        (isset($route['contenttype']) && $route['contenttype'] === $this->contenttype['slug']) ||
        (isset($route['recordslug']) && $route['recordslug'] === $this->getReference());
    }

    /**
     * Get the previous record. In this case 'previous' is defined as 'latest one published before
     * this one' by default. You can pass a parameter like 'id' or '-title' to use that as
     * the column to sort on.
     *
     * @param string $field
     * @param array  $where
     *
     * @return \Bolt\Content
     */
    public function previous($field = 'datepublish', $where = array())
    {
        list($field, $asc) = $this->app['storage']->getSortOrder($field);

        $operator = $asc ? '<' : '>';
        $order = $asc ? ' DESC' : ' ASC';

        $params = array(
            $field         => $operator . $this->values[$field],
            'limit'        => 1,
            'order'        => $field . $order,
            'returnsingle' => true,
            'hydrate'      => false
        );

        $pager = array();
        $previous = $this->app['storage']->getContent($this->contenttype['singular_slug'], $params, $pager, $where);

        return $previous;
    }

    /**
     * Get the next record. In this case 'next' is defined as 'first one published after
     * this one' by default. You can pass a parameter like 'id' or '-title' to use that as
     * the column to sort on.
     *
     * @param string $field
     * @param array  $where
     *
     * @return \Bolt\Content
     */
    public function next($field = 'datepublish', $where = array())
    {
        list($field, $asc) = $this->app['storage']->getSortOrder($field);

        $operator = $asc ? '>' : '<';
        $order = $asc ? ' ASC' : ' DESC';

        $params = array(
            $field         => $operator . $this->values[$field],
            'limit'        => 1,
            'order'        => $field . $order,
            'returnsingle' => true,
            'hydrate'      => false
        );

        $pager = array();
        $next = $this->app['storage']->getContent($this->contenttype['singular_slug'], $params, $pager, $where);

        return $next;
    }

    /**
     * Gets one or more related records.
     *
     * @param string $filtercontenttype Contenttype to filter returned results on
     * @param array  $options           A set of 'WHERE' options to apply to the filter
     *
     * Backward compatability note:
     * The $options parameter used to be $filterid, an integer.
     *
     * @return \Bolt\Content[]
     */
    public function related($filtercontenttype = null, $options = array())
    {
        if (empty($this->relation)) {
            return false; // nothing to do here.
        }

        // Backwards compatibility: If '$options' is a string, assume we passed an id
        if (!is_array($options)) {
            $options = array(
                'id' => $options
            );
        }

        $records = array();

        foreach ($this->relation as $contenttype => $ids) {
            if (!empty($filtercontenttype) && ($contenttype != $filtercontenttype)) {
                continue; // Skip other contenttypes, if we requested a specific type.
            }

            $params = array('hydrate' => true);
            $where = array('id' => implode(' || ', $ids));
            $dummy = false;

            // If there were other options add them to the 'where'. We potentially overwrite the 'id' here.
            if (!empty($options)) {
                foreach ($options as $option => $value) {
                    $where[$option] = $value;
                }
            }

            $tempResult = $this->app['storage']->getContent($contenttype, $params, $dummy, $where);

            if (empty($tempResult)) {
                continue; // Go ahead if content not found.
            }

            // Variable $temp_result can be an array of object.
            if (is_array($tempResult)) {
                $records = array_merge($records, $tempResult);
            } else {
                $records[] = $tempResult;
            }
        }

        return $records;
    }

    /**
     * Get field information for the given field.
     *
     * @param string $key
     *
     * @return array An associative array containing at least the key 'type',
     *               and, depending on the type, other keys.
     */
    public function fieldinfo($key)
    {
        if (isset($this->contenttype['fields'][$key])) {
            return $this->contenttype['fields'][$key];
        } else {
            return array('type' => '');
        }
    }

    /**
     * Get the fieldtype for a given fieldname.
     *
     * @param string $key
     *
     * @return string
     */
    public function fieldtype($key)
    {
        $field = $this->fieldinfo($key);

        return $field['type'];
    }

    /**
     * Create an excerpt for the content.
     *
     * @param integer $length
     * @param boolean $includetitle
     *
     * @return \Twig_Markup
     */
    public function excerpt($length = 200, $includetitle = false)
    {
        if ($includetitle) {
            $title = Html::trimText(strip_tags($this->getTitle()), $length);
            $length = $length - strlen($title);
        }

        if ($length > 0) {
            $excerptParts = array();

            if (!empty($this->contenttype['fields'])) {
                foreach ($this->contenttype['fields'] as $key => $field) {
                    // Skip empty fields, and fields used as 'title'.
                    if (!isset($this->values[$key]) || in_array($key, $this->getTitleColumnName())) {
                        continue;
                    }

                    // add 'text', 'html' and 'textarea' fields.
                    if (in_array($field['type'], array('text', 'html', 'textarea'))) {
                        $excerptParts[] = $this->values[$key];
                    }
                    // add 'markdown' field
                    if ($field['type'] === 'markdown') {
                        $excerptParts[] = $this->app['markdown']->text($this->values[$key]);
                    }
                }
            }

            $excerpt = implode(' ', $excerptParts);
            $excerpt = Html::trimText(strip_tags($excerpt), $length);
        } else {
            $excerpt = '';
        }

        if (!empty($title)) {
            $excerpt = '<b>' . $title . '</b> ' . $excerpt;
        }

        return new \Twig_Markup($excerpt, 'UTF-8');
    }

    /**
     * Creates RSS safe content. Wraps it in CDATA tags, strips style and
     * scripts out. Can optionally also return a (cleaned) excerpt.
     *
     * Note: To conform to the template style, this method name is not following PSR-1:
     *    {{ record.rss_safe() }}
     *
     * @param string  $fields        Comma separated list of fields to clean up
     * @param integer $excerptLength Number of chars of the excerpt
     *
     * @return string RSS safe string
     */
    public function /*@codingStandardsIgnoreStart*/rss_safe/*@codingStandardsIgnoreEnd*/($fields = '', $excerptLength = 0)
    {
        // Make sure we have an array of fields. Even if it's only one.
        if (!is_array($fields)) {
            $fields = explode(',', $fields);
        }
        $fields = array_map('trim', $fields);

        $result = '';

        foreach ($fields as $field) {
            if (array_key_exists($field, $this->values)) {

                // Completely remove style and script blocks
                $maid = new Maid(
                    array(
                        'output-format'   => 'html',
                        'allowed-tags'    => array('a', 'b', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'p', 'strong', 'em', 'i', 'u', 'strike', 'ul', 'ol', 'li', 'img'),
                        'allowed-attribs' => array('id', 'class', 'name', 'value', 'href', 'src')
                    )
                );

                $result .= $maid->clean($this->values[$field]);
            }
        }

        if ($excerptLength > 0) {
            $result .= Html::trimText($result, $excerptLength);
        }

        return '<![CDATA[ ' . $result . ' ]]>';
    }

    /**
     * Weight a text part relative to some other part.
     *
     * @param string  $subject  The subject to search in.
     * @param string  $complete The complete search term (lowercased).
     * @param array   $words    All the individual search terms (lowercased).
     * @param integer $max      Maximum number of points to return.
     *
     * @return integer The weight
     */
    private function weighQueryText($subject, $complete, $words, $max)
    {
        $lowSubject = mb_strtolower(trim($subject));

        if ($lowSubject == $complete) {
            // a complete match is 100% of the maximum
            return round((100 / 100) * $max);
        }
        if (strstr($lowSubject, $complete)) {
            // when the whole query is found somewhere is 70% of the maximum
            return round((70 / 100) * $max);
        }

        $wordMatches = 0;
        $cntWords = count($words);
        for ($i = 0; $i < $cntWords; $i++) {
            if (strstr($lowSubject, $words[$i])) {
                $wordMatches++;
            }
        }
        if ($wordMatches > 0) {
            // marcel: word matches are maximum of 50% of the maximum per word
            // xiao: made (100/100) instead of (50/100).
            return round(($wordMatches / $cntWords) * (100 / 100) * $max);
        }

        return 0;
    }

    /**
     * Calculate the default field weights.
     *
     * This gives more weight to the 'slug pointer fields'.
     *
     * @return array
     */
    private function getFieldWeights()
    {
        // This could be more configurable
        // (see also Storage->searchSingleContentType)
        $searchableTypes = array('text', 'textarea', 'html', 'markdown');

        $fields = array();

        foreach ($this->contenttype['fields'] as $key => $config) {
            if (in_array($config['type'], $searchableTypes)) {
                $fields[$key] = isset($config['searchweight']) ? $config['searchweight'] : 50;
            }
        }

        foreach ($this->contenttype['fields'] as $config) {
            if ($config['type'] == 'slug') {
                foreach ($config['uses'] as $ptrField) {
                    if (isset($fields[$ptrField])) {
                        $fields[$ptrField] = 100;
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Calculate the default taxonomy weights.
     *
     * Adds weights to taxonomies that behave like tags.
     *
     * @return array
     */
    private function getTaxonomyWeights()
    {
        $taxonomies = array();

        if (isset($this->contenttype['taxonomy'])) {
            foreach ($this->contenttype['taxonomy'] as $key) {
                if ($this->app['config']->get('taxonomy/' . $key . '/behaves_like') == 'tags') {
                    $taxonomies[$key] = $this->app['config']->get('taxonomy/' . $key . '/searchweight', 75);
                }
            }
        }

        return $taxonomies;
    }

    /**
     * Weigh this content against a query.
     *
     * The query is assumed to be in a format as returned by decode Storage->decodeSearchQuery().
     *
     * @param array $query Query to weigh against
     *
     * @return void
     */
    public function weighSearchResult($query)
    {
        static $contenttypeFields = null;
        static $contenttypeTaxonomies = null;

        $ct = $this->contenttype['slug'];
        if ((is_null($contenttypeFields)) || (!isset($contenttypeFields[$ct]))) {
            // Should run only once per contenttype (e.g. singlular_name)
            $contenttypeFields[$ct] = $this->getFieldWeights();
            $contenttypeTaxonomies[$ct] = $this->getTaxonomyWeights();
        }

        $weight = 0;

        // Go over all field, and calculate the overall weight.
        foreach ($contenttypeFields[$ct] as $key => $fieldWeight) {
            $value = $this->values[$key];
            if (is_array($value)) {
                $value = implode(' ', $value);
            }
            $weight += $this->weighQueryText($value, $query['use_q'], $query['words'], $fieldWeight);
        }

        // Go over all taxonomies, and calculate the overall weight.
        foreach ($contenttypeTaxonomies[$ct] as $key => $taxonomy) {

            // skip empty taxonomies.
            if (empty($this->taxonomy[$key])) {
                continue;
            }
            $weight += $this->weighQueryText(implode(' ', $this->taxonomy[$key]), $query['use_q'], $query['words'], $taxonomy);
        }

        $this->lastWeight = $weight;
    }

    /**
     * Get the content's query weight… and something to eat… it looks hungry.
     *
     * @return integer
     */
    public function getSearchResultWeight()
    {
        return $this->lastWeight;
    }

    /**
     * ArrayAccess support.
     *
     * @param mixed $offset
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    /**
     * ArrayAccess support.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->getDecodedValue($offset);
    }

    /**
     * ArrayAccess support.
     *
     * @todo we could implement an setDecodedValue() function to do the encoding here
     *
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    /**
     * ArrayAccess support.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        if (isset($this->values[$offset])) {
            unset($this->values[$offset]);
        }
    }
}
