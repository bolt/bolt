<?php

namespace Bolt\Storage\Database\Schema\Types;

use Carbon\Carbon;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * Doctrine DateTimeType using Carbon.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CarbonDateTimeType extends DateTimeType
{
    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::instance(parent::convertToPHPValue($value, $platform));
    }
}
