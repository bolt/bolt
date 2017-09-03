<?php

namespace Bolt\Storage;

use Bolt\Config;
use Bolt\Storage\Field\Sanitiser\SanitiserAwareInterface;
use Bolt\Storage\Field\Sanitiser\SanitiserInterface;
use Bolt\Storage\Field\Type\FieldTypeBase;

/**
 * Uses a typemap to construct an instance of a Field.
 */
class FieldManager
{
    /** @var EntityManager */
    protected $em;
    protected $handlers = [];
    protected $typemap;
    /** @var Config */
    protected $boltConfig;
    /** @var SanitiserInterface */
    protected $sanitiser;
    protected $customHandlers = [];

    /**
     * Constructor.
     * Requires access to legacy Config class so that it can add fields to the old-style manager
     * This can be removed once the templating has migrated to the new system.
     *
     * @param array              $typemap
     * @param Config             $config
     * @param SanitiserInterface $sanitiser
     */
    public function __construct(array $typemap, Config $config, SanitiserInterface $sanitiser)
    {
        $this->typemap = $typemap;
        $this->boltConfig = $config;
        $this->sanitiser = $sanitiser;
    }

    /**
     * Set an instance of EntityManager.
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
     * @param string $class
     * @param array  $mapping
     *
     * @return FieldTypeBase
     */
    public function get($class, $mapping)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        if (array_key_exists($class, $this->handlers)) {
            $handler = $this->handlers[$class];

            $field = call_user_func($handler, $mapping, $this->em);
        } else {
            $field = new $class($mapping, $this->em);
        }

        if ($field instanceof SanitiserAwareInterface) {
            $field->setSanitiser($this->sanitiser);
        }

        return $field;
    }

    /**
     * Looks up a type from the typemap and returns a field class.
     *
     * @param string $type
     *
     * @return FieldTypeBase|false
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
     * @param string          $class
     * @param callable|object $handler
     */
    public function setHandler($class, $handler)
    {
        $this->handlers[$class] = $handler;
    }

    /**
     * Shorthand to add a field to both the new and legacy managers.
     *
     * @param string               $name
     * @param Field\FieldInterface $field
     */
    public function addFieldType($name, Field\FieldInterface $field)
    {
        $this->setHandler($name, $field);
        $this->customHandlers[] = $name;
        $this->boltConfig->getFields()->addField($field);
    }

    /**
     * Note, this method is for Bolt use only, as a way to distinguish which fields have been added outside of the
     * core system. It will be removed in a future version.
     *
     * @param string $name
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
