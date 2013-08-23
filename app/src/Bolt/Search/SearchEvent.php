<?php

namespace Bolt\Search;

use Silex;
use Bolt;
use Symfony\Component\EventDispatcher\Event;

/**
 * Description of SearchEvent
 *
 * @author leon
 */
class SearchEvent extends Event
{
    protected $queryBuilder;
    
    protected $contenttype;
    
    protected $filter;


    public function __construct(\Bolt\Search\QueryBuilderAdapterInterface $queryBuilder, $contenttype, $filter) 
    {
        $this->contenttype = $contenttype;
        $this->queryBuilder = $queryBuilder;
        $this->filter = (string) $filter;
    }
    
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }
    
    public function getContenttype()
    {
        return $this->contenttype;
    }
    
    public function getFilter()
    {
        return $this->filter;
    }
}

