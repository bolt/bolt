<?php

namespace Bolt\Legacy;

use Bolt\Common\Json;
use Bolt\Helpers\Excerpt;
use Bolt\Storage\Entity;
use Silex;
use Twig\Markup;

/**
 * Legacy Content class.
 *
 * @deprecated Deprecated since 3.0, to be removed in 4.0.
 */
class Content implements \ArrayAccess
{
    use Entity\ContentTypeTrait;
    use Entity\ContentRelationTrait;
    use Entity\ContentRouteTrait;
    use Entity\ContentSearchTrait;
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
            'templatefields',
        ];
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
            $fieldtype = $this->fieldType($name);
            $fieldinfo = $this->fieldInfo($name);
            $allowtwig = !empty($fieldinfo['allowtwig']);

            switch ($fieldtype) {
                case 'markdown':
                    // Deprecated: This should be moved to a render function in
                    // Bolt\Storage\Field\Type\MarkdownType eventually.
                    $value = $this->app['markdown']->text($this->values[$name]);
                    $value = $this->preParse($value, $allowtwig);
                    $value = new Markup($value, 'UTF-8');

                    break;

                case 'html':
                case 'text':
                case 'textarea':
                    $value = $this->preParse($this->values[$name], $allowtwig);
                    $value = new Markup($value, 'UTF-8');

                    break;

                case 'imagelist':
                case 'filelist':
                    if (is_string($this->values[$name])) {
                        // Parse the field as JSON, return the array
                        $value = Json::parse($this->values[$name]);
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
     * @internal Forward comparability for to forward legacy calls to getDecodedValue()
     *
     * @param string $fieldName
     *
     * @return mixed
     */
    public function getRenderedValue($fieldName)
    {
        return $this->getDecodedValue($fieldName);
    }

    /**
     * Create an excerpt for the Record.
     *
     * @param int               $length
     * @param bool              $includeTitle
     * @param array|string|null $focus
     * @param array             $stripFields
     *
     * @return string|null
     */
    public function getExcerpt($length = 200, $includeTitle = false, $focus = null, $stripFields = [])
    {
        $excerpter = new Excerpt($this);
        $excerpt = $excerpter->getExcerpt($length, $includeTitle, $focus, $stripFields);

        return new Markup($excerpt, 'UTF-8');
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
        if (!$allowtwig || !preg_match('/[{][{%#]/', $snippet)) {
            return $snippet;
        }

        // Don't parse Twig for live editor.
        $request = $this->app['request_stack']->getCurrentRequest();
        if ($request && $request->request->getBoolean('_live-editor-preview')) {
            return $snippet;
        }

        $snippet = html_entity_decode($snippet, ENT_QUOTES, 'UTF-8');

        $template = $this->app['twig']->createTemplate((string) $snippet);

        return twig_include($this->app['twig'], $this->getTemplateContext(), $template, [], true, false, true);
    }

    public function getTemplateContext()
    {
        return [
            'record'                            => $this,
            $this->contenttype['singular_slug'] => $this, // Make sure we can also access it as {{ page.title }} for pages, etc.
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
     * Get the previous record. In this case 'previous' is defined as 'latest one published before
     * this one' by default. You can pass a parameter like 'id' or '-title' to use that as
     * the column to sort on.
     *
     * @param string $field
     * @param array  $where
     *
     * @return \Bolt\Legacy\Content
     */
    public function previous($field = 'datepublish', $where = [])
    {
        list($field, $asc) = $this->app['storage']->getSortOrder($field);

        $operator = $asc ? '<' : '>';
        $order = $asc ? ' DESC' : ' ASC';
        $value = isset($this->values[$field]) ? $this->values[$field] : null;

        $params = [
            $field         => $operator . $value,
            'limit'        => 1,
            'order'        => $field . $order,
            'returnsingle' => true,
            'hydrate'      => true,
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
     * @return \Bolt\Legacy\Content
     */
    public function next($field = 'datepublish', $where = [])
    {
        list($field, $asc) = $this->app['storage']->getSortOrder($field);

        $operator = $asc ? '>' : '<';
        $order = $asc ? ' ASC' : ' DESC';
        $value = isset($this->values[$field]) ? $this->values[$field] : null;

        $params = [
            $field         => $operator . $value,
            'limit'        => 1,
            'order'        => $field . $order,
            'returnsingle' => true,
            'hydrate'      => true,
        ];

        $pager = [];
        $next = $this->app['storage']->getContent($this->contenttype['singular_slug'], $params, $pager, $where);

        return $next;
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
