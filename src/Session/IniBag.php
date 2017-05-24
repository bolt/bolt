<?php

namespace Bolt\Session;

use Webmozart\Assert\Assert;

/**
 * IniBag is a container for ini options.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class IniBag extends OptionsBag
{
    /** @var string|null */
    private $extension;
    /** @var string|null */
    private $prefix;
    /** @var array ini keys and whether they are editable */
    private $cache;

    /**
     * Constructor.
     *
     * Note that extension and prefix, if given, are removed from the beginning of all keys.
     *
     * @param string|null $extension The extension name to pull ini values from, or null for all ini values.
     * @param string|null $prefix    An additional prefix to filter by.
     */
    public function __construct($extension = null, $prefix = null)
    {
        if ($extension) {
            if (!extension_loaded($extension)) {
                throw new \InvalidArgumentException(sprintf('Extension "%s" does not exist.', $extension));
            }

            $prefix = $extension . '.' . $prefix;
        }

        $this->extension = $extension;
        $this->prefix = $prefix;

        parent::__construct([]);
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        $values = ini_get_all($this->extension);

        $cache = [];
        $parameters = [];

        $prefixLength = strlen($this->prefix);

        foreach ($values as $key => $value) {
            // Filter out values that don't match the prefix.
            if ($this->prefix && strpos($key, $this->prefix) === false) {
                continue;
            }
            $key = substr($key, $prefixLength);
            $parameters[$key] = $value['local_value'];

            if ($this->cache === null) {
                $cache[$key] = $value['access'] === 1 /* user */ || $value['access'] === 7 /* all */;
            }
        }

        if ($this->cache === null) {
            $this->cache = $cache;
        }

        return $parameters;
    }

    /**
     * Returns all the options that are editable.
     *
     * @return array
     */
    public function allEditable()
    {
        $editable = [];

        foreach ($this as $key => $value) {
            if ($this->cache[$key]) {
                $editable[$key] = $value;
            }
        }

        return $editable;
    }

    /**
     * {@inheritdoc}
     */
    public function keys()
    {
        if ($this->cache === null) {
            $this->all();
        }

        return array_keys($this->cache);
    }

    /**
     * {@inheritdoc}
     *
     * Use set() logic.
     */
    public function add(array $parameters = [])
    {
        foreach ($parameters as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     *
     * Replace the values given, but don't remove any since that's not possible.
     */
    public function replace(array $parameters = [])
    {
        $this->add($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key, $default = null, $deep = false)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return ini_get($this->prefix . $key);
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        Assert::nullOrScalar($value, 'ini values must be scalar or null. Got: %s');

        $origValue = $value;

        // false is converted to empty string
        // converting to '0' is more consistent
        if ($value === false) {
            $value = '0';
        }

        $fullKey = $this->prefix . $key;

        $ex = null;
        set_error_handler(function ($severity, $message, $file, $line) use (&$ex) {
            $ex = new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $result = ini_set($fullKey, $value);
        } finally {
            restore_error_handler();
        }

        if ($result === false || $ex !== null) {
            if ($this->has($key)) {
                if (!$this->cache[$key]) {
                    throw new \RuntimeException(sprintf('Unable to change ini option "%s", because it is not editable at runtime.', $fullKey, $value), 0, $ex);
                }

                if (is_string($origValue)) {
                    $value = '"' . $origValue . '"';
                } elseif ($origValue === false) {
                    $value = 'false';
                } elseif ($origValue === true) {
                    $value = 'true';
                } elseif ($origValue === null) {
                    $value = 'null';
                }

                throw new \RuntimeException(sprintf('Unable to change ini option "%s" to %s.', $fullKey, $value), 0, $ex);
            }

            throw new \RuntimeException(sprintf('The ini option "%s" does not exist. New ini options cannot be added.', $fullKey), 0, $ex);
        }
    }

    /**
     * {@inheritdoc}
     *
     * Check existence after keys cache is populated.
     */
    public function has($key)
    {
        if ($this->cache === null) {
            $this->all();
        }

        return array_key_exists($key, $this->cache);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        throw new \BadMethodCallException('ini options cannot be removed.');
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->all());
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if ($this->cache === null) {
            $this->all();
        }

        return count($this->cache);
    }
}
