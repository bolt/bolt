<?php
namespace Bolt\Storage;

use Bolt\Config;

/**
 * Uses a typemap to construct an instance of a Field
 */
class FieldManager
{
    /** @var array */
    protected $em;
    protected $handlers = [];
    protected $typemap;
    protected $boltConfig;
    protected $customHandlers = [];

    /**
     * Constructor.
     * Requires access to legacy Config class so that it can add fields to the old-style manager
     * This can be removed once the templating has migrated to the new system.
     *
     * @param array  $typemap
     * @param Config $config
     */
    public function __construct(array $typemap, Config $config)
    {
        $this->typemap = $typemap;
        $this->boltConfig = $config;
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

    /**
     * Links the field name found in the config to a callable handler.
     *
     * @param $class
     * @param callable|object $handler
     */
    public function setHandler($class, $handler)
    {
        $this->handlers[$class] = $handler;
    }

    /**
     * Shorthand to add a field to both the new and legacy managers.
     *
     * @param $name
     * @param $handler
     */
    public function addFieldType($name, $handler)
    {
        $this->setHandler($name, $handler);
        $this->customHandlers[] = $name;
        $this->boltConfig->getFields()->addField($handler);
    }

    /**
     * Note, this method is for Bolt use only, as a way to distinguish which fields have been added outside of the
     * core system. It will be removed in a future version.
     *
     * @param $name
     *
     * @internal
     *
     * @return bool
     */
    public function hasCustomHandler($name)
    {
        return in_array($name, $this->customHandlers);
    }
}
