<?php

namespace Bolt\Storage\Database\Schema\Types;

use Doctrine\DBAL\Types;
use Exception;

/**
 * @internal Provided to bridge DBAL 2.5 & 2.6 support.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class ConversionException extends Types\ConversionException
{
    /**
     * Thrown when a value failed serialisation.
     *
     * @param mixed          $value
     * @param string         $format
     * @param string         $error
     * @param Exception|null $e
     *
     * @return ConversionException
     */
    public static function conversionFailedSerialization($value, $format, $error, Exception $e = null)
    {
        $actualType = is_object($value) ? get_class($value) : gettype($value);

        return new self(sprintf(
            "Could not convert PHP type '%s' to '%s', as an error was triggered by the serialization: %s",
            $actualType,
            $format,
            $error
        ), 0, $e);
    }
}
