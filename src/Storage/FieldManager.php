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
    protected $typemap;

    /**
     * Constructor.
     *
     * @param array $typemap
     */
    public function __construct($typemap = [])
    {
        $this->typemap = $typemap;
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
     * Gets the field instance for the supplied class.
     *
     * @param $class
     * @param $mapping
     *
     * @return mixed
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
     * Looks up a type from the typemap and returns a field class.
     *
     * @param $type
     * @param array $mapping
     *
     * @return bool|mixed
     */
    public function getFieldFor($type)
    {
        if (!isset($this->typemap[$type])) {
            return false;
        }
        $class = $this->typemap[$type];

        return $this->get($class, ['type' => $type]);
    }

    public function setHandler($class, callable $handler)
    {
        $this->handlers[$class] = $handler;
    }
}
