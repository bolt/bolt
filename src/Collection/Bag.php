<?php

namespace Bolt\Collection;

use Bolt\Helpers\Arr;

/**
 * This is an OO implementation of almost all of PHP's array functionality.
 *
 * Generally only methods dealing with a single item mutate the current bag,
 * all others return a new bag.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Bag extends ImmutableBag
{
    /**
     * Returns an immutable bag with the items from this bag.
     *
     * @return ImmutableBag
     */
    public function immutable()
    {
        return new ImmutableBag($this->items);
    }

    // region Mutating Methods

    /**
     * Adds an item to the end of this bag.
     *
     * @param mixed $item The item to append.
     */
    public function add($item)
    {
        $this->items[] = $item;
    }

    /**
     * Adds an item to the beginning of this bag.
     *
     * @param mixed $item The item to prepend.
     */
    public function prepend($item)
    {
        array_unshift($this->items, $item);
    }

    /**
     * Sets a item by key.
     *
     * @param string $key   The key
     * @param mixed  $value The value
     */
    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * Sets a value at the path given.
     * Keys will be created as needed to set the value.
     *
     * This function does not support keys that contain "/" or "[]" characters
     * because these are special tokens used when traversing the data structure.
     * A value may be appended to an existing array by using "[]" as the final
     * key of a path.
     *
     *     // Set an item at a nested structure.
     *     setPath('foo/bar', 'color');
     *
     *     // Append to a list in a nested structure.
     *     setPath('foo/baz/[]', 'a');
     *     setPath('foo/baz/[]', 'b');
     *     getPath('foo/baz'); // returns ['a', 'b']
     *
     * Note: To set values not directly under ArrayAccess objects their
     * offsetGet() method needs to be defined as return by reference.
     *
     *     public function &offsetGet($offset) {}
     *
     * @param string $path  The path to traverse and set the value at
     * @param mixed  $value The value to set
     */
    public function setPath($path, $value)
    {
        Arr::set($this->items, $path, $value);
    }

    /**
     * Remove all items from bag.
     */
    public function clear()
    {
        $this->items = [];
    }

    /**
     * Removes and returns the item at the specified key from the bag.
     *
     * @param string|integer $key     The kex of the item to remove.
     * @param mixed|null     $default The default value to return if the key is not found.
     *
     * @return mixed The removed item or default, if the bag did not contain the item.
     */
    public function remove($key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        $removed = $this->items[$key];
        unset($this->items[$key]);

        return $removed;
    }

    /**
     * Removes the given item from the bag if it is found.
     *
     * @param mixed $item
     */
    public function removeItem($item)
    {
        $key = array_search($item, $this->items, true);

        if ($key !== false) {
            unset($this->items[$key]);
        }
    }

    /**
     * Removes and returns the first item in the list.
     *
     * @return mixed|null
     */
    public function removeFirst()
    {
        return array_shift($this->items);
    }

    /**
     * Removes and returns the last item in the list.
     *
     * @return mixed|null
     */
    public function removeLast()
    {
        return array_pop($this->items);
    }

    // endregion

    // region Internal Methods

    /**
     * Don't call directly. Used for ArrayAccess.
     *
     * @internal
     *
     * @inheritdoc
     */
    public function &offsetGet($offset)
    {
        $result = null;
        if (isset($this->items[$offset])) {
            $result = &$this->items[$offset];
        }

        return $result;
    }

    /**
     * Don't call directly. Used for ArrayAccess.
     *
     * @internal
     *
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }

    /**
     * Don't call directly. Used for ArrayAccess.
     *
     * @internal
     *
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    // endregion
}
