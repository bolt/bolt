<?php

namespace Bolt\Storage;

/**
 * Handles Object to DB naming adjustments.
 */
interface NamingStrategyInterface
{

    /**
     * Takes either a global or absolute class name and returns an underscored table name
     */
    public function classToTableName($className);
    
}
