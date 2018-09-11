<?php

namespace Bolt\Events;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\QueryResultset;
use Symfony\Component\EventDispatcher\Event;

/**
 * Query event allow access to content queries.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QueryEvent extends Event
{
    protected $query;
    protected $result;

    public function __construct($query, $result = null)
    {
        $this->query = $query;
        $this->result = $result;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function hasResult()
    {
        return $this->result instanceof QueryResultset;
    }
}
