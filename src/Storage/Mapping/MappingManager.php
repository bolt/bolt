<?php

namespace Bolt\Storage\Mapping;

use InvalidArgumentException;

/**
 * This class takes care of looking up a mapping class from the registered handlers.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class MappingManager
{
    protected $handlers;
    protected $defaultHandler;
    protected $config;

    /**
     * MappingManager constructor.
     * @param array $handlers
     * @param string|null $defaultHandler
     * @param array $config
     */
    public function __construct(array $handlers, $defaultHandler = null, array $config = [])
    {
        $this->handlers = $handlers;
        $this->defaultHandler = $defaultHandler;
        $this->config = $config;
    }

    public function setHandlers(array $handlers)
    {
        $this->handlers = $handlers;
    }

    public function addHandler($type, $handler)
    {
        $this->handlers[$type] = $handler;
    }

    public function getHandler($type)
    {
        if (!array_key_exists($type, $this->handlers)) {
            return $this->defaultHandler;
        }
        return $this->handlers[$type];
    }

    public function setDefaultHandler($handler)
    {
        $this->defaultHandler = $handler;
    }

    public function load($name, array $parameters)
    {
        if (!array_key_exists('type', $parameters) || empty($parameters['type'])) {
            $error = sprintf('Field "%s" has no "type" set.', $name);

            throw new InvalidArgumentException($error);
        }

        $handler = $this->getHandler($parameters['type']);

        return new $handler($name, $parameters, $this->config);
    }

}