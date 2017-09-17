<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Doctrine\DBAL;
use Doctrine\DBAL\Schema;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types;

/**
 * Comparator class.
 *
 * @internal
 *
 * @deprecated Drop when minimum PHP version is 7.1 or greater.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class Comparator extends Schema\Comparator
{
    /**
     * {@inheritdoc}
     */
    public function diffColumn(Column $column1, Column $column2)
    {
        $changedProperties = parent::diffColumn($column1, $column2);
        if (DBAL\Version::compare('2.6.0') < 0) {
            return $changedProperties;
        }

        if ($column1->getType()->getName() === Types\Type::JSON_ARRAY && $column2->getType()->getName() === 'json') {
            array_shift($changedProperties);
            $changedProperties[] = 'comment';
            $column2->setComment('(DC2Type:json)');
        }

        return $changedProperties;
    }
}
