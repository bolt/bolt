<?php

namespace Bolt\Twig;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use Bolt\Debug\Caster\TransparentProxyTrait;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use IteratorIterator;
use Traversable;
use Twig_Extension_Sandbox as Sandbox;

/**
 * This is a proxy for arrays and ArrayAccess objects that verifies access with a Twig Sandbox.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ArrayAccessSecurityProxy implements ArrayAccess, Countable, IteratorAggregate, SecurityProxyInterface
{
    use TransparentProxyTrait;

    /** @var array|ArrayAccess */
    protected $object;
    /** @var Sandbox */
    protected $sandbox;
    /** @var string */
    protected $class;

    /**
     * Constructor.
     *
     * @param array|ArrayAccess $array       The object or array to proxy to
     * @param Sandbox           $sandbox     The Sandbox to verify with
     * @param string            $fakeClass   A class name to use for checking with Sandbox and dumper (if object)
     * @param bool              $transparent Whether this proxy should be transparent to the VarDumper
     */
    public function __construct($array, Sandbox $sandbox, $fakeClass = null, $transparent = true)
    {
        if (!is_array($array) && !$array instanceof ArrayAccess) {
            throw new InvalidArgumentException('Must be given an array, or an object implementing ArrayAccess');
        }

        $this->object = $array;
        $this->sandbox = $sandbox;
        $this->class = $fakeClass ?: (is_object($array) ? get_class($array) : 'Array');
        $this->transparent = $transparent;
    }

    /**
     * {@inheritdoc}
     */
    public function getProxiedClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->object[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $this->sandbox->checkPropertyAllowed($this, $offset);

        return $this->object[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->object[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->object[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        if (!is_array($this->object) && !$this->object instanceof Traversable) {
            throw new BadMethodCallException('Object is not an array, or does not implement Traversable');
        }

        $this->sandbox->checkPropertyAllowed($this, 'iterator');

        if ($this->object instanceof Traversable) {
            return new IteratorIterator($this->object);
        }

        return new ArrayIterator($this->object);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if (!is_array($this->object) && !$this->object instanceof Countable) {
            throw new BadMethodCallException('Object is not an array, or does not implement Countable');
        }

        $this->sandbox->checkPropertyAllowed($this, 'count');

        return count($this->object);
    }

    /**
     * @return array|ArrayAccess
     */
    protected function getProxiedObject()
    {
        return $this->object;
    }
}
