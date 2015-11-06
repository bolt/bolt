<?php

namespace Bolt\Storage\Database\Schema\Comparison;

use Doctrine\DBAL\Schema\TableDiff;

/**
 * Comparison handling for PostgreSQL platforms.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PostgreSql extends BaseComparator
{
    /** @var string */
    protected $platform = 'postgresql';
    /** @var array */
    protected $ignoredChanges = [
        'changedColumns' => [
            'propertyName'   => 'type',
            'ignoredChanges' => [
                ['before' => 'date', 'after' => 'date'],
                ['before' => 'datetime', 'after' => 'datetime'],
                ['before' => 'string', 'after' => 'json_array'],
                ['before' => 'text', 'after' => 'json_array'],
            ],
        ]
    ];

    /**
     * {@inheritDoc}
     */
    protected function removeIgnoredChanges(TableDiff $diff)
    {
    }
}
