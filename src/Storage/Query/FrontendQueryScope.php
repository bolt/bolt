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
     * Get the default order setting for a given content type.
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
     * Iterates over the main config and sets up what the default ordering should be
     */
    protected function parseContenttypes()
    {
        $contentTypes = $this->config->get('contenttypes');
        foreach ($contentTypes as $type => $values) {
            $sort = $values['sort'] ?: '-datepublish';
            $this->orderBys[$type] = $sort;
            if (isset($values['singular_slug'])) {
                $this->orderBys[$values['singular_slug']] = $sort;
            }
        }
    }

    /**
     * @param ContentQueryInterface $query
     */
    public function onQueryExecute(ContentQueryInterface $query)
    {
        $ct = $query->getContentType();

        // Setup default ordering of queries on a per-contenttype basis
        if (empty($query->getQueryBuilder()->getQueryPart('orderBy')) && isset($this->orderBys[$ct])) {
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
