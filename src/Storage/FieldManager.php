<?php
namespace Bolt\Storage;

/**
 * Uses a typemap to construct an instance of a Field
 */
class FieldManager
{
    /** @var array */
    protected $em;
    protected $handlers = [];

    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em = null)
    {
        $this->setEntityManager($em);
    }

    /**
     * Set an instance of EntityManager
     *
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em = null)
    {
        $this->em = $em;
    }

    /**
     * Get a field type object.
     *
     * @param string|object $class
     * @param mixed         $mapping
     *
     * @return object
     */
    public function get($class, $mapping)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        if (array_key_exists($class, $this->handlers)) {
            $handler = $this->handlers[$class];
            return call_user_func_array($handler, [$mapping, $this->em]);
        }

        return new $class($mapping, $this->em);
    }

    /**
     * Set  the field's handler.
     *
     * @param string   $class
     * @param callable $handler
     */
    public function setHandler($class, callable $handler)
    {
        $this->handlers[$class] = $handler;
    }
}
