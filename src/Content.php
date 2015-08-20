<?php

namespace Bolt;

use Bolt\Helpers\Html;
use Bolt\Storage\Entity;
use Maid\Maid;
use Silex;

class Content implements \ArrayAccess
{
    use Entity\ContentRelationTrait;
    use Entity\ContentTaxonomyTrait;
    use Entity\ContentValuesTrait;

    public $id;
    public $values = [];
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
    public function __construct(Silex\Application $app, $contenttype = '', $values = [], $isRootType = true)
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
        return [
            'id',
            'slug',
            'datecreated',
            'datechanged',
            'datepublish',
            'datedepublish',
            'ownerid',
            'status',
            'templatefields'
        ];
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
            [$this, 'upcountNameCallback'],
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
                        ['div', 'p', 'br', 'hr', 's', 'u', 'strong', 'em', 'i', 'b', 'li', 'ul', 'ol', 'blockquote', 'pre', 'code', 'tt', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'dd', 'dl', 'dt', 'table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr', 'a', 'img'];
                    $allowed_attributes = !empty($config['allowed_attributes']) ? $config['allowed_attributes'] :
                        ['id', 'class', 'name', 'value', 'href', 'src'];

                    // Sanitize/clean the HTML.
                    $maid = new Maid(
                        [
                            'output-format'   => 'html',
                            'allowed-tags'    => $allowed_tags,
                            'allowed-attribs' => $allowed_attributes
                        ]
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
            } catch (\Exception $e) {
                $message = 'Rendering a record Twig snippet failed.';
                $this->app['logger.system']->critical($message, ['event' => 'exception', 'exception' => $e]);

                return $message;
            }
        }

        return $snippet;
    }

    public function getTemplateContext()
    {
        return [
            'record'                            => $this,
            $this->contenttype['singular_slug'] => $this // Make sure we can also access it as {{ page.title }} for pages, etc.
        ];
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
     * @return array
     */
    public function getTitleColumnName()
    {
        // If we specified a specific fieldname or array of fieldnames as 'title'.
        if (!empty($this->contenttype['title_format'])) {
            if (!is_array($this->contenttype['title_format'])) {
                $this->contenttype['title_format'] = [$this->contenttype['title_format']];
            }
            return $this->contenttype['title_format'];
        }

        // Sets the names of some 'common' names for the 'title' column.
        $names = ['title', 'name', 'caption', 'subject'];

        // Some localised options as well
        $names = array_merge($names, ['titel', 'naam', 'onderwerp']); // NL
        $names = array_merge($names, ['nom', 'sujet']); // FR
        $names = array_merge($names, ['nombre', 'sujeto']); // ES

        foreach ($names as $name) {
            if (isset($this->values[$name])) {
                return [$name];
            }
        }

        // Otherwise, grab the first field of type 'text', and assume that's the title.
        if (!empty($this->contenttype['fields'])) {
            foreach ($this->contenttype['fields'] as $key => $field) {
                if ($field['type'] == 'text') {
                    return [$key];
                }
            }
        }

        // Nope, no title was found.
        return [];
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
    protected function getReference()
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
        $perm = 'contenttype:' . $this->contenttype['slug'] . ':edit:' . $this->id;

        if ($this->app['users']->isAllowed($perm)) {
            return $this->app->generatePath('editcontent', ['contenttypeslug' => $this->contenttype['slug'], 'id' => $this->id ]);
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
                    $route['defaults'] ?: [],
                    $this->getRouteRequirementParams($route),
                    [
                        'contenttypeslug' => $this->contenttype['singular_slug'],
                        'id'              => $this->id,
                        'slug'            => $slug
                    ]
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
        $uriID = $this->contenttype['singular_slug'] . '/' . $this->get('id');
        $uriSlug = $this->contenttype['singular_slug'] . '/' . $this->get('slug');

        return $uriID === $homepage || $uriSlug === $homepage;
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
        $params = [];
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
                return [$binding, $route];
            }
        }

        // Just return the 'generic' contentlink route.
        if (!empty($allroutes['contentlink'])) {
            return ['contentlink', $allroutes['contentlink']];
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
        return (isset($route['contenttype']) && $route['contenttype'] === $this->contenttype['singular_slug'])
            || (isset($route['contenttype']) && $route['contenttype'] === $this->contenttype['slug'])
            || (isset($route['recordslug'])  && $route['recordslug']  === $this->getReference())
        ;
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
    public function previous($field = 'datepublish', $where = [])
    {
        list($field, $asc) = $this->app['storage']->getSortOrder($field);

        $operator = $asc ? '<' : '>';
        $order = $asc ? ' DESC' : ' ASC';

        $params = [
            $field         => $operator . $this->values[$field],
            'limit'        => 1,
            'order'        => $field . $order,
            'returnsingle' => true,
            'hydrate'      => false
        ];

        $pager = [];
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
    public function next($field = 'datepublish', $where = [])
    {
        list($field, $asc) = $this->app['storage']->getSortOrder($field);

        $operator = $asc ? '>' : '<';
        $order = $asc ? ' ASC' : ' DESC';

        $params = [
            $field         => $operator . $this->values[$field],
            'limit'        => 1,
            'order'        => $field . $order,
            'returnsingle' => true,
            'hydrate'      => false
        ];

        $pager = [];
        $next = $this->app['storage']->getContent($this->contenttype['singular_slug'], $params, $pager, $where);

        return $next;
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
            return ['type' => ''];
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
