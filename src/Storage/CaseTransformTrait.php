<?php

namespace Bolt\Storage;

/**
 *  This trait provides re-usable methods for converting strings between different case styles.
 */
trait CaseTransformTrait
{
    /**
     * Converts a string from underscored to Camel Case.
     *
     * @param string $id A string to camelize
     *
     * @return string The camelized string
     */
    public function camelize($id)
    {
        return strtr(ucwords(strtr($id, ['_' => ' ', '.' => '_ ', '\\' => '_ '])), [' ' => '']);
    }

    /**
     * Converts a string from camel case to underscored.
     *
     * @param string $id The string to underscore
     *
     * @return string The underscored string
     */
    public function underscore($id)
    {
        return strtolower(preg_replace(['/([A-Z]+)([A-Z][a-z])/', '/([a-z\d])([A-Z])/'], ['\\1_\\2', '\\1_\\2'], $id));
    }
}
