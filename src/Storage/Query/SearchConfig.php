<?php

namespace Bolt\Storage\Query;

use Bolt\Config;

/**
 * This class takes an overall config array as input and parses into values
 * applicable for performing searches.
 *
 * This takes into account ContentTypes that aren't searchable along with
 * taxonomy and field weightings.
 */
class SearchConfig
{
    /** @var array|Config */
    protected $config = [];
    /** @var array */
    protected $searchableTypes = [];

    /** @var array */
    protected $invisibleTypes = [];

    /** @var array */
    protected $joins = [];

    protected $searchInvisible = false;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->parseContenttypes();
    }

    /**
     * Get the config of all fields for a given content type.
     *
     * @param string $contentType
     *
     * @return array|false
     */
    public function getConfig($contentType)
    {
        if (array_key_exists($contentType, $this->searchableTypes)) {
            return $this->searchableTypes[$contentType];
        }

        if ($this->canSearchInvisible() && array_key_exists($contentType, $this->invisibleTypes)) {
            return $this->invisibleTypes[$contentType];
        }

        return false;
    }


    /**
     * Get the config of one given field for a given content type.
     *
     * @param string $contentType
     * @param string $field
     *
     * @return array|false
     */
    public function getFieldConfig($contentType, $field)
    {
        if (isset($this->searchableTypes[$contentType][$field])) {
            return $this->searchableTypes[$contentType][$field];
        }

        if ($this->canSearchInvisible() && isset($this->invisibleTypes[$contentType][$field])) {
            return $this->invisibleTypes[$contentType][$field];
        }

        return false;
    }

    /**
     * Iterates over the main config and delegates weighting to both
     * searchable columns and searchable taxonomies.
     */
    protected function parseContenttypes()
    {
        $contentTypes = $this->config->get('contenttypes');

        foreach ($contentTypes as $type => $values) {
            $this->getSearchableColumns($type);
            if (isset($values['taxonomy'])) {
                $this->parseTaxonomies($type, $values['taxonomy']);
            }
        }
    }

    /**
     * Iterates the taxonomies for a given ContentType, then assigns a
     * weighting based on type.
     *
     * @param string $contentType
     * @param array  $taxonomies
     */
    protected function parseTaxonomies($contentType, $taxonomies)
    {
        foreach ((array) $taxonomies as $taxonomy) {
            $taxonomyConfig = $this->config->get('taxonomy/' . $taxonomy);
            if (isset($taxonomyConfig['searchweight'])) {
                $weight = $taxonomyConfig['searchweight'];
            } elseif (isset($taxonomyConfig['behaves_like']) && $taxonomyConfig['behaves_like'] === 'tags') {
                $weight = 75;
            } else {
                $weight = 50;
            }
            if (!$this->isInvisible($contentType)) {
                $this->searchableTypes[$contentType][$taxonomy] = ['weight' => $weight];
            } else {
                $this->invisibleTypes[$contentType][$taxonomy] = ['weight' => $weight];
            }
            $this->joins[$contentType][] = $taxonomy;
        }
    }

    /**
     * Helper method to return the join search columns for a ContentType
     * weighting based on type.
     *
     * @param string $contentType
     *
     * @return array
     */
    public function getJoins($contentType)
    {
        return $this->joins[$contentType];
    }

    /**
     * Determine what columns are searchable for a given ContentType.
     *
     * @param string $type
     */
    protected function getSearchableColumns($type)
    {
        $fields = $this->config->get('contenttypes/' . $type . '/fields');

        foreach ($fields as $field => $options) {
            if (in_array($options['type'], ['text', 'textarea', 'html', 'markdown']) ||
                (isset($options['searchable']) && $options['searchable'] === true)) {
                if (isset($options['searchweight'])) {
                    $weight = (int) $options['searchweight'];
                } elseif (isset($fields['slug']['uses']) && in_array($field, (array)$fields['slug']['uses'], true)) {
                    $weight = 100;
                } else {
                    $weight = 50;
                }

                if (!$this->isInvisible($type)) {
                    $this->searchableTypes[$type][$field] = ['weight' => $weight];
                } else {
                    $this->invisibleTypes[$type][$field] = ['weight' => $weight];
                }
            }
        }
    }

    /**
     * Does some checks to see whether a ContentType should appear in search results.
     * This is based on ContentType options.
     *
     * @param string $contentType
     *
     * @return bool
     */
    protected function isInvisible($contentType)
    {
        $info = $this->config->get('contenttypes/' . $contentType);
        if ($info) {
            if (array_key_exists('viewless', $info) && $info['viewless'] === true) {
                return true;
            }
            if (array_key_exists('searchable', $info) && $info['searchable'] === false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function canSearchInvisible()
    {
        return $this->searchInvisible;
    }

    /**
     * @param bool $searchInvisible
     */
    public function enableSearchInvisible($searchInvisible)
    {
        $this->searchInvisible = $searchInvisible;
    }

    /**
     * Return an array of searchable contenttypes
     * @return array
     */
    public function getSearchableTypes()
    {
        return $this->searchableTypes;
    }

}
