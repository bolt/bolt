<?php

namespace Bolt\Storage;

/**
 * Handles Object to DB naming adjustments.
 */
interface NamingStrategyInterface
{
    /**
     * Takes either a global or absolute class name and returns an underscored table name
     *
     * @param $className
     *
     * @return string
     */
    public function classToTableName($className);

    /**
     * Returns a short alias for the entity
     *
     * @param $className
     *
     * @return string
     */
    public function classToAlias($className);
}
