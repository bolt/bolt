<?php

namespace Bolt\Storage\Database\Schema\Types;

use Bolt\Common\Exception\DumpException;
use Bolt\Common\Exception\ParseException;
use Bolt\Common\Json;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types;

/**
 * Type generating JSON object values.
 *
 * DBAL 2.6 deprecated JsonArrayType in favour of JsonType, and bumped the
 * minimum PHP version to 7.1. As we need to maintain support for PHP 5.5+
 * this has caused problems with schema change detection.
 *
 * @see https://github.com/bolt/bolt/issues/6856
 *
 * @internal Provided to bridge DBAL 2.5 & 2.6 support.
 *
 * @author Baptiste Clavié <clavie.b@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class JsonType extends Types\Type
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getJsonTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        try {
            return Json::dump($value);
        } catch (DumpException $e) {
            throw ConversionException::conversionFailedSerialization($value, $this->getName(), $e->getMessage(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        try {
            return Json::parse($value);
        } catch (ParseException $e) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'json';
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return !$platform->hasNativeJsonType();
    }
}
