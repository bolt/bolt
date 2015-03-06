<?php

namespace Bolt\Storage;

/**
 * Handles Object to DB naming adjustments.
 */
class NamingStrategy
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
     */
    public function classToTableName($className)
    {
        if (strpos($className, '\\') !== false) {
            $className = substr($className, strrpos($className, '\\') + 1);
        }
        
        $className = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', $input)), '_');

        return $this->prefix.$className;
    }
    
}
