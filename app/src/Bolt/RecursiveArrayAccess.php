<?php

namespace Bolt;


/**
 * Class to implement Recursive Array Access. We want to use 'config' as a multi-
 * dimensional array, but we also want it to be self-contained with it's sanity
 * checks. Hence, RecursiveArrayAccess.
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 */
class RecursiveArrayAccess implements \ArrayAccess, \Iterator
{

    private $data = array();

    /**
     * Set up our data, and initialize the pointer position for iteration.
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        foreach ($data as $key => $value) $this[$key] = $value;
        $this->position = 0;
    }

    /**
     * Implement clone, otherwise we won't get a proper copy of our Object, when we need to.
     */
    public function __clone()
    {
        foreach ($this->data as $key => $value) if ($value instanceof self) $this[$key] = clone $value;
    }


    /**
     * implement ->toArray(), so we can get a 'traditional' array.
     *
     * @return array
     */
    public function toArray()
    {
        $data = $this->data;
        foreach ($data as $key => $value) if ($value instanceof self) $data[$key] = $value->toArray();
        return $data;
    }


    /**
     * Implement offsetSet, for the arrayaccess part of this class.
     *
     * @param $offset
     * @param $data
     */
    public function offsetSet($offset, $data)
    {
        if (is_array($data)) $data = new self($data);
        if ($offset === null) { // don't forget this!
            $this->data[] = $data;
        } else {
            $this->data[$offset] = $data;
        }
    }

    /**
     * Implement offsetGet, for the arrayaccess part of this class.
     *
     * @param $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        //return $this->data[$offset];
        return $this->data[$offset]->toArray();
    }

    /**
     * Implement offsetExists, for the arrayaccess part of this class.
     *
     * @param $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Implement offsetUnset, for the arrayaccess part of this class.
     *
     * @param $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data);
    }

    /**
     * Implement rewind, for the iterator part of this class.
     *
     * @return mixed
     */
    function rewind()
    {
        return reset($this->data);
    }

    /**
     * Implement current, for the iterator part of this class.
     *
     * @return mixed
     */
    function current()
    {

        $current = current($this->data);

        if (is_object($current)) {
            $current = $current->toArray();
        }

        return $current;
    }

    /*
     * Implement key, for the iterator part of this class.
     *
     * @return mixed
     */
    function key()
    {
        return key($this->data);
    }

    /**
     * Implement next, for the iterator part of this class.
     *
     * @return mixed
     */
    function next()
    {
        return next($this->data);
    }

    /**
     * Implement valid, for the iterator part of this class.
     *
     * @return bool
     */
    function valid()
    {
        return key($this->data) !== null;
    }

}
