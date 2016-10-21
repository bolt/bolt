<?php

namespace Bolt\Storage\Mapping;

use ArrayAccess;
use Cocur\Slugify\Slugify;

/**
 * Mapping class to represent a ContentType with array access.
 *
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ContentType implements ArrayAccess
{
    /** @var string */
    protected $boltname;
    /** @var array */
    protected $contentType;

    protected $config;

    protected $initialised = false;

    /**
     * Constructor.
     *
     * @param string $boltname
     * @param array $contentType
     * @param array $config
     */
    public function __construct($boltname, array $contentType, array $config = [])
    {
        $this->boltname = $boltname;
        $this->contentType = $contentType;
        $this->config = $config;
    }

    public function setup()
    {
        if (!isset($this->contentType['slug']) && !is_numeric($this->boltname)) {
            $this->contentType['slug'] = Slugify::create()
                ->slugify($this->boltname);
        }

        if (!isset($this->contentType['slug'])) {
            $this->contentType['slug'] = Slugify::create()
                ->slugify($this->boltname);
        }
        if (!isset($this->contentType['singular_slug'])) {
            $this->contentType['singular_slug'] = Slugify::create()
                ->slugify($this->contentType['singular_name']);
        }

        if (($this->get('viewless')) || (!$this->config['liveeditor'])) {
            $this->contentType['liveeditor'] = false;
        }

        // Allow explicit setting of a Contenttype's table name suffix. We default
        // to slug if not present as it has been this way since Bolt v1.2.1
        if (!isset($this->contentType['tablename'])) {
            $this->contentType['tablename'] = Slugify::create()
                ->slugify($this->contentType['slug'], '_');
        } else {
            $this->contentType['tablename'] = Slugify::create()
                ->slugify($this->contentType['tablename'], '_');
        }

        if (!empty($this->contentType['relations']) && is_array($this->contentType['relations'])) {
            foreach (array_keys($this->contentType['relations']) as $relkey) {
                if ($relkey != Slugify::create()->slugify($relkey)) {
                    $this->contentType['relations'][Slugify::create()
                        ->slugify($relkey)] = $this->contentType['relations'][$relkey];
                    unset($this->contentType['relations'][$relkey]);
                }
            }
        }


    }

    public function setupFields(MappingManager $mappingManager)
    {
        if (!$this->initialised) {
            $fields = new FieldCollection();
            foreach ($this->contentType['fields'] as $key => $field) {
                $fields->set($key, $mappingManager->load($key, $field));
            }

            $this->contentType['fields'] = $fields;
        }
    }

    public function validate()
    {
        // If neither 'name' nor 'slug' is set, we need to warn the user. Same goes for when
        // neither 'singular_name' nor 'singular_slug' is set.
        if (!isset($this->contentType['name']) && !isset($this->contentType['slug'])) {
            $error = sprintf(
                "In contenttype <code>%s</code>, neither 'name' nor 'slug' is set. Please edit <code>contenttypes.yml</code>, and correct this.",
                $this->boltname
            );

            throw new InvalidArgumentException($error);
        }

        if (!isset($this->contentType['singular_name']) && !isset($this->contentType['singular_slug'])) {
            $error = sprintf(
                "In contenttype <code>%s</code>, neither 'singular_name' nor 'singular_slug' is set. Please edit <code>contenttypes.yml</code>, and correct this.",
                $this->boltname
            );

            throw new InvalidArgumentException($error);
        }

        // Contenttypes without fields make no sense.
        if (!isset($this->contentType['fields'])) {
            $error = sprintf(
                "In contenttype <code>%s</code>, no 'fields' are set. Please edit <code>contenttypes.yml</code>, and correct this.",
                $this->boltname
            );

            throw new InvalidArgumentException($error);
        }
    }

    public function getAllowNumericSlugs()
    {
        return $this->get('allow_numeric_slugs', false);
    }

    protected function get($param, $default = null)
    {
        if ($this->has($param)) {
            return $this->contentType[$param];
        }

        return $default;
    }

    protected function has($param)
    {
        if (array_key_exists($param, $this->contentType) && !empty($this->contentType[$param])) {
            return true;
        }

        return false;
    }

    public function getDefaultStatus()
    {
        return $this->get('default_status', 'draft');
    }

    public function getIconMany()
    {
        return $this->get('icon_many', false);
    }

    public function getIconOne()
    {
        return $this->get('icon_one', false);
    }

    public function getGroups()
    {
        $groups = [];
        $hasGroups = false;

        foreach ($this->getFields() as $field) {
            if (!empty($field->getGroup())) {
                $hasGroups = true;
            }
            $currentGroup = $field->getGroup();
            $groups[$currentGroup] = 1;
        }

        $this->contentType['groups'] = $hasGroups ? array_keys($groups) : [];

    }

    public function getFields()
    {
        return $this->get('fields', []);
    }

    public function getListingRecords()
    {
        return $this->get('listing_records', false);
    }

    public function getListingTemplate()
    {
        return $this->get('listing_template', false);
    }

    public function getLiveeditor()
    {
        return $this->get('liveeditor', true);
    }

    public function getName()
    {
        return $this->get('name');
    }

    public function getRecordsperpage()
    {
        return $this->get('recordsperpage', false);
    }

    public function getRecordTemplate()
    {
        return $this->get('record_template', false);
    }

    public function getRelations()
    {
        return $this->get('relations', []);
    }

    public function getSearchable()
    {
        return $this->get('searchable', true);
    }

    public function getShowInMenu()
    {
        return $this->get('show_in_menu', true);
    }

    public function getShowOnDashboard()
    {
        return $this->get('show_on_dashboard', true);
    }

    public function getSingularName()
    {
        return $this->get('singular_name');
    }

    public function getSingularSlug()
    {
        return $this->get('singular_slug');
    }

    public function getSlug()
    {
        return $this->get('slug');
    }

    public function getSort()
    {
        return $this->get('sort', false);
    }

    public function getTaxonomy()
    {
        return $this->get('taxonomy', []);
    }

    public function getViewless()
    {
        return $this->get('viewless', false);
    }

    public function __toString()
    {
        return $this->boltname;
    }

    /**
     *  ArrayAccess interface methods
     *
     */
    public function offsetSet($offset, $value)
    {
        $this->contentType[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetUnset($offset)
    {
        unset($this->contentType[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function getFields()
    {
        if (isset($this->contentType['fields'])) {
            return $this->contentType['fields'];
        }

        return [];
    }
}
