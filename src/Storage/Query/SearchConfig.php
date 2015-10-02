<?php

namespace Bolt\Storage\Query;

use Bolt\Config;

/**
 * This class takes an overall config array as input and parses into values
 * applicable for performing searches.
 *
 * This takes into account contenttypes that aren't searchable along with
 * taxonomy and field weightings.
 */
class SearchConfig
{
    protected $config = [];
    protected $searchableTypes = [];
    protected $joins = [];

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->parseContenttypes();
    }

    /**
     * Get the config of all fields for a given content type.
     *
     * @param string $contenttype
     *
     * @return array|false
     */
    public function getConfig($contenttype)
    {
        if (array_key_exists($contenttype, $this->searchableTypes)) {
            return $this->searchableTypes[$contenttype];
        }

        return false;
    }

    /**
     * Get the config of one given field for a given content type.
     *
     * @param string $contenttype
     * @param string $field
     *
     * @return array|false
     */
    public function getFieldConfig($contenttype, $field)
    {
        if (isset($this->searchableTypes[$contenttype][$field])) {
            return $this->searchableTypes[$contenttype][$field];
        }

        return false;
    }

    /**
     * Iterates over the main config and delegates weighting to both
     * searchable columns and searchable taxonomies.
     *
     * @return void
     */
    protected function parseContenttypes()
    {
        $contenttypes = $this->config->get('contenttypes');

        foreach ($contenttypes as $type => $values) {
            if (! $this->isInvisible($type)) {
                $this->getSearchableColumns($type);
                if (isset($values['taxonomy'])) {
                    $this->parseTaxonomies($type, $values['taxonomy']);
                }
            }
        }
    }

    /**
     * Iterates the taxonomies for a given contenttype, then assigns a
     * weighting based on type.
     *
     * @param string $contenttype
     * @param array  $taxonomies
     *
     * @return void
     */
    protected function parseTaxonomies($contenttype, $taxonomies)
    {
        foreach ((array)$taxonomies as $taxonomy) {
            $taxonomyConfig = $this->config->get('taxonomy/'.$taxonomy);
            if (isset($taxonomyConfig['searchweight'])) {
                $weight = $taxonomyConfig['searchweight'];
            } elseif (isset($taxonomyConfig['behaves_like']) && $taxonomyConfig['behaves_like'] === 'tags') {
                $weight = 75;
            } else {
                $weight = 50;
            }
            $this->searchableTypes[$contenttype][$taxonomy] = ['weight' => $weight];
            $this->joins[$contenttype][] = $taxonomy;
        }
    }

    /**
     * Helper method to return the join search columns for a contenttype
     * weighting based on type.
     *
     * @param string $contenttype
     *
     * @return array
     */
    public function getJoins($contenttype)
    {
        return $this->joins[$contenttype];
    }

    /**
     * Determine what columns are searchable for a given contenttype.
     *
     * @param string $type
     *
     * @return void
     */
    protected function getSearchableColumns($type)
    {
        $fields = $this->config->get('contenttypes/'.$type.'/fields');

        foreach ($fields as $field => $options) {
            if (in_array($options['type'], ['text', 'textarea', 'html', 'markdown']) || $options['searchable'] == true) {
                if (isset($options['searchweight'])) {
                    $weight = (int)$options['searchweight'];
                } elseif (isset($fields['slug']['uses']) && in_array($field, (array)$fields['slug']['uses'])) {
                    $weight = 100;
                } else {
                    $weight = 50;
                }

                $this->searchableTypes[$type][$field] = ['weight' => $weight];
            }
        }
    }

    /**
     * Does some checks to see whether a contenttype should appear in search results.
     * This is based on contenttype options.
     *
     * @param string $contenttype
     *
     * @return boolean
     */
    protected function isInvisible($contenttype)
    {
        $info = $this->config->get('contenttypes/'.$contenttype);
        if ($info) {
            if (array_key_exists('viewless', $info) && $info['viewless'] == true) {
                return true;
            }
            if (array_key_exists('searchable', $info) && $info['searchable'] == false) {
                return true;
            }
        }

        return false;
    }
}
