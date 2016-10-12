<?php

namespace Bolt\Storage\Mapping;

/**
 * This is a base class that stores information about a contenttype field definition
 * Primarily these are defined in contenttypes.yml
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Definition
{

    protected $name;

    protected $parameters;

    public function __construct($name, array $parameters)
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }



}