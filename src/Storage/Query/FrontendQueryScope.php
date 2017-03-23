<?php

namespace Bolt\Storage\Query;

use Bolt\Config;
use Bolt\Storage\Query\Directive\OrderDirective;

/**
 * This class takes an overall config array as input and parses into values
 * applicable for performing select queries.
 *
 * This takes into account default ordering for ContentTypes.
 */
class FrontendQueryScope implements QueryScopeInterface
{
    /** @var array|Config */
    protected $config = [];
    /** @var array */
    protected $orderBys = [];

    /**
     * Constructor.
     *
     * @param Config $config
     */
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

        return false;
    }

    /**
     * Get the config of one given field for a given content type.
     *
     * @param string $contentType
     *
     * @return array|false
     */
    public function getOrder($contentType)
    {
        if (isset($this->orderBys[$contentType])) {
            return $this->orderBys[$contentType];
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
            if (isset($values['sort'])) {
                $this->orderBys[$type] = $values['sort'];
            }
        }
    }

    /**
     * @param QueryInterface $query
     */
    public function onQueryExecute(QueryInterface $query)
    {
        $ct = $query->getContentType();

        // Setup default ordering of queries on a per-contenttype basis
        $existing = $query->getParameter('order');
        if (!$existing && $this->orderBys[$ct]) {
            $handler = new OrderDirective();
            $handler($query, $this->orderBys[$ct]);
        }

        // Setup status to only published unless otherwise specified
        $status = $query->getParameter('status');
        if (!$status) {
            $query->setParameter('status', 'published');
        }
    }
}
