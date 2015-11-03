<?php

namespace Bolt\Storage;

/**
 * Handles Object to DB naming adjustments.
 */
class NamingStrategy implements NamingStrategyInterface
{
    public $prefix = '';

    public function __construct($prefix = 'bolt_')
    {
        if ($prefix) {
            $this->prefix = $prefix;
        }
    }

    /**
     * Takes either a global or absolute class name and returns an underscored table name
     *
     * @param string $className
     *
     * @return string
     */
    public function classToTableName($className)
    {
        $className = $this->getRelativeClass($className);
        $className = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $className)), '_');

        return $this->prefix . $className;
    }

    /**
     * Creates an automatic alias from a class name
     *
     * @param string $className
     *
     * @return string
     */
    public function classToAlias($className)
    {
        $className = $this->getRelativeClass($className);

        return ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $className)), '_');
    }

    /**
     * Returns a class name with namespaces removed.
     *
     * @param string $className
     *
     * @return string
     */
    public function getRelativeClass($className)
    {
        if (strpos($className, '\\') !== false) {
            $className = substr($className, strrpos($className, '\\') + 1);
        }

        return $className;
    }
}
