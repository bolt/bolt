<?php

namespace Bolt\Storage\Query;

use Bolt\Storage\EntityManager;

/**
 *  Handler class to convert the DSL for content queries into an
 *  object representation.
 *
 *  @author Ross Riley <riley.ross@gmail.com>
 */
class ContentQueryParser
{
    protected $em;

    protected $query;

    protected $params;

    protected $contentTypes = [];

    protected $operation;

    protected $identifier;

    protected $operations = ['search', 'latest', 'first', 'random'];

    protected $directives = [];
    
    protected $handlers = [];
    
    protected $services = [];

    public function __construct(EntityManager $em, $query = null, array $params = [])
    {
        $this->em = $em;
        $this->query = $query;
        $this->params = $params;
        $this->setupDefaults();
    }

    public function setupDefaults()
    {
        $this->addHandler('select', function () {
            $set = new QueryResultset();

            foreach ($this->getContentTypes() as $contenttype) {                
                $query = $this->services['select'];
                $repo = $this->em->getRepository($contenttype);
                $query->setQueryBuilder($repo->createQueryBuilder($contenttype));
                $query->setContentType($contenttype);
                
                $this->parseDirectives($query);
                
                $query->setParameters($this->params);

                $result = $repo->queryWith($query);
                if ($result) {
                    $set->add($result, $contenttype);
                }
            }

            return $set;
        });
        
        $this->addDirective('printquery', function(QueryInterface $query){
            
        });
        
        $this->addDirective('getquery', function(QueryInterface $query, callable $callback){
            
        });
        
        $this->addDirective('returnsingle', function(QueryInterface $query){
            $query->getQueryBuilder()->setMaxResults(1);
        });
        
        $this->addDirective('order', function(QueryInterface $query, $order){
            $query->getQueryBuilder()->orderBy($order);
        });
        
        $this->addDirective('limit', function(QueryInterface $query, $limit){
            $query->getQueryBuilder()->setMaxResults($limit);
        });
        
    }
    
    public function setQuery($query)
    {
        $this->query = $query;
    }
    
    public function setParameters($params)
    {
        $this->params = $params;
    }

    public function parse()
    {
        $this->parseContent();
        $this->parseOperation();
    }

    /**
     * Parses the content area of the querystring.
     */
    protected function parseContent()
    {
        $contentString = strtok($this->query, '/');

        $content = array();
        $delim = '(),';
        $tok = strtok($contentString, $delim);
        while ($tok !== false) {
            $content[] = $tok;
            $tok = strtok($delim);
        }

        $this->contentTypes = $content;
    }

    /**
     * Internal method that takes the 'query' part of the input and
     * parses it into one of the various operations supported.
     * 
     * A simple select operation will just contain the contenttype eg 'pages'
     * but additional operations can be triggered using the '/' separator.
     * 
     * @return string Parsed operation name
     */
    protected function parseOperation()
    {
        $operation = 'select';

        $queryParts = explode('/', $this->query);
        array_shift($queryParts);

        if (!count($queryParts)) {
            $this->operation = $operation;
            return;
        }

        if (in_array($queryParts[0], $this->operations)) {
            $operation = array_shift($queryParts);
            $this->params['limit'] = array_shift($queryParts);
            $this->identifier = implode(',', $queryParts);
        } else {
            $this->identifier = implode(',', $queryParts);
        }

        $this->operation = $operation;
    }

    /**
     * Directives are all of the other parameters supported by Bolt that do not
     * relate to an actual filter query. Some examples include 'printquery', 'limit',
     * 'order' or 'returnsingle'
     * 
     * All these need to parsed and taken out of the params that are sent to the query.
     * 
     * @return void
     */
    protected function parseDirectives(QueryInterface $query)
    {
        if (!$this->params) {
            return;
        }
        foreach ($this->params as $key => $value) {
            if ($this->hasDirective($key)) {
                if (is_callable($this->getDirective($key))) {
                    unset($this->params[$key]);
                    call_user_func_array($this->getDirective($key), [$query, $value]);
                }
            }
        }
    }

    public function getContentTypes()
    {
        return $this->contentTypes;
    }

    public function getOperation()
    {
        return $this->operation;
    }

    public function getIdentifier()
    {
        return $this->identifier;
    }


    public function getDirective($check)
    {
        return $this->directives[$check];
    }

    public function hasDirective($check)
    {
        return array_key_exists($check, $this->directives);
    }
    
    public function addDirective($key, callable $callback = null )
    {
        if (!array_key_exists($key, $this->directives)) {
            $this->directives[$key] = $callback;
        }
    }

    public function addHandler($operation, callable $callback)
    {
        $this->handlers[$operation] = $callback;
    }
    
    public function addService($operation, $service)
    {
        $this->services[$operation] = $service;
    }
    
    public function getParameters()
    {
        return $this->params;
    }
    
    public function getParameter($param)
    {
        return $this->params[$param];
    }

    public function fetch()
    {
        $this->parse();

        return call_user_func_array($this->handlers[$this->getOperation()], []);
    }

    /**
     * Adds a new operation to the list supported.
     *
     * @param string $operation name of operation to parse for
     */
    public function addOperation($operation)
    {
        if (!in_array($operation, $this->operations)) {
            $this->operations[] = $operation;
        }
    }

    /**
     * Removes an operation from the list supported.
     *
     * @param string $operation name of operation to remove
     */
    public function removeOperation($operation)
    {
        if (in_array($operation, $this->operations)) {
            $key = array_search($operation, $this->operations);
            unset($this->operations[$key]);
        }
    }
}
