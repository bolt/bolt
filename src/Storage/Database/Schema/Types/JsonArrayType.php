<?php

namespace Bolt\Storage\Database\Schema\Types;

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
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class JsonArrayType extends Types\JsonArrayType
{
    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
