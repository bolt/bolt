<?php

namespace Bolt\Storage\Query;

use Bolt\Storage\EntityManager;
use Bolt\Storage\Query\Handler\FirstQueryHandler;
use Bolt\Storage\Query\Handler\GetQueryHandler;
use Bolt\Storage\Query\Handler\HydrateHandler;
use Bolt\Storage\Query\Handler\LatestQueryHandler;
use Bolt\Storage\Query\Handler\LimitHandler;
use Bolt\Storage\Query\Handler\NativeSearchHandler;
use Bolt\Storage\Query\Handler\OrderHandler;
use Bolt\Storage\Query\Handler\PagingHandler;
use Bolt\Storage\Query\Handler\PrintQueryHandler;
use Bolt\Storage\Query\Handler\RandomQueryHandler;
use Bolt\Storage\Query\Handler\ReturnSingleHandler;
use Bolt\Storage\Query\Handler\SearchQueryHandler;
use Bolt\Storage\Query\Handler\SelectQueryHandler;

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

    protected $operations = ['search', 'latest', 'first', 'random', 'nativesearch'];

    protected $directives = [];

    protected $directiveHandlers = [];

    protected $handlers = [];

    protected $services = [];

    /**
     * Constructor.
     *
     * @param EntityManager  $em
     * @param QueryInterface $queryHandler
     */
    public function __construct(EntityManager $em, QueryInterface $queryHandler = null)
    {
        $this->em = $em;

        if ($queryHandler !== null) {
            $this->addService('select', $queryHandler);
        }

        $this->setupDefaults();
    }

    /**
     * Internal method to initialise the default handlers.
     */
    protected function setupDefaults()
    {
        $this->addHandler('select', new SelectQueryHandler());
        $this->addHandler('search', new SearchQueryHandler());
        $this->addHandler('random', new RandomQueryHandler());
        $this->addHandler('first', new FirstQueryHandler());
        $this->addHandler('latest', new LatestQueryHandler());
        $this->addHandler('nativesearch', new NativeSearchHandler());

        $this->addDirectiveHandler('getquery', new GetQueryHandler());
        $this->addDirectiveHandler('hydrate', new HydrateHandler());
        $this->addDirectiveHandler('limit', new LimitHandler());
        $this->addDirectiveHandler('order', new OrderHandler());
        $this->addDirectiveHandler('paging', new PagingHandler());
        $this->addDirectiveHandler('printquery', new PrintQueryHandler());
        $this->addDirectiveHandler('returnsingle', new ReturnSingleHandler());
    }
    /**
     * Sets the input query.
     *
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * Sets the input parameters to handle.
     *
     * @param array $params
     */
    public function setParameters($params)
    {
        $this->params = $params;
    }

    /**
     * Sets a single input parameter.
     *
     * @param array $param
     * @param mixed $value
     */
    public function setParameter($param, $value)
    {
        $this->params[$param] = $value;
    }

    /**
     * Parse a query.
     */
    public function parse()
    {
        $this->parseContent();
        $this->parseOperation();
        $this->parseDirectives();
    }

    /**
     * Parses the content area of the querystring.
     */
    protected function parseContent()
    {
        $contentString = strtok($this->query, '/');

        $content = [];
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
     * 'order' or 'returnsingle'.
     *
     * All these need to parsed and taken out of the params that are sent to the query.
     */
    protected function parseDirectives()
    {
        if (!$this->params) {
            return;
        }

        foreach ($this->params as $key => $value) {
            if ($this->hasDirectiveHandler($key)) {
                $this->directives[$key] = $value;
                unset($this->params[$key]);
            }
        }
    }

    /**
     * This runs the callbacks attached to each directive command.
     *
     * @param QueryInterface $query
     */
    public function runDirectives(QueryInterface $query)
    {
        foreach ($this->directives as $key => $value) {
            if ($this->hasDirectiveHandler($key)) {
                if (is_callable($this->getDirectiveHandler($key))) {
                    call_user_func_array($this->getDirectiveHandler($key), [$query, $value]);
                }
            }
        }
    }

    /**
     * Gets the object EntityManager
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Returns the parsed content types.
     *
     * @return array
     */
    public function getContentTypes()
    {
        return $this->contentTypes;
    }

    /**
     * Returns the parsed operation.
     *
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Returns the parsed identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns a directive from the parsed list.
     *
     * @param string $key
     *
     * @return string
     */
    public function getDirective($key)
    {
        return $this->directives[$key];
    }

    /**
     * Sets a directive for the named key.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setDirective($key, $value)
    {
        $this->directives[$key] = $value;
    }

    /**
     * Returns the handler for the named directive.
     *
     * @param string $check
     *
     * @return callable
     */
    public function getDirectiveHandler($check)
    {
        return $this->directiveHandlers[$check];
    }

    /**
     * Returns boolean for existence of handler.
     *
     * @param string $check
     *
     * @return bool
     */
    public function hasDirectiveHandler($check)
    {
        return array_key_exists($check, $this->directiveHandlers);
    }

    /**
     * Adds a handler for the named directive.
     *
     * @param string        $key
     * @param callable|null $callback
     */
    public function addDirectiveHandler($key, callable $callback = null)
    {
        if (!array_key_exists($key, $this->directiveHandlers)) {
            $this->directiveHandlers[$key] = $callback;
        }
    }

    /**
     * Adds a handler for the named operation.
     *
     * @param string   $operation
     * @param callable $callback
     */
    public function addHandler($operation, callable $callback)
    {
        $this->handlers[$operation] = $callback;
    }

    /**
     * Returns a handler for the named operation.
     *
     * @param string $operation
     *
     * @return callable
     */
    public function getHandler($operation)
    {
        return $this->handlers[$operation];
    }

    /**
     * Adds a service for the named operation.
     *
     * @param string         $operation
     * @param QueryInterface $service
     */
    public function addService($operation, $service)
    {
        $this->services[$operation] = $service;
    }

    /**
     * Returns a service for the named operation.
     *
     * @param string $operation
     *
     * @return QueryInterface
     */
    public function getService($operation)
    {
        return $this->services[$operation];
    }

    /**
     * Returns the current parameters.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * Returns a single named parameter.
     *
     * @param string $param
     *
     * @return array
     */
    public function getParameter($param)
    {
        return $this->params[$param];
    }

    /**
     * Runs the query and fetches the results.
     *
     * @return QueryResult
     */
    public function fetch()
    {
        $this->parse();

        return call_user_func_array($this->handlers[$this->getOperation()], [$this]);
    }

    /**
     * Getter to return the currently registered operations.
     *
     * @return array
     */
    public function getOperations()
    {
        return $this->operations;
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
