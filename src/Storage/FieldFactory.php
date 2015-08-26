<?php
namespace Bolt\Storage;

use Bolt\Storage\Field\Type\FieldTypeInterface;

/**
 * Uses a typemap to construct an instance of a Field 
 */
class FieldFactory
{
    /** @var array */
    protected $typemap;
    protected $handlers = [];
    

    /**
     * Constructor.
     *
     * @param array $typemap
     */
    public function __construct(array $typemap)
    {
        $this->typemap = $typemap;
    }
    
    public function get($class, $mapping)
    {
        if (array_key_exists($class, $this->handlers)) {
            return call_user_func_array([$this, $class], $mapping);
        }
        
        return new $class($mapping);
    }
    
    public function setHandler($class, callable $handler)
    {
        $this->handlers[$class] = $handler;
    }


}
