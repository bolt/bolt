<?php

namespace Bolt\Storage\Query;

use Bolt\Events\QueryEvent;
use Bolt\Events\QueryEvents;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Query\Directive\GetQueryDirective;
use Bolt\Storage\Query\Directive\HydrateDirective;
use Bolt\Storage\Query\Directive\LimitDirective;
use Bolt\Storage\Query\Directive\OffsetDirective;
use Bolt\Storage\Query\Directive\OrderDirective;
use Bolt\Storage\Query\Directive\PagingDirective;
use Bolt\Storage\Query\Directive\PrintQueryDirective;
use Bolt\Storage\Query\Directive\ReturnSingleDirective;
use Bolt\Storage\Query\Handler\FirstQueryHandler;
use Bolt\Storage\Query\Handler\IdentifiedSelectHandler;
use Bolt\Storage\Query\Handler\LatestQueryHandler;
use Bolt\Storage\Query\Handler\NativeSearchHandler;
use Bolt\Storage\Query\Handler\RandomQueryHandler;
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
    /** @var EntityManager */
    protected $em;
    /** @var string */
    protected $query;
    /** @var array */
    protected $params = [];
    /** @var array */
    protected $contentTypes = [];
    /** @var string */
    protected $operation;
    /** @var string */
    protected $identifier;
    /** @var array */
    protected $operations = ['search', 'latest', 'first', 'random', 'nativesearch'];
    /** @var array */
    protected $directives = [];
    /** @var callable[] */
    protected $directiveHandlers = [];
    /** @var callable[] */
    protected $handlers = [];
    /** @var QueryInterface[] */
    protected $services = [];
    /** @var QueryScopeInterface */
    protected $scope;

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
        $this->addHandler('namedselect', new IdentifiedSelectHandler());

        $this->addDirectiveHandler('getquery', new GetQueryDirective());
        $this->addDirectiveHandler('hydrate', new HydrateDirective());
        $this->addDirectiveHandler('limit', new LimitDirective());
        $this->addDirectiveHandler('order', new OrderDirective());
        $this->addDirectiveHandler('page', new OffsetDirective());
        $this->addDirectiveHandler('paging', new PagingDirective());
        $this->addDirectiveHandler('printquery', new PrintQueryDirective());
        $this->addDirectiveHandler('returnsingle', new ReturnSingleDirective());
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
    public function setParameters(array $params)
    {
        $this->params = $params;
    }

    /**
     * Sets a single input parameter.
     *
     * @param string $param
     * @param mixed  $value
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
     * Parses the content area of the query string.
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
     * A simple select operation will just contain the ContentType eg 'pages'
     * but additional operations can be triggered using the '/' separator.
     *
     * @internal
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

        if (in_array($queryParts[0], $this->operations, true)) {
            $operation = array_shift($queryParts);
            if (count($queryParts) && is_numeric($queryParts[0])) {
                $this->params['limit'] = array_shift($queryParts);
            }
            $this->identifier = implode(',', $queryParts);
        } else {
            $this->identifier = implode(',', $queryParts);
        }

        if (!empty($this->identifier)) {
            $operation = 'namedselect';
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
        $this->directives = [];

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
     * @param array          $skipDirective
     */
    public function runDirectives(QueryInterface $query, array $skipDirective = [])
    {
        foreach ($this->directives as $key => $value) {
            if (in_array($key, $skipDirective, true)) {
                continue;
            }
            if (!$this->hasDirectiveHandler($key)) {
                continue;
            }
            if (is_callable($this->getDirectiveHandler($key))) {
                call_user_func($this->getDirectiveHandler($key), $query, $value, $this->directives);
            }
        }
    }

    public function setScope(QueryScopeInterface $scope)
    {
        $this->scope = $scope;
    }

    public function runScopes(ContentQueryInterface $query)
    {
        if ($this->scope !== null) {
            $this->scope->onQueryExecute($query);
        }
    }

    /**
     * Gets the object EntityManager.
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
     * @return string|null
     */
    public function getDirective($key)
    {
        if (array_key_exists($key, $this->directives)) {
            return $this->directives[$key];
        }

        return null;
    }

    /**
     * Sets a directive for the named key.
     *
     * @param string      $key
     * @param string|bool $value
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
     * Adds a handler AND operation for the named operation.
     *
     * @param string   $operation
     * @param callable $callback
     */
    public function addHandler($operation, callable $callback)
    {
        $this->handlers[$operation] = $callback;
        $this->addOperation($operation);
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
     * Helper method to check if parameters are set for a specific key.
     *
     * @param string $param
     *
     * @return bool
     */
    public function hasParameter($param)
    {
        return array_key_exists($param, $this->params);
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
     * @return QueryResultset|Content|null
     */
    public function fetch()
    {
        $this->parse();
        $parseEvent = new QueryEvent($this);
        $this->getEntityManager()->getEventManager()->dispatch(QueryEvents::PARSE, $parseEvent);

        $result = call_user_func($this->handlers[$this->getOperation()], $this);
        $executeEvent = new QueryEvent($this, $result);
        $this->getEntityManager()->getEventManager()->dispatch(QueryEvents::EXECUTE, $executeEvent);

        return $result;
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
        if (!in_array($operation, $this->operations, true)) {
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
        if (in_array($operation, $this->operations, true)) {
            $key = array_search($operation, $this->operations, true);
            unset($this->operations[$key]);
        }
    }
}
