<?php

namespace Bolt;

use Silex;
use Symfony\Component\Filesystem\Filesystem;
use Bolt\Library as Lib;
use Bolt\Helpers\String;
use Bolt\Helpers\Input;
use Bolt\Helpers\Html;

class Content implements \ArrayAccess
{
    protected $app;
    public $id;
    public $values;
    public $taxonomy;
    public $relation;
    public $contenttype;

    // The last time we weight a searchresult
    private $last_weight = 0;

    public function __construct(Silex\Application $app, $contenttype = '', $values = '')
    {
        $this->app = $app;

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
                    $default_value = $this->app['config']->get('taxonomy/' . $taxonomytype . '/default');
                    $options = $this->app['config']->get('taxonomy/' . $taxonomytype . '/options');
                    if (isset($options) &&
                            isset($default_value) &&
                            array_search($default_value, array_keys($options)) !== false ) {
                            $this->setTaxonomy($taxonomytype, $default_value);
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
            $values = array();
            if (is_array($this->contenttype)) {
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
            // Specify an '(undefined contenttype)'..
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
            'status'
        );
    }

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
            $this->setValue($key, $value);
        }

        // If default status is set in contentttype..
        if (empty($this->values['status'])) {
            $this->values['status'] = $this->contenttype['default_status'];
        }

        $serialized_field_types = array(
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
            if (in_array($this->fieldtype($key), $serialized_field_types)) {
                if (!empty($value) && is_string($value) && (substr($value, 0, 2) == "a:" || $value[0] === '[' || $value[0] === '{')) {
                    $unserdata = @Lib::smartUnserialize($value);
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

                // See if it's widescreen or not..
                if (!empty($video['height']) && ( ($video['width'] / $video['height']) > 1.76)) {
                    $responsiveclass .= " widescreen";
                }

                if (strpos($video['url'], "vimeo") !== false) {
                    $responsiveclass .= " vimeo";
                }

                $video['responsive'] = sprintf('<div class="%s">%s</div>', $responsiveclass, $video['html']);

                // Mark them up as Twig_Markup..
                $video['html'] = new \Twig_Markup($video['html'], 'UTF-8');
                $video['responsive'] = new \Twig_Markup($video['responsive'], 'UTF-8');

                $this->values[$key] = $video;
            }

            // Make sure 'date' and 'datetime' don't end in " :00".
            if ($this->fieldtype($key) == "datetime") {
                if (strpos($this->values[$key], ":") === false) {
                    $this->values[$key] = trim($this->values[$key]) . " 00:00:00";
                }
                $this->values[$key] = str_replace(" :00", " 00:00", $this->values[$key]);
            }

        }
    }

    public function setValue($key, $value)
    {
        // Check if the value need to be unserialized..
        if (is_string($value) && substr($value, 0, 2) == "a:") {
            $unserdata = @Lib::smartUnserialize($value);
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
                    $value = date("1900-01-01 00:00:00");
                } else {
                    $value = date("Y-m-d H:i:s");
                }
            }
        }

        if (!isset($this->values['datechanged']) ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datechanged'])) {
            $this->values['datechanged'] = date("Y-m-d H:i:s");
        }

        $this->values[$key] = $value;
    }

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

        // Make sure we have a proper status..
        if (!in_array($values['status'], array('published', 'timed', 'held', 'draft'))) {
            if ($this['status']) {
                $values['status'] = $this['status'];
            } else {
                $values['status'] = "draft";
            }
        }

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

        // @todo check for allowed file types..

        // Handle file-uploads.
        if (!empty($_FILES)) {
            foreach ($_FILES as $key => $file) {

                if (empty($file['name'][0])) {
                    continue; // Skip 'empty' uploads..
                }

                $filename = sprintf(
                    '%s/files/%s/%s',
                    $this->app['paths']['rootpath'],
                    date('Y-m'),
                    String::makeSafe($file['name'][0], false, '[]{}()')
                );
                $basename = sprintf('/%s/%s', date('Y-m'), String::makeSafe($file['name'][0], false, "[]{}()"));

                if ($file['error'][0] != UPLOAD_ERR_OK) {
                    $this->app['log']->add('Upload: Error occured during upload: ' . $file['error'][0] . ' - ' . $filename, 2);
                    continue;
                }

                if (substr($key, 0, 11) != 'fileupload-') {
                    $this->app['log']->add("Upload: skipped an upload that wasn't for Content. - " . $filename, 2);
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
                    $this->app['log']->add("Upload: uploaded file '$basename'.", 2);
                    $values[$fieldname] = $basename;
                } else {
                    $this->app['log']->add("Upload: couldn't write upload '$basename'.", 2);
                }

            }
        }

        $this->setValues($values);
    }

    /**
     * "upcount" a filename: Add (1), (2), etc. for filenames that already exist.
     * Taken from jQuery file upload..
     *
     * @param  string $name
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
     * Taken from jQuery file upload..
     *
     * @see upcountName()
     * @param  array  $matches
     * @internal param string $name
     * @return string
     */
    protected function upcountNameCallback($matches)
    {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';

        return ' (' . $index . ')' . $ext;
    }

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
     * @param $taxonomytype
     * @param $slug
     * @param  string $name
     * @param  int    $sortorder
     * @return bool
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
        if ($this->app['config']->get('taxonomy/' . $taxonomytype . '/has_sortorder') == false) {
            $sortorder = false;
        } else {
            $sortorder = (int) $sortorder;
            // Note: by doing this we assume a contenttype can have only one taxonomy which has has_sortorder: true.
            $this->sortorder = $sortorder;
        }

        // Make the 'key' of the array an absolute link to the taxonomy.
        $link = sprintf("%s%s/%s", $this->app['paths']['root'], $taxonomytype, $slug);

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
     */
    public function sortTaxonomy()
    {
        if (empty($this->taxonomy)) {
            // Nothing to do here.
            return;
        }

        foreach ($this->taxonomy as $type => $values) {
            $taxonomytype = $this->app['config']->get('taxonomy/' . $type);
            // Don't order tags..
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
     * @param $group
     * @param string $name
     * @param string $taxonomytype
     * @param int    $sortorder
     * @internal param string $value
     */
    public function setGroup($group, $name, $taxonomytype, $sortorder = 0)
    {
        $this->group = array(
            'slug' => $group,
            'name' => $name
        );

        $has_sortorder = $this->app['config']->get('taxonomy/' . $taxonomytype . '/has_sortorder');

        // Only set the sortorder, if the contenttype has a taxonomy that has sortorder
        if ($has_sortorder !== false) {
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
     * @param  string $name name of the value to get
     * @return mixed  decoded value or null when no value available
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
                    $value = \ParsedownExtra::instance()->text($value);

                    // Sanitize/clean the HTML.
                    $maid = new \Maid\Maid(
                        array(
                            'output-format' => 'html',
                            'allowed-tags' => array('html', 'head', 'body', 'section', 'div', 'p', 'br', 'hr', 's', 'u', 'strong', 'em', 'i', 'b', 'li', 'ul', 'ol', 'menu', 'blockquote', 'pre', 'code', 'tt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'dd', 'dl', 'dh', 'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr', 'a', 'img'),
                            'allowed-attribs' => array('id', 'class', 'name', 'value', 'href', 'src')
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
     * If passed snippet contains Twig tags, parse the string as Twig, and return the results
     *
     * @param  string $snippet
     * @param $allowtwig
     * @return string
     */
    public function preParse($snippet, $allowtwig)
    {
        // Quickly verify that we actually need to parse the snippet!
        if ($allowtwig && preg_match('/[{][{%#]/', $snippet)) {
            $snippet = html_entity_decode($snippet, ENT_QUOTES, 'UTF-8');

            return $this->app['safe_render']->render($snippet, $this->getTemplateContext());
        }

        return $snippet;
    }

    public function getTemplateContext()
    {
        return array(
            'record' => $this,
            $this->contenttype['singular_slug'] => $this // Make sure we can also access it as {{ page.title }} for pages, etc.
        );
    }

    /**
     * Magic __call function, used for when templates use {{ content.title }},
     * so we can map it to $this->values['title']
     *
     * @param  string $name      method name originally called
     * @param  array  $arguments arguments to the call
     * @return mixed  return value of the call
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
     * pseudo-magic function, used for when templates use {{ content.get(title) }},
     * so we can map it to $this->values['title']
     */
    public function get($name)
    {
        // For fields that are stored as arrays, like 'video'
        if (strpos($name, ".") > 0) {
            list ($name, $attr) = explode(".", $name);
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
     * Get the title, name, caption or subject..
     */
    public function getTitle()
    {
        if ($column = $this->getTitleColumnName()) {
            return $this->values[$column];
        }

        // nope, no title was found..
        return "(untitled)";
    }

    /**
     * Get the columnname of the title, name, caption or subject..
     */
    public function getTitleColumnName()
    {
        // Sets the names of some 'common' names for the 'title' column.
        $names = array('title', 'name', 'caption', 'subject');

        // Some localised options as well
        $names = array_merge($names, array('titel', 'naam', 'onderwerp')); // NL
        $names = array_merge($names, array('nom', 'sujet')); // FR
        $names = array_merge($names, array('nombre', 'sujeto')); // ES

        foreach ($names as $name) {
            if (isset($this->values[$name])) {
                return $name;
            }
        }

        // Otherwise, grab the first field of type 'text', and assume that's the title.
        if (!empty($this->contenttype['fields'])) {
            foreach ($this->contenttype['fields'] as $key => $field) {
                if ($field['type'] == 'text') {
                    return $key;
                }
            }
        }

        // nope, no title was found..
        return false;
    }

    /**
     * Get the first image in the content ..
     */
    public function getImage()
    {
        // No fields, no image.
        if (empty($this->contenttype['fields'])) {
            return "";
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
     */
    public function getReference()
    {
        $reference = $this->contenttype['singular_slug'] . '/' . $this->values['slug'];

        return $reference;
    }

    /**
     * Creates a link to EDIT this record, if the user is logged in.
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

        $link = $this->app['url_generator']->generate(
            $binding,
            array_filter(
                array_merge(
                    $route['defaults'] ?: array(),
                    $this->getRouteRequirementParams($route),
                    array(
                        'contenttypeslug' => $this->contenttype['singular_slug'],
                        'id'              => $this->id,
                        'slug'            => $this->values['slug']
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
     */
    public function previous($field = 'datepublish', $where = array())
    {
        list ($field, $asc) = $this->app['storage']->getSortOrder($field);

        $operator = $asc ? '<' : '>';
        $order = $asc ? ' DESC' : ' ASC';

        $params = array(
            $field => $operator . $this->values[$field],
            'limit' => 1,
            'order' => $field . $order,
            'returnsingle' => true,
            'hydrate' => false
        );

        $previous = $this->app['storage']->getContent($this->contenttype['singular_slug'], $params, $dummy, $where);

        return $previous;
    }

    /**
     * Get the next record. In this case 'next' is defined as 'first one published after
     * this one' by default. You can pass a parameter like 'id' or '-title' to use that as
     * the column to sort on.
     */
    public function next($field = 'datepublish', $where = array())
    {
        list ($field, $asc) = $this->app['storage']->getSortOrder($field);

        $operator = $asc ? '>' : '<';
        $order = $asc ? ' ASC' : ' DESC';

        $params = array(
            $field => $operator . $this->values[$field],
            'limit' => 1,
            'order' => $field . $order,
            'returnsingle' => true,
            'hydrate' => false
        );

        $next = $this->app['storage']->getContent($this->contenttype['singular_slug'], $params, $dummy, $where);

        return $next;
    }

    /**
     * Gets one or more related records.
     *
     */
    public function related($filtercontenttype = '', $filterid = '')
    {
        if (empty($this->relation)) {
            return false; // nothing to do here.
        }

        $records = array();

        foreach ($this->relation as $contenttype => $ids) {
            if (!empty($filtercontenttype) && ($contenttype != $filtercontenttype)) {
                continue; // Skip other contenttypes, if we requested a specific type.
            }

            $params = array('hydrate' => true);
            $where = array('id' => implode(" || ", $ids));
            $dummy = false;

            $temp_result = $this->app['storage']->getContent($contenttype, $params, $dummy, $where);

            if (empty($temp_result)) {
                continue; // Go ahead if content not found.
            }
            
            // Variable $temp_result can be an array of object.
            if (is_array($temp_result)) {
                $records = array_merge($records, $temp_result);
            } else {
                $records[] = $temp_result;
            }
        }

        return $records;
    }


    /**
     * Get field information for the given field.
     * @param $key
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
     * @param $key
     * @return string
     */
    public function fieldtype($key)
    {
        $field = $this->fieldinfo($key);

        return $field['type'];
    }

    /**
     *
     * Create an excerpt for the content.
     *
     * @param  int    $length
     * @param  bool   $includetitle
     * @return string
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
                    // Skip empty fields, and fields called 'title' or 'name'..
                    if (!isset($this->values[$key]) || in_array($key, array('title', 'name'))) {
                        continue;
                    }
                    // add 'text', 'html' and 'textarea' fields.
                    if (in_array($field['type'], array('text', 'html', 'textarea'))) {
                        $excerptParts[] = $this->values[$key];
                    }
                    // add 'markdown' field
                    if ($field['type'] === 'markdown') {
                        $excerptParts[] = $value = \ParsedownExtra::instance()->text($this->values[$key]);
                    }

                }
            }

            $excerpt = str_replace('>', '> ', implode(' ', $excerptParts));
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
     * @param  string $fields        Comma separated list of fields to clean up
     * @param  int    $excerptLength Number of chars of the excerpt
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
                $maid = new \Maid\Maid(
                    array(
                        'output-format' => 'html',
                        'allowed-tags' => array('a', 'b', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'p', 'strong', 'em', 'i', 'u', 'strike', 'ul', 'ol', 'li', 'img'),
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
     * Weight a text part relative to some other part
     *
     * @param  string  $subject  The subject to search in.
     * @param  string  $complete The complete search term (lowercased).
     * @param  array   $words    All the individual search terms (lowercased).
     * @param  integer $max      Maximum number of points to return.
     * @return integer The weight
     */
    private function weighQueryText($subject, $complete, $words, $max)
    {
        $low_subject = mb_strtolower(trim($subject));

        if ($low_subject == $complete) {
            // a complete match is 100% of the maximum
            return round((100 / 100) * $max);
        }
        if (strstr($low_subject, $complete)) {
            // when the whole query is found somewhere is 70% of the maximum
            return round((70 / 100) * $max);
        }

        $word_matches = 0;
        $cnt_words    = count($words);
        for ($i = 0; $i < $cnt_words; $i++) {
            if (strstr($low_subject, $words[$i])) {
                $word_matches++;
            }
        }
        if ($word_matches > 0) {
            // marcel: word matches are maximum of 50% of the maximum per word
            // xiao: made (100/100) instead of (50/100).
            return round(($word_matches / $cnt_words) * (100 / 100) * $max);
        }

        return 0;
    }

    /**
     * Calculate the default field weights
     *
     * This gives more weight to the 'slug pointer fields'.
     */
    private function getFieldWeights()
    {
        // This could be more configurable
        // (see also Storage->searchSingleContentType)
        $searchable_types = array('text', 'textarea', 'html', 'markdown');

        $fields = array();

        foreach ($this->contenttype['fields'] as $key => $config) {
            if (in_array($config['type'], $searchable_types)) {
                $fields[$key] = isset($config['searchweight']) ? $config['searchweight'] : 50;
            }
        }

        foreach ($this->contenttype['fields'] as $config) {

            if ($config['type'] == 'slug') {
                foreach ($config['uses'] as $ptr_field) {
                    if (isset($fields[$ptr_field])) {
                        $fields[$ptr_field] = 100;
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Calculate the default taxonomy weights
     *
     * Adds weights to taxonomies that behave like tags
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
     * Weigh this content against a query
     *
     * The query is assumed to be in a format as returned by decode Storage->decodeSearchQuery().
     *
     * @param array $query Query to weigh against
     */
    public function weighSearchResult($query)
    {
        static $contenttype_fields = null;
        static $contenttype_taxonomies = null;

        $ct = $this->contenttype['slug'];
        if ((is_null($contenttype_fields)) || (!isset($contenttype_fields[$ct]))) {
            // Should run only once per contenttype (e.g. singlular_name)
            $contenttype_fields[$ct] = $this->getFieldWeights();
            $contenttype_taxonomies[$ct] = $this->getTaxonomyWeights();
        }

        $weight = 0;

        // Go over all field, and calculate the overall weight.
        foreach ($contenttype_fields[$ct] as $key => $field_weight) {
            $weight += $this->weighQueryText($this->values[$key], $query['use_q'], $query['words'], $field_weight);
        }

        // Go over all taxonomies, and calculate the overall weight.
        foreach ($contenttype_taxonomies[$ct] as $key => $taxonomy) {

            // skip empty taxonomies.
            if (empty($this->taxonomy[$key])) {
                continue;
            }
            $weight += $this->weighQueryText(implode(' ', $this->taxonomy[$key]), $query['use_q'], $query['words'], $taxonomy);
        }

        $this->last_weight = $weight;
    }

    /**
     */
    public function getSearchResultWeight()
    {
        return $this->last_weight;
    }

    /**
     * ArrayAccess support
     */
    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    /**
     * ArrayAccess support
     */
    public function offsetGet($offset)
    {
        return $this->getDecodedValue($offset);
    }

    /**
     * ArrayAccess support
     *
     * @todo we could implement an setDecodedValue() function to do the encoding here
     */
    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    /**
     * ArrayAccess support
     */
    public function offsetUnset($offset)
    {
        if (isset($this->values[$offset])) {
            unset($this->values[$offset]);
        }
    }
}
